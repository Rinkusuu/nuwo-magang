<?php
require '../../config.php';
require '../../batas_percobaan.php';

$data = body();
$email = strtolower(trim((string) ($data['email'] ?? '')));
$password = (string) ($data['password'] ?? '');

// Backoffice lebih ketat: 5 percobaan gagal per email dan 10 per IP dalam 15 menit
$kunci = 'admin:' . $email;
cek_batas($pdo, $kunci, 5, 15, 10);

$stmt = $pdo->prepare("SELECT id, nama, email, password, peran FROM admins WHERE email = ? AND aktif = 1");
$stmt->execute([$email]);
$admin = $stmt->fetch();

$hash = $admin['password'] ?? '$2y$12$Rv3ny1DkB0MXm4Pkgn4S9e4dAV/aaULJi1TKq/oVc8DLpN/UtxGK6';
if (!password_verify($password, $hash) || !$admin) {
    catat_percobaan($pdo, $kunci);
    fail('Email atau kata sandi salah.', 401);
}
hapus_percobaan($pdo, $kunci);

mulai_sesi_baru();
$_SESSION['admin_id'] = (int) $admin['id'];
$_SESSION['admin_nama'] = $admin['nama'];
$_SESSION['admin_email'] = $admin['email'];

json_out(['success' => true, 'message' => 'Berhasil masuk.', 'nama' => $admin['nama'], 'peran' => $admin['peran'], 'csrf' => csrf_token()]);
