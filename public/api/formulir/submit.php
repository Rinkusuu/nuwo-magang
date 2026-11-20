<?php
require __DIR__ . '/../../../backend/config.php';
require __DIR__ . '/../../../backend/app_helpers.php';
require __DIR__ . '/../../../backend/mailer.php';

$userId = require_user();
$pdo->prepare("UPDATE applications a JOIN users u ON u.id = a.user_id SET a.email = u.email WHERE a.user_id = ? AND a.email <> u.email")
    ->execute([$userId]);
$app = app_by_user($pdo, $userId);
if (!$app) fail('Lengkapi formulir terlebih dahulu.');
if (!app_editable($app)) fail('Pendaftaran sudah dikirim.', 409);

$batch = batch_by_id($pdo, $app['batch_id'] ? (int) $app['batch_id'] : null);
if (!$batch) fail('Pilih periode PKL terlebih dahulu.');
if (!batch_terbuka($batch) && $app['status_admin'] !== 'perbaikan') {
    fail("Pendaftaran {$batch['nama']} sedang tidak dibuka.");
}

$wajib = [
    'nama' => 'Nama lengkap', 'email' => 'Email', 'no_wa' => 'Nomor HP/WhatsApp', 'domisili' => 'Domisili',
    'universitas' => 'Perguruan tinggi', 'fakultas_prodi' => 'Fakultas / Program studi', 'nim' => 'NPM',
    'ipk' => 'IPK', 'jenjang' => 'Jenjang pendidikan', 'semester' => 'Semester',
];
foreach ($wajib as $f => $label) {
    if ($app[$f] === null || $app[$f] === '') fail("$label wajib diisi.");
}
if (!filter_var($app['email'], FILTER_VALIDATE_EMAIL)) fail('Email tidak valid.');
if ($app['jenjang'] !== 'S1' || (int) $app['semester'] < 4) {
    fail('Belum memenuhi syarat akademik: jenjang S1 dan minimal semester 4.');
}

$files = (array) app_files($pdo, (int) $app['id']);
foreach (JENIS_BERKAS as $jenis => $label) {
    if (!isset($files[$jenis])) fail("Berkas $label belum diunggah.");
}
if (!$app['pernyataan']) fail('Centang pernyataan kebenaran data terlebih dahulu.');

$noPendaftaran = $app['no_pendaftaran']
    ?: sprintf('MG-%s-%04d', substr($batch['mulai'], 0, 4), $app['id']);
$from = $app['submitted_at'] ? $app['status_admin'] : 'draft';

$pdo->prepare("
    UPDATE applications
    SET no_pendaftaran = ?, submitted_at = NOW(), status_admin = 'menunggu', batas_perbaikan = NULL,
        tanggal_mulai = ?, tanggal_selesai = ?
    WHERE id = ?
")->execute([$noPendaftaran, $batch['mulai'], $batch['selesai'], $app['id']]);
log_status($pdo, (int) $app['id'], $from, 'menunggu', null,
    $from === 'perbaikan' ? 'Perbaikan berkas dikirim' : 'Pendaftaran dikirim');

email_pendaftaran_terkirim(app_by_user($pdo, $userId), $from === 'perbaikan', $batch);

json_out(['success' => true, 'message' => 'Pendaftaran berhasil dikirim.', 'no_pendaftaran' => $noPendaftaran]);
