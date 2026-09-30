<?php
require '../../config.php';
require '../../app_helpers.php';

$userId = require_user();
$jenis = $_POST['jenis'] ?? '';
if (!isset(JENIS_BERKAS[$jenis])) fail('Jenis berkas tidak valid.');

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    $tooBig = $file && in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
    fail($tooBig ? 'Ukuran berkas melebihi 2 MB.' : 'Berkas gagal diunggah.');
}
if ($file['size'] > MAX_FILE_SIZE) fail('Ukuran berkas melebihi 2 MB.');

$head = file_get_contents($file['tmp_name'], false, null, 0, 5);
if ($head !== '%PDF-') fail('Berkas harus berformat PDF.');

$app = app_ensure($pdo, $userId);
if (!app_editable($app)) fail('Pendaftaran sudah dikirim dan tidak dapat diubah.', 409);

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0775, true);
$stored = $app['id'] . '_' . $jenis . '_' . bin2hex(random_bytes(8)) . '.pdf';
if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $stored)) fail('Berkas gagal disimpan di server.', 500);

// Hapus berkas lama dengan jenis yang sama (kecuali berkas contoh bawaan)
$old = $pdo->prepare("SELECT stored_name FROM application_files WHERE application_id = ? AND jenis = ?");
$old->execute([$app['id'], $jenis]);
$oldName = $old->fetchColumn();
if ($oldName && $oldName !== 'contoh.pdf' && is_file(UPLOAD_DIR . $oldName)) unlink(UPLOAD_DIR . $oldName);

$pdo->prepare("
    INSERT INTO application_files (application_id, jenis, stored_name, original_name, size)
    VALUES (?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE stored_name = VALUES(stored_name), original_name = VALUES(original_name),
        size = VALUES(size), status_verifikasi = NULL, uploaded_at = NOW()
")->execute([$app['id'], $jenis, $stored, mb_substr(basename($file['name']), 0, 255), $file['size']]);

json_out(['success' => true, 'message' => 'Berkas terunggah.', 'files' => app_files($pdo, (int) $app['id'])]);
