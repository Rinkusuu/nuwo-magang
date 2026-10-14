<?php
require '../../config.php';

$adminId = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    json_out(['success' => true, 'periode' => get_periode($pdo)]);
}

// Jadwal kini diatur per batch (batch.php); di sini pengaturan umum:
// batas perbaikan berkas serta titik kantor, radius, dan akurasi GPS untuk absen
$data = body();
$hari = (int) ($data['hari_perbaikan'] ?? 3);
if ($hari < 1 || $hari > 30) fail('Batas perbaikan harus 1–30 hari kerja.');

$angka = fn($k) => ($data[$k] ?? '') === '' ? null : filter_var($data[$k], FILTER_VALIDATE_FLOAT);
$lat = $angka('kantor_lat');
$lng = $angka('kantor_lng');
if ($lat === false || $lng === false || ($lat === null) !== ($lng === null)) fail('Isi lintang dan bujur kantor sebagai angka, atau kosongkan keduanya.');
if ($lat !== null && (abs($lat) > 90 || abs($lng) > 180)) fail('Koordinat kantor tidak valid.');
$radius = (int) ($data['radius_m'] ?? 150);
$akurasi = (int) ($data['akurasi_maks_m'] ?? 100);
if ($radius < 20 || $radius > 2000) fail('Radius absen harus 20–2.000 meter.');
if ($akurasi < 10 || $akurasi > 1000) fail('Akurasi GPS maksimal harus 10–1.000 meter.');

$pdo->prepare("
    INSERT INTO periode (id, tahun, hari_perbaikan, kantor_lat, kantor_lng, radius_m, akurasi_maks_m)
    VALUES (1, YEAR(CURDATE()), ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE hari_perbaikan = VALUES(hari_perbaikan), kantor_lat = VALUES(kantor_lat),
        kantor_lng = VALUES(kantor_lng), radius_m = VALUES(radius_m), akurasi_maks_m = VALUES(akurasi_maks_m)
")->execute([$hari, $lat, $lng, $radius, $akurasi]);

json_out(['success' => true, 'message' => 'Pengaturan disimpan.']);
