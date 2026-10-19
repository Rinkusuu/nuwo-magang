<?php
require '../../config.php';
require '../../absen_helpers.php';

// Status absen hari ini untuk halaman absen peserta
$userId = require_user();
[$app, $batch, $alasan] = peserta_aktif($pdo, $userId);
$atur = pengaturan_absen($pdo);
$siapLokasi = $atur['kantor_lat'] !== null && $atur['kantor_lng'] !== null;

json_out([
    'success' => true,
    'boleh' => $alasan === null && $siapLokasi,
    'alasan' => $alasan ?? ($siapLokasi ? null : 'Lokasi kantor untuk absen belum diatur panitia.'),
    'hari_ini' => date('Y-m-d'),
    'nama' => $app['nama'] ?? null,
    'batch' => $batch ? ['nama' => $batch['nama'], 'mulai' => $batch['mulai'], 'selesai' => $batch['selesai']] : null,
    'absen' => $app ? absen_hari_ini($pdo, (int) $app['id']) : ['datang' => null, 'pulang' => null],
    'lokasi' => $siapLokasi ? $atur : null,
    'logbook' => logbook_status($pdo, $alasan === null ? $app : null),
]);
