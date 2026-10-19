<?php
require '../../config.php';
require '../../app_helpers.php';
require '../../pembersihan_helpers.php';

$userId = require_user();
$app = app_by_user($pdo, $userId);

if (!$app) json_out(['success' => true, 'has_application' => false, 'periode' => get_periode($pdo)]);

$app['editable'] = app_editable($app);
$app['hapus_pada'] = tanggal_hapus_akun($app);   // pelamar yang ditolak: tanggal akun dihapus otomatis
unset($app['verified_by'], $app['seleksi_by']);

// Informasi batch bertahap: link Zoom hanya untuk yang lolos ke wawancara,
// link grup WhatsApp hanya untuk peserta diterima yang sudah menyatakan bersedia
$batch = batch_by_id($pdo, $app['batch_id'] ? (int) $app['batch_id'] : null);
if ($batch) {
    $info = batch_publik($batch);
    if ($app['status_seleksi'] === 'lolos') {
        $info += ['wawancara_tanggal' => $batch['wawancara_tanggal'], 'wawancara_jam' => $batch['wawancara_jam'], 'link_zoom' => $batch['link_zoom']];
    }
    if ($app['hasil_akhir'] === 'diterima' && $app['kesediaan'] === 'bersedia') $info['link_wa'] = $batch['link_wa'];
    $batch = $info;
}

json_out([
    'success' => true,
    'has_application' => true,
    'application' => $app,
    'files' => app_files($pdo, (int) $app['id']),
    'batch' => $batch,
    'berkas_pkl' => berkas_pkl($pdo, (int) $app['id']),
    'periode' => get_periode($pdo),
]);
