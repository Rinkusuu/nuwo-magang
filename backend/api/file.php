<?php
require '../config.php';

// Lihat berkas PDF: hanya pemilik pendaftaran atau admin
$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT f.stored_name, f.original_name, a.user_id
    FROM application_files f JOIN applications a ON a.id = f.application_id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$f = $stmt->fetch();

$isOwner = $f && isset($_SESSION['user_id']) && (int) $f['user_id'] === (int) $_SESSION['user_id'];
if (!$f || (!$isOwner && !isset($_SESSION['admin_id']))) fail('Berkas tidak ditemukan.', 404);

$path = UPLOAD_DIR . $f['stored_name'];
if (!is_file($path)) fail('Berkas tidak ditemukan di server.', 404);

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
$nama = str_replace(['"', "\r", "\n", '\\'], '', $f['original_name']);
$ascii = preg_replace('/[^\x20-\x7E]/', '_', $nama);
header('Content-Disposition: inline; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nama));
header('Cache-Control: private, no-store');
readfile($path);
