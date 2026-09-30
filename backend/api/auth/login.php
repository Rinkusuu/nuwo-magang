<?php
require '../../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = strtolower(trim($data['email'] ?? ''));
$password = $data['password'] ?? '';

$stmt = $pdo->prepare("SELECT id, nama, email, password FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Email atau kata sandi salah.']);
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['user_nama'] = $user['nama'];
$_SESSION['user_email'] = $user['email'];

echo json_encode(['success' => true, 'message' => 'Berhasil masuk.', 'nama' => $user['nama']]);
