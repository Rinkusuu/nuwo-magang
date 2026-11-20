<?php
require '../../config.php';

$id = (int) ($_GET['id'] ?? 0);
$q = $pdo->prepare("
    SELECT b.stored_name, b.original_name, a.user_id
    FROM berkas_pkl b JOIN applications a ON a.id = b.application_id WHERE b.id = ?
");
$q->execute([$id]);
$f = $q->fetch();

$pemilik = $f && (int) $f['user_id'] === pengguna_aktif();
if (!$f || (!$pemilik && (staf_aktif()['peran'] ?? '') !== 'admin')) fail('Berkas tidak ditemukan.', 404);

$path = UPLOAD_DIR . $f['stored_name'];
if (!is_file($path)) fail('Berkas tidak ditemukan di server.', 404);

$ext = strtolower(pathinfo($f['stored_name'], PATHINFO_EXTENSION));
$tipe = $ext === 'pdf' ? 'application/pdf' : 'application/octet-stream';
$nama = str_replace(['"', "\r", "\n", '\\'], '', $f['original_name']);
$ascii = preg_replace('/[^\x20-\x7E]/', '_', $nama);

header('Content-Type: ' . $tipe);
header('Content-Length: ' . filesize($path));
header('Content-Disposition: ' . ($ext === 'pdf' ? 'inline' : 'attachment') . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nama));
header('Cache-Control: private, no-store');
readfile($path);
