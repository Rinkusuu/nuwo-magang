<?php
require '../../config.php';
require '../../app_helpers.php';
require '../../mailer.php';

$userId = require_user();
$app = app_by_user($pdo, $userId);
if (!$app) fail('Lengkapi formulir terlebih dahulu.');
if (!app_editable($app)) fail('Pendaftaran sudah dikirim.', 409);

// Pendaftaran baru hanya diterima dalam rentang periode; perbaikan berkas tetap boleh
$periode = get_periode($pdo);
$today = date('Y-m-d');
if (!empty($periode['tanggal_buka']) && !empty($periode['tanggal_tutup'])
    && ($today < $periode['tanggal_buka'] || $today > $periode['tanggal_tutup'])
    && $app['status_admin'] !== 'perbaikan') {
    fail('Pendaftaran periode ini sedang tidak dibuka.');
}

$wajib = [
    'nama' => 'Nama lengkap', 'email' => 'Email', 'no_wa' => 'Nomor HP/WhatsApp', 'domisili' => 'Domisili',
    'universitas' => 'Perguruan tinggi', 'fakultas_prodi' => 'Fakultas / Program studi', 'nim' => 'NIM',
    'ipk' => 'IPK', 'jenjang' => 'Jenjang pendidikan', 'semester' => 'Semester',
    'bidang_utama_id' => 'Bidang utama', 'tanggal_mulai' => 'Tanggal mulai', 'tanggal_selesai' => 'Tanggal selesai',
];
foreach ($wajib as $f => $label) {
    if ($app[$f] === null || $app[$f] === '') fail("$label wajib diisi.");
}
if (!filter_var($app['email'], FILTER_VALIDATE_EMAIL)) fail('Email tidak valid.');
if ($app['jenjang'] !== 'S1' || (int) $app['semester'] < 4) {
    fail('Belum memenuhi syarat akademik: jenjang S1 dan minimal semester 4.');
}
if ($app['bidang_alternatif_id'] && $app['bidang_alternatif_id'] === $app['bidang_utama_id']) {
    fail('Bidang alternatif harus berbeda dari bidang utama.');
}
if ($app['tanggal_selesai'] <= $app['tanggal_mulai']) fail('Tanggal selesai harus setelah tanggal mulai.');

$files = (array) app_files($pdo, (int) $app['id']);
foreach (JENIS_BERKAS as $jenis => $label) {
    if (!isset($files[$jenis])) fail("Berkas $label belum diunggah.");
}
if (!$app['pernyataan']) fail('Centang pernyataan kebenaran data terlebih dahulu.');

$noPendaftaran = $app['no_pendaftaran']
    ?: sprintf('MG-%d-%04d', $periode['tahun'] ?? date('Y'), $app['id']);
$from = $app['submitted_at'] ? $app['status_admin'] : 'draft';

$pdo->prepare("
    UPDATE applications
    SET no_pendaftaran = ?, submitted_at = NOW(), status_admin = 'menunggu', batas_perbaikan = NULL
    WHERE id = ?
")->execute([$noPendaftaran, $app['id']]);
log_status($pdo, (int) $app['id'], $from, 'menunggu', null,
    $from === 'perbaikan' ? 'Perbaikan berkas dikirim' : 'Pendaftaran dikirim');

email_pendaftaran_terkirim(app_by_user($pdo, $userId), $from === 'perbaikan');

json_out(['success' => true, 'message' => 'Pendaftaran berhasil dikirim.', 'no_pendaftaran' => $noPendaftaran]);
