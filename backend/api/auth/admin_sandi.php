<?php
require '../../config.php';

// Ganti kata sandi akun backoffice sendiri (admin maupun pewawancara)
$id = require_staf();
$data = body();
$lama = (string) ($data['lama'] ?? '');
$baru = (string) ($data['baru'] ?? '');

$q = $pdo->prepare("SELECT password FROM admins WHERE id = ?");
$q->execute([$id]);
if (!password_verify($lama, (string) $q->fetchColumn())) fail('Kata sandi lama salah.');
if (strlen($baru) < 12 || strlen($baru) > 72) fail('Kata sandi baru minimal 12 karakter.');
if ($baru === $lama) fail('Kata sandi baru harus berbeda dari kata sandi lama.');

$hash = password_hash($baru, PASSWORD_BCRYPT);
$pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$hash, $id]);
// Sesi ini tetap masuk; sesi lain akun ini (perangkat lain) berakhir karena sidiknya tidak cocok lagi
$_SESSION['sidik_admin'] = sidik_sandi($hash);
json_out(['success' => true, 'message' => 'Kata sandi berhasil diganti.']);
