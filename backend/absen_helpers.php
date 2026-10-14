<?php
// Helper absen harian (selfie + GPS). Dipakai endpoint api/absen/* dan admin/kehadiran.php.

require_once __DIR__ . '/app_helpers.php';

const FONT_STEMPEL = __DIR__ . '/font/PlusJakartaSans-Bold.ttf';
const LEBAR_FOTO_MAKS = 720;

// Pengaturan lokasi absen dari baris pengaturan umum (periode id=1)
function pengaturan_absen(PDO $pdo): array {
    $p = $pdo->query("SELECT kantor_lat, kantor_lng, radius_m, akurasi_maks_m FROM periode WHERE id = 1")->fetch() ?: [];
    return [
        'kantor_lat' => isset($p['kantor_lat']) ? (float) $p['kantor_lat'] : null,
        'kantor_lng' => isset($p['kantor_lng']) ? (float) $p['kantor_lng'] : null,
        'radius_m' => (int) ($p['radius_m'] ?? 150),
        'akurasi_maks_m' => (int) ($p['akurasi_maks_m'] ?? 100),
    ];
}

/**
 * Pendaftaran peserta PKL yang sedang aktif hari ini, atau alasan mengapa belum bisa absen.
 * Mengembalikan [app, batch, alasan]; alasan null berarti boleh absen.
 */
function peserta_aktif(PDO $pdo, int $userId): array {
    $app = app_by_user($pdo, $userId);
    if (!$app || $app['hasil_akhir'] !== 'diterima' || $app['kesediaan'] !== 'bersedia') {
        return [null, null, 'Absen hanya untuk peserta PKL yang sudah menyatakan bersedia.'];
    }
    $batch = batch_by_id($pdo, $app['batch_id'] ? (int) $app['batch_id'] : null);
    $hariIni = date('Y-m-d');
    if (!$batch) return [$app, null, 'Periode PKL Anda tidak ditemukan.'];
    if ($hariIni < $batch['mulai']) return [$app, $batch, 'Periode PKL Anda belum dimulai.'];
    if ($hariIni > $batch['selesai']) return [$app, $batch, 'Periode PKL Anda sudah selesai.'];
    if (!hari_kerja($hariIni)) return [$app, $batch, 'Absen hanya dapat dilakukan pada hari kerja (Senin–Jumat).'];
    return [$app, $batch, null];
}

// Senin–Jumat; Sabtu dan Minggu tidak ada absen
function hari_kerja(string $tanggal): bool {
    return (int) date('N', strtotime($tanggal)) <= 5;
}

// Absen hari ini milik pendaftaran ini, dikelompokkan per jenis
function absen_hari_ini(PDO $pdo, int $appId): array {
    $q = $pdo->prepare("SELECT id, jenis, waktu, jarak_m FROM absen WHERE application_id = ? AND tanggal = CURDATE()");
    $q->execute([$appId]);
    $hasil = ['datang' => null, 'pulang' => null];
    foreach ($q->fetchAll() as $a) $hasil[$a['jenis']] = $a;
    return $hasil;
}

// Jarak dua titik di permukaan bumi dalam meter (rumus haversine)
function jarak_meter(float $lat1, float $lng1, float $lat2, float $lng2): int {
    $r = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
}

/**
 * Buat ulang foto dari JPEG kiriman: diperkecil, lalu diberi pita stempel berisi nama,
 * jenis absen, waktu server, koordinat, dan jarak. Hasilnya JPEG baru tanpa metadata asli.
 */
function stempel_foto(string $sumber, string $tujuan, array $baris): bool {
    $img = @imagecreatefromjpeg($sumber);
    if (!$img) return false;
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w > LEBAR_FOTO_MAKS) {
        $nh = (int) round($h * LEBAR_FOTO_MAKS / $w);
        $kecil = imagescale($img, LEBAR_FOTO_MAKS, $nh, IMG_BICUBIC);
        imagedestroy($img);
        $img = $kecil;
        $w = LEBAR_FOTO_MAKS;
        $h = $nh;
    }

    // Pita navy semi-transparan di bagian bawah foto
    $ukuran = max(11, (int) round($w / 42));
    $jarakBaris = (int) round($ukuran * 1.55);
    $tinggiPita = $jarakBaris * count($baris) + $ukuran;
    imagealphablending($img, true);
    $navy = imagecolorallocatealpha($img, 10, 42, 74, 30);
    imagefilledrectangle($img, 0, $h - $tinggiPita, $w, $h, $navy);
    $putih = imagecolorallocate($img, 255, 255, 255);
    $langit = imagecolorallocate($img, 185, 216, 243);
    $y = $h - $tinggiPita + $ukuran + (int) round($ukuran * 0.6);
    foreach ($baris as $i => $teks) {
        imagettftext($img, $i === 0 ? $ukuran + 1 : $ukuran, 0, (int) round($ukuran * 0.9), $y, $i === 0 ? $putih : $langit, FONT_STEMPEL, $teks);
        $y += $jarakBaris;
    }

    $dir = dirname($tujuan);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $ok = imagejpeg($img, $tujuan, 82);
    imagedestroy($img);
    return $ok;
}
