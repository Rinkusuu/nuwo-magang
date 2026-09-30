<?php
require '../../config.php';

require_admin();
$id = (int) (body()['id'] ?? 0);
if (!$id) fail('ID pelamar tidak valid.');

$stmt = $pdo->prepare("SELECT id, nama, user_id FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) fail('Data pelamar tidak ditemukan.', 404);

$files = $pdo->prepare("SELECT stored_name FROM application_files WHERE application_id = ?");
$files->execute([$id]);
$storedNames = $files->fetchAll(PDO::FETCH_COLUMN);

try {
    // Menghapus akun ikut menghapus pendaftaran, berkas, penilaian, dan log (ON DELETE CASCADE)
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$app['user_id']]);
} catch (PDOException $e) {
    fail('Gagal menghapus data: ' . $e->getMessage(), 500);
}

foreach ($storedNames as $name) {
    if ($name !== 'contoh.pdf' && is_file(UPLOAD_DIR . $name)) unlink(UPLOAD_DIR . $name);
}

json_out(['success' => true, 'message' => "Data pelamar '{$app['nama']}' beserta akunnya berhasil dihapus."]);
