<?php
require '../../config.php';
require '../../batas_percobaan.php';

$data = body();
$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');

$kunci = 'login:' . $email;
cek_batas($pdo, $kunci, 5, 15, 20, 30);

$stmt = $pdo->prepare("SELECT id, nama, email, password FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// tetap verifikasi walau email tidak ada, supaya waktu respons tidak membocorkan email terdaftar
$hash = $user['password'] ?? '$2y$12$Rv3ny1DkB0MXm4Pkgn4S9e4dAV/aaULJi1TKq/oVc8DLpN/UtxGK6';
if (!password_verify($password, $hash) || !$user) {
    catat_percobaan($pdo, $kunci);
    fail('Email atau kata sandi salah.', 401);
}
hapus_percobaan($pdo, $kunci);

mulai_sesi_baru();
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['user_nama'] = $user['nama'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['sidik_user'] = sidik_sandi($hash);

json_out(['success' => true, 'message' => 'Berhasil masuk.', 'nama' => $user['nama'], 'csrf' => csrf_token()]);
