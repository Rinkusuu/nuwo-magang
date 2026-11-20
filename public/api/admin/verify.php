<?php
require __DIR__ . '/../../../backend/config.php';
require __DIR__ . '/../../../backend/app_helpers.php';
require __DIR__ . '/../../../backend/mailer.php';

$adminId = require_admin();
$data = body();
$id = (int) ($data['id'] ?? 0);

$stmt = $pdo->prepare("SELECT status_admin, nama, email, no_pendaftaran FROM applications WHERE id = ? AND submitted_at IS NOT NULL");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) fail('Data pelamar tidak ditemukan.', 404);

$syarat = $data['syarat'] ?? [];
foreach (['s1', 'semester', 'aktif'] as $k) {
    if (!in_array($syarat[$k] ?? null, ['memenuhi', 'tidak'], true)) fail('Tetapkan seluruh syarat akademik.');
}

$berkas = $data['berkas'] ?? [];
foreach (array_keys(JENIS_BERKAS) as $jenis) {
    if (!in_array($berkas[$jenis] ?? null, ['sesuai', 'perbaikan', 'tidak_sesuai'], true)) {
        fail('Tetapkan status kelima berkas.');
    }
}

$files = (array) app_files($pdo, $id);
$adaTidak = in_array('tidak', $syarat, true) || in_array('tidak_sesuai', $berkas, true) || count($files) < count(JENIS_BERKAS);
if ($adaTidak) $hasil = 'tidak_memenuhi';
elseif (in_array('perbaikan', $berkas, true)) $hasil = 'perbaikan';
else $hasil = 'memenuhi';

$catatan = trim($data['catatan'] ?? '');
if ($hasil !== 'memenuhi' && $catatan === '') fail('Catatan untuk pelamar wajib diisi.');

$pengaturan = get_pengaturan($pdo);
$batas = $hasil === 'perbaikan' ? tambah_hari_kerja((int) ($pengaturan['hari_perbaikan'] ?? 3)) : null;

$pdo->beginTransaction();
$pdo->prepare("
    UPDATE applications
    SET syarat_s1 = ?, syarat_semester = ?, syarat_aktif = ?, status_admin = ?, catatan_verifikasi = ?,
        batas_perbaikan = ?, verified_at = NOW(), verified_by = ?,
        status_seleksi = IF(? = 'memenuhi', status_seleksi, NULL)
    WHERE id = ?
")->execute([$syarat['s1'], $syarat['semester'], $syarat['aktif'], $hasil, $catatan ?: null, $batas, $adminId, $hasil, $id]);

$upd = $pdo->prepare("UPDATE application_files SET status_verifikasi = ? WHERE application_id = ? AND jenis = ?");
foreach ($berkas as $jenis => $status) $upd->execute([$status, $id, $jenis]);

log_status($pdo, $id, $app['status_admin'], $hasil, $adminId, $catatan ? mb_substr($catatan, 0, 255) : null);
$pdo->commit();

email_hasil_verifikasi($app, $hasil, $catatan, $batas);

json_out(['success' => true, 'message' => 'Hasil verifikasi disimpan.', 'hasil' => $hasil, 'batas_perbaikan' => $batas]);
