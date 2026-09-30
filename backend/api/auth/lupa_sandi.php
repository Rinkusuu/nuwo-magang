<?php
require '../../config.php';
require '../../batas_percobaan.php';
require '../../mailer.php';

// Kirim tautan atur ulang kata sandi. Jawaban selalu sama, baik email terdaftar
// maupun tidak, supaya formulir ini tidak bisa dipakai menebak email pelamar.
$email = strtolower(trim((string) (body()['email'] ?? '')));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Masukkan alamat email yang valid.');

cek_batas($pdo, 'lupa:' . $email, 3, 60, 10);
catat_percobaan($pdo, 'lupa:' . $email);

$stmt = $pdo->prepare("SELECT id, nama, email FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    $token = bin2hex(random_bytes(32));
    $pdo->prepare("DELETE FROM reset_sandi WHERE user_id = ?")->execute([$user['id']]);
    $pdo->prepare("INSERT INTO reset_sandi (user_id, token_hash, kedaluwarsa) VALUES (?, ?, NOW() + INTERVAL 60 MINUTE)")
        ->execute([$user['id'], hash('sha256', $token)]);
    email_atur_ulang_sandi($user['email'], $user['nama'], (APP_URL ?: '') . '/atur_sandi.html?token=' . $token);
}

json_out(['success' => true, 'message' => 'Jika email tersebut terdaftar, tautan untuk mengatur ulang kata sandi sudah kami kirim. Periksa juga folder spam.']);
