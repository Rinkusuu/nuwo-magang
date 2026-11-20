<?php
require '../../config.php';

$id = (int) ($_GET['id'] ?? 0);
$q = $pdo->prepare("SELECT ab.foto, a.user_id FROM absen ab JOIN applications a ON a.id = ab.application_id WHERE ab.id = ?");
$q->execute([$id]);
$f = $q->fetch();

$pemilik = $f && (int) $f['user_id'] === pengguna_aktif();
$admin = (staf_aktif()['peran'] ?? '') === 'admin';
if (!$f || !$f['foto'] || (!$pemilik && !$admin)) fail('Foto tidak ditemukan.', 404);

$path = UPLOAD_DIR . 'absen/' . $f['foto'];
if (!is_file($path) || str_contains($f['foto'], '..')) fail('Foto tidak ditemukan.', 404);

header('Content-Type: image/jpeg');
header('Content-Length: ' . filesize($path));
header('Cache-Control: private, no-store');
readfile($path);
