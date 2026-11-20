<?php
require '../config.php';

$id = (int) ($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT f.stored_name, f.original_name, a.user_id, a.status_seleksi
    FROM application_files f JOIN applications a ON a.id = f.application_id
    WHERE f.id = ?
");
$stmt->execute([$id]);
$f = $stmt->fetch();

$isOwner = $f && (int) $f['user_id'] === pengguna_aktif();
$staf = staf_aktif();
$bolehStaf = $f && $staf && ($staf['peran'] === 'admin' || $f['status_seleksi'] === 'lolos');
if (!$f || (!$isOwner && !$bolehStaf)) fail('Berkas tidak ditemukan.', 404);

$path = UPLOAD_DIR . $f['stored_name'];
if (!is_file($path)) fail('Berkas tidak ditemukan di server.', 404);

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($path));
$nama = str_replace(['"', "\r", "\n", '\\'], '', $f['original_name']);
$ascii = preg_replace('/[^\x20-\x7E]/', '_', $nama);
header('Content-Disposition: inline; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nama));
header('Cache-Control: private, no-store');
readfile($path);
