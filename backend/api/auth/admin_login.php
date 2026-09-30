<?php
require '../../config.php';

$data = json_decode(file_get_contents('php://input'), true);
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';

$stmt = $pdo->prepare("SELECT id, nama, email, password FROM admins WHERE email = ?");
$stmt->execute([$email]);
$admin = $stmt->fetch();

if (!$admin || !password_verify($password, $admin['password'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Email atau kata sandi salah.']);
    exit;
}

$_SESSION['admin_id'] = $admin['id'];
$_SESSION['admin_nama'] = $admin['nama'];
$_SESSION['admin_email'] = $admin['email'];

echo json_encode(['success' => true, 'message' => 'Berhasil masuk.', 'nama' => $admin['nama']]);
