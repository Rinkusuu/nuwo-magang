<?php
require '../../config.php';
require '../../batas_percobaan.php';

$data = body();

$nama = trim((string) ($data['nama'] ?? ''));
$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');

cek_batas($pdo, 'daftar', PHP_INT_MAX, 60, 10);

if (mb_strlen($nama) < 3 || mb_strlen($nama) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($password) < 8 || strlen($password) > 72) {
    fail('Data tidak lengkap atau kata sandi kurang dari 8 karakter.');
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) fail('Email sudah terdaftar. Silakan masuk.', 409);

$stmt = $pdo->prepare("INSERT INTO users (nama, email, password) VALUES (?, ?, ?)");
$hash = password_hash($password, PASSWORD_BCRYPT);
$stmt->execute([$nama, $email, $hash]);
// ambil sebelum catat_percobaan() menimpa lastInsertId
$userId = (int) $pdo->lastInsertId();
catat_percobaan($pdo, 'daftar');

mulai_sesi_baru();
$_SESSION['user_id'] = $userId;
$_SESSION['user_nama'] = $nama;
$_SESSION['user_email'] = $email;
$_SESSION['sidik_user'] = sidik_sandi($hash);

json_out(['success' => true, 'message' => 'Akun berhasil dibuat.', 'csrf' => csrf_token()]);
