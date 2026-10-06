<?php
require '../../config.php';

$adminId = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['success' => true, 'periode' => get_periode($pdo)]);
}

// Jadwal kini diatur per batch (batch.php); di sini tinggal pengaturan umum
$hari = (int) (body()['hari_perbaikan'] ?? 3);
if ($hari < 1 || $hari > 30) fail('Batas perbaikan harus 1–30 hari kerja.');

$pdo->prepare("
    INSERT INTO periode (id, tahun, hari_perbaikan) VALUES (1, YEAR(CURDATE()), ?)
    ON DUPLICATE KEY UPDATE hari_perbaikan = VALUES(hari_perbaikan)
")->execute([$hari]);

json_out(['success' => true, 'message' => 'Pengaturan disimpan.']);
