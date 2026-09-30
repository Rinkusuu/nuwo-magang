<?php
require '../../config.php';
require '../../batas_percobaan.php';

$data = body();

$nama = trim((string) ($data['nama'] ?? ''));
$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');
$no_wa = trim((string) ($data['no_wa'] ?? ''));

// Mencegah pembuatan akun massal: maksimal 10 akun per IP per jam
cek_batas($pdo, 'daftar', PHP_INT_MAX, 60, 10);

if (mb_strlen($nama) < 3 || mb_strlen($nama) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)
    || strlen($password) < 8 || strlen($password) > 72 || strlen(preg_replace('/\D/', '', $no_wa)) < 9) {
    fail('Data tidak lengkap atau kata sandi kurang dari 8 karakter.');
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->fetch()) fail('Email sudah terdaftar. Silakan masuk.', 409);

$stmt = $pdo->prepare("INSERT INTO users (nama, email, password, no_wa) VALUES (?, ?, ?, ?)");
$stmt->execute([$nama, $email, password_hash($password, PASSWORD_BCRYPT), mb_substr($no_wa, 0, 30)]);
// Ambil ID akun SEBELUM query INSERT lain (catat_percobaan) mengubah lastInsertId
$userId = (int) $pdo->lastInsertId();
catat_percobaan($pdo, 'daftar');

mulai_sesi_baru();
$_SESSION['user_id'] = $userId;
$_SESSION['user_nama'] = $nama;
$_SESSION['user_email'] = $email;

json_out(['success' => true, 'message' => 'Akun berhasil dibuat.', 'csrf' => csrf_token()]);
