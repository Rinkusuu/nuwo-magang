<?php
require '../../config.php';

$adminId = require_admin();
$data = body();
$id = (int) ($data['id'] ?? 0);
$tanggal = trim($data['tanggal'] ?? '');
$jam = trim($data['jam'] ?? '');
$lokasi = trim($data['lokasi'] ?? '');

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !preg_match('/^\d{2}:\d{2}/', $jam) || $lokasi === '') {
    fail('Tanggal, jam, dan lokasi wawancara wajib diisi.');
}

$stmt = $pdo->prepare("SELECT status_admin FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) fail('Data pelamar tidak ditemukan.', 404);
if ($app['status_admin'] !== 'memenuhi') fail('Hanya pelamar yang lolos administrasi yang dapat dijadwalkan.');

$pdo->prepare("UPDATE applications SET jadwal_tanggal = ?, jadwal_jam = ?, jadwal_lokasi = ? WHERE id = ?")
    ->execute([$tanggal, $jam, $lokasi, $id]);
log_status($pdo, $id, 'memenuhi', 'wawancara', $adminId, "$tanggal $jam · " . mb_substr($lokasi, 0, 200));

json_out(['success' => true, 'message' => 'Jadwal wawancara disimpan.']);
