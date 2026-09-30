<?php
require '../../config.php';

$adminId = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['success' => true, 'periode' => get_periode($pdo)]);
}

$data = body();
$tahun = (int) ($data['tahun'] ?? 0);
$buka = trim($data['tanggal_buka'] ?? '') ?: null;
$tutup = trim($data['tanggal_tutup'] ?? '') ?: null;
$durasi = trim($data['durasi'] ?? '') ?: null;
$kuota = ($data['kuota'] ?? '') === '' ? null : (int) $data['kuota'];
$hari = (int) ($data['hari_perbaikan'] ?? 3);

if ($tahun < 2000 || $tahun > 2100) fail('Tahun periode tidak valid.');
if ($buka && $tutup && $tutup < $buka) fail('Tanggal tutup harus setelah tanggal buka.');
if ($kuota !== null && $kuota < 0) fail('Kuota tidak valid.');
if ($hari < 1 || $hari > 30) fail('Batas perbaikan harus 1–30 hari kerja.');

$pdo->prepare("
    INSERT INTO periode (id, tahun, tanggal_buka, tanggal_tutup, durasi, kuota, hari_perbaikan)
    VALUES (1, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE tahun = VALUES(tahun), tanggal_buka = VALUES(tanggal_buka), tanggal_tutup = VALUES(tanggal_tutup),
        durasi = VALUES(durasi), kuota = VALUES(kuota), hari_perbaikan = VALUES(hari_perbaikan)
")->execute([$tahun, $buka, $tutup, $durasi, $kuota, $hari]);

json_out(['success' => true, 'message' => 'Periode dan kuota disimpan.']);
