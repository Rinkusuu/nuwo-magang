<?php
require '../../config.php';
require '../../absen_helpers.php';

$userId = require_user();
[$app, , $alasan] = peserta_aktif($pdo, $userId);
if ($alasan) fail($alasan, 403);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['success' => true, 'logbook' => logbook_status($pdo, $app)]);
}

$hari = absen_hari_ini($pdo, (int) $app['id']);
if (!$hari['datang']) fail('Absen datang terlebih dahulu sebelum mengisi logbook.', 409);

[$isi, $galat] = validasi_logbook((string) (body()['isi'] ?? ''));
if ($galat) fail($galat);

$pdo->beginTransaction();
// FOR UPDATE: jangan sampai logbook berubah bersamaan dengan absen pulang di kirim.php
$k = $pdo->prepare("SELECT id FROM absen WHERE application_id = ? AND tanggal = CURDATE() AND jenis = 'pulang' FOR UPDATE");
$k->execute([$app['id']]);
if ($k->fetch()) { $pdo->rollBack(); fail('Logbook hari ini sudah terkunci karena Anda sudah absen pulang.', 409); }
$pdo->prepare("INSERT INTO logbook (application_id, tanggal, isi) VALUES (?, CURDATE(), ?)
    ON DUPLICATE KEY UPDATE isi = VALUES(isi), diubah_at = NOW()")->execute([$app['id'], $isi]);
$pdo->commit();

json_out(['success' => true, 'message' => 'Logbook tersimpan.', 'logbook' => logbook_status($pdo, $app)]);
