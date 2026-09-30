<?php
require '../../config.php';

$data = body();

$nama = trim($data['nama'] ?? '');
$email = strtolower(trim($data['email'] ?? ''));
$password = $data['password'] ?? '';
$no_wa = trim($data['no_wa'] ?? '');

if (!$nama || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || !$no_wa) {
    fail('Data tidak lengkap atau kata sandi kurang dari 8 karakter.');
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) fail('Email sudah terdaftar. Silakan masuk.', 409);

$stmt = $pdo->prepare("INSERT INTO users (nama, email, password, no_wa) VALUES (?, ?, ?, ?)");
$stmt->execute([$nama, $email, password_hash($password, PASSWORD_BCRYPT), $no_wa]);

$_SESSION['user_id'] = (int) $pdo->lastInsertId();
$_SESSION['user_nama'] = $nama;
$_SESSION['user_email'] = $email;

json_out(['success' => true, 'message' => 'Akun berhasil dibuat.']);
