<?php
require '../../config.php';
require '../../app_helpers.php';

$userId = require_user();
$app = app_by_user($pdo, $userId);

if (!$app) json_out(['success' => true, 'has_application' => false, 'periode' => get_periode($pdo)]);

$bidang = bidang_map($pdo);
$app['bidang_utama'] = $bidang[$app['bidang_utama_id']]['nama'] ?? null;
$app['bidang_alternatif'] = $bidang[$app['bidang_alternatif_id']]['nama'] ?? null;
$app['bidang_penempatan'] = $bidang[$app['bidang_penempatan_id']]['nama'] ?? null;
$app['editable'] = app_editable($app);
unset($app['verified_by']);

json_out([
    'success' => true,
    'has_application' => true,
    'application' => $app,
    'files' => app_files($pdo, (int) $app['id']),
    'periode' => get_periode($pdo),
]);
