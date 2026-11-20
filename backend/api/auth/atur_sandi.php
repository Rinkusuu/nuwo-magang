<?php
require '../../config.php';
require '../../batas_percobaan.php';

$data = body();
$token = (string) ($data['token'] ?? '');
$password = (string) ($data['password'] ?? '');

cek_batas($pdo, 'atur-sandi', PHP_INT_MAX, 15, 20);
catat_percobaan($pdo, 'atur-sandi');

if (!preg_match('/^[a-f0-9]{64}$/', $token)) fail('Tautan tidak valid. Minta tautan baru dari halaman Lupa kata sandi.');
if (strlen($password) < 8 || strlen($password) > 72) fail('Kata sandi minimal 8 karakter.');

$stmt = $pdo->prepare("SELECT id, user_id FROM reset_sandi WHERE token_hash = ? AND kedaluwarsa > NOW()");
$stmt->execute([hash('sha256', $token)]);
$reset = $stmt->fetch();
if (!$reset) fail('Tautan sudah kedaluwarsa atau sudah dipakai. Minta tautan baru dari halaman Lupa kata sandi.');

$pdo->beginTransaction();
$pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([password_hash($password, PASSWORD_BCRYPT), $reset['user_id']]);
$pdo->prepare("DELETE FROM reset_sandi WHERE user_id = ?")->execute([$reset['user_id']]);
$pdo->commit();

json_out(['success' => true, 'message' => 'Kata sandi berhasil diubah. Silakan masuk dengan kata sandi baru.']);
