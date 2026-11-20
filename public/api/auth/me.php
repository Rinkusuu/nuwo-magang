<?php
require __DIR__ . '/../../../backend/config.php';

$userId = require_user();

$stmt = $pdo->prepare("SELECT id, nama, email, no_wa, foto FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) fail('Akun tidak ditemukan.', 404);

json_out(['success' => true, 'user' => $user]);
