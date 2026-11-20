<?php
require __DIR__ . '/../../../backend/config.php';
require __DIR__ . '/../../../backend/absen_helpers.php';

$userId = require_user();
$jenis = body()['jenis'] ?? '';
if (!in_array($jenis, ['datang', 'pulang'], true)) fail('Jenis absen tidak valid.');

[$app, , $alasan] = peserta_aktif($pdo, $userId);
if ($alasan) fail($alasan, 403);
$atur = pengaturan_absen($pdo);
if ($atur['kantor_lat'] === null) fail('Lokasi kantor untuk absen belum diatur panitia.', 403);

$hari = absen_hari_ini($pdo, (int) $app['id']);
if ($hari[$jenis]) fail($jenis === 'datang' ? 'Anda sudah absen datang hari ini.' : 'Anda sudah absen pulang hari ini.', 409);
if ($jenis === 'pulang' && !$hari['datang']) fail('Absen datang terlebih dahulu sebelum absen pulang.', 409);
if ($jenis === 'pulang') {
    $logbook = logbook_hari_ini($pdo, (int) $app['id']);
    if (!$logbook || validasi_logbook($logbook['isi'])[1]) fail('Isi dan simpan logbook hari ini sebelum absen pulang.', 409);
}

$pdo->prepare("DELETE FROM absen_token WHERE application_id = ? AND (kedaluwarsa < NOW() OR dipakai_at IS NOT NULL)")->execute([$app['id']]);
$token = bin2hex(random_bytes(32));
$pdo->prepare("INSERT INTO absen_token (application_id, jenis, token_hash, kedaluwarsa) VALUES (?, ?, ?, NOW() + INTERVAL 2 MINUTE)")
    ->execute([$app['id'], $jenis, hash('sha256', $token)]);

json_out(['success' => true, 'token' => $token, 'berlaku_detik' => 120]);
