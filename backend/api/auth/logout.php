<?php
require '../../config.php';

// POST + CSRF supaya tautan dari situs lain tidak bisa me-logout pengguna
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Metode tidak diizinkan.', 405);

$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => $p['samesite']]);
session_destroy();
json_out(['success' => true]);
