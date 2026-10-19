<?php
require '../../config.php';
require '../../app_helpers.php';

// Unggah laporan akhir (PDF) oleh peserta yang sudah menyatakan bersedia; unggahan baru menggantikan
// yang lama. Logbook tidak lagi diunggah: diisi harian di halaman absen dan diunduh sebagai Excel.
const BERKAS_PKL = [
    'laporan' => ['label' => 'Laporan akhir', 'ekstensi' => ['pdf'], 'maks' => 10 * 1024 * 1024],
];

$userId = require_user();
$jenis = $_POST['jenis'] ?? '';
if (!isset(BERKAS_PKL[$jenis])) fail('Jenis berkas tidak valid.');
$aturan = BERKAS_PKL[$jenis];

$app = app_by_user($pdo, $userId);
if (!$app || $app['hasil_akhir'] !== 'diterima' || $app['kesediaan'] !== 'bersedia') {
    fail('Laporan akhir hanya untuk peserta PKL yang sudah menyatakan bersedia.', 403);
}

$file = $_FILES['file'] ?? null;
$mb = $aturan['maks'] / 1048576;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $terlaluBesar = $file && in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
    fail($terlaluBesar ? "Ukuran berkas melebihi $mb MB." : 'Berkas gagal diunggah.');
}
if ($file['size'] > $aturan['maks']) fail("Ukuran berkas melebihi $mb MB.");

// Periksa isi berkas, bukan hanya nama: PDF diawali %PDF-
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$kepala = file_get_contents($file['tmp_name'], false, null, 0, 8);
if (!in_array($ext, $aturan['ekstensi'], true) || !str_starts_with($kepala, '%PDF-')) fail('Laporan akhir harus berupa berkas PDF.');

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
$simpan = 'pkl_' . $app['id'] . '_' . $jenis . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $simpan)) fail('Berkas gagal disimpan di server.', 500);

$lama = $pdo->prepare("SELECT stored_name FROM berkas_pkl WHERE application_id = ? AND jenis = ?");
$lama->execute([$app['id'], $jenis]);
$namaLama = $lama->fetchColumn();
if ($namaLama && is_file(UPLOAD_DIR . $namaLama)) unlink(UPLOAD_DIR . $namaLama);

$pdo->prepare("
    INSERT INTO berkas_pkl (application_id, jenis, stored_name, original_name, size) VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE stored_name = VALUES(stored_name), original_name = VALUES(original_name), size = VALUES(size), uploaded_at = NOW()
")->execute([$app['id'], $jenis, $simpan, mb_substr(basename($file['name']), 0, 255), $file['size']]);

json_out(['success' => true, 'message' => $aturan['label'] . ' terunggah.', 'berkas_pkl' => berkas_pkl($pdo, (int) $app['id'])]);
