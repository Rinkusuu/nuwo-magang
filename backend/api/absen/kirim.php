<?php
require '../../config.php';
require '../../absen_helpers.php';

// Menyimpan absen: token sekali pakai + foto dari kamera + lokasi GPS.
// Waktu absen selalu waktu server; foto dibuat ulang dan diberi stempel oleh server.
$userId = require_user();
[$app, $batch, $alasan] = peserta_aktif($pdo, $userId);
if ($alasan) fail($alasan, 403);

$token = (string) ($_POST['token'] ?? '');
if (!preg_match('/^[a-f0-9]{64}$/', $token)) fail('Sesi absen tidak valid. Mulai ulang absen.', 400);

$pdo->beginTransaction();
$q = $pdo->prepare("SELECT id, jenis FROM absen_token WHERE token_hash = ? AND application_id = ? AND dipakai_at IS NULL AND kedaluwarsa > NOW() FOR UPDATE");
$q->execute([hash('sha256', $token), $app['id']]);
$sesi = $q->fetch();
if (!$sesi) { $pdo->rollBack(); fail('Sesi absen sudah kedaluwarsa atau sudah dipakai. Mulai ulang absen.', 409); }
// Token langsung hangus, apa pun hasil pemeriksaan berikutnya
$pdo->prepare("UPDATE absen_token SET dipakai_at = NOW() WHERE id = ?")->execute([$sesi['id']]);
$pdo->commit();
$jenis = $sesi['jenis'];

$hari = absen_hari_ini($pdo, (int) $app['id']);
if ($hari[$jenis]) fail('Absen ini sudah tercatat hari ini.', 409);
if ($jenis === 'pulang' && !$hari['datang']) fail('Absen datang terlebih dahulu sebelum absen pulang.', 409);

// Lokasi
$atur = pengaturan_absen($pdo);
if ($atur['kantor_lat'] === null) fail('Lokasi kantor untuk absen belum diatur panitia.', 403);
$lat = filter_var($_POST['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($_POST['lng'] ?? null, FILTER_VALIDATE_FLOAT);
$akurasi = filter_var($_POST['akurasi'] ?? null, FILTER_VALIDATE_FLOAT);
if ($lat === false || $lng === false || $akurasi === false || abs($lat) > 90 || abs($lng) > 180 || $akurasi < 0) {
    fail('Lokasi tidak terbaca. Aktifkan GPS lalu coba lagi.');
}
if ($akurasi > $atur['akurasi_maks_m']) {
    fail('Sinyal GPS kurang akurat (±' . round($akurasi) . ' m). Pindah ke tempat terbuka atau dekat jendela, lalu coba lagi.');
}
$jarak = jarak_meter($lat, $lng, $atur['kantor_lat'], $atur['kantor_lng']);
if ($jarak > $atur['radius_m']) {
    fail('Anda berada sekitar ' . number_format($jarak, 0, ',', '.') . ' m dari kantor. Absen hanya bisa dilakukan dalam radius ' . $atur['radius_m'] . ' m.');
}

// Foto: harus JPEG asli dari kamera, maksimal 3 MB
$file = $_FILES['foto'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK || $file['size'] > 3 * 1024 * 1024) fail('Foto gagal dikirim. Coba lagi.');
$info = @getimagesize($file['tmp_name']);
if (!$info || $info[2] !== IMAGETYPE_JPEG || $info[0] < 200 || $info[1] < 200) fail('Foto tidak valid. Ambil ulang foto dari kamera.');

$waktu = date('Y-m-d H:i:s');
$nama = date('Y-m') . '/' . $app['id'] . '_' . date('Ymd') . '_' . $jenis . '_' . bin2hex(random_bytes(6)) . '.jpg';
$baris = [
    $app['nama'] . ' · Absen ' . ($jenis === 'datang' ? 'datang' : 'pulang'),
    tanggal_id_singkat($waktu) . ' · ' . substr($waktu, 11, 5) . ' WIB (waktu server)',
    sprintf('%.6f, %.6f · ±%d m · %d m dari kantor', $lat, $lng, round($akurasi), $jarak),
];
if (!stempel_foto($file['tmp_name'], UPLOAD_DIR . 'absen/' . $nama, $baris)) fail('Foto gagal diproses. Ambil ulang foto.', 500);

try {
    $pdo->beginTransaction();
    // Absen pulang hanya tercatat bila logbook hari ini ada dan valid. Baris logbook dikunci
    // supaya tidak bisa diubah bersamaan; setelah absen pulang tersimpan, logbook terkunci.
    if ($jenis === 'pulang') {
        $l = $pdo->prepare("SELECT isi FROM logbook WHERE application_id = ? AND tanggal = ? FOR UPDATE");
        $l->execute([$app['id'], substr($waktu, 0, 10)]);
        $isi = $l->fetchColumn();
        if ($isi === false || validasi_logbook($isi)[1]) {
            $pdo->rollBack();
            @unlink(UPLOAD_DIR . 'absen/' . $nama);
            fail('Isi dan simpan logbook hari ini sebelum absen pulang.', 409);
        }
    }
    $pdo->prepare("INSERT INTO absen (application_id, tanggal, jenis, waktu, lat, lng, akurasi_m, jarak_m, foto, ip, user_agent)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)")
        ->execute([$app['id'], substr($waktu, 0, 10), $jenis, $waktu, $lat, $lng, (int) round($akurasi), $jarak, $nama,
            substr($_SERVER['REMOTE_ADDR'] ?? '', 0, 45), mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255)]);
    $pdo->commit();
} catch (PDOException $e) {
    // Dua kiriman bersamaan: kunci unik mencegah absen ganda
    if ($pdo->inTransaction()) $pdo->rollBack();
    @unlink(UPLOAD_DIR . 'absen/' . $nama);
    fail('Absen ini sudah tercatat hari ini.', 409);
}

json_out(['success' => true, 'message' => 'Absen ' . $jenis . ' tercatat pukul ' . substr($waktu, 11, 5) . ' WIB.',
    'absen' => absen_hari_ini($pdo, (int) $app['id']), 'logbook' => logbook_status($pdo, $app)]);

function tanggal_id_singkat(string $waktu): string {
    $bulan = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    [$y, $m, $d] = array_map('intval', explode('-', substr($waktu, 0, 10)));
    return "$d {$bulan[$m - 1]} $y";
}
