<?php
require '../../config.php';

// Unduh logbook atau laporan akhir: hanya pemilik atau admin
$id = (int) ($_GET['id'] ?? 0);
$q = $pdo->prepare("
    SELECT b.stored_name, b.original_name, a.user_id
    FROM berkas_pkl b JOIN applications a ON a.id = b.application_id WHERE b.id = ?
");
$q->execute([$id]);
$f = $q->fetch();

$pemilik = $f && isset($_SESSION['user_id']) && (int) $f['user_id'] === (int) $_SESSION['user_id'];
if (!$f || (!$pemilik && (staf_aktif()['peran'] ?? '') !== 'admin')) fail('Berkas tidak ditemukan.', 404);

$path = UPLOAD_DIR . $f['stored_name'];
if (!is_file($path)) fail('Berkas tidak ditemukan di server.', 404);

$ext = strtolower(pathinfo($f['stored_name'], PATHINFO_EXTENSION));
$tipe = ['pdf' => 'application/pdf', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'xls' => 'application/vnd.ms-excel'][$ext] ?? 'application/octet-stream';
$nama = str_replace(['"', "\r", "\n", '\\'], '', $f['original_name']);
$ascii = preg_replace('/[^\x20-\x7E]/', '_', $nama);

header('Content-Type: ' . $tipe);
header('Content-Length: ' . filesize($path));
// PDF dibuka di peramban, Excel diunduh
header('Content-Disposition: ' . ($ext === 'pdf' ? 'inline' : 'attachment') . '; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nama));
header('Cache-Control: private, no-store');
readfile($path);
