<?php
require __DIR__ . '/../../../backend/config.php';
require __DIR__ . '/../../../backend/app_helpers.php';

$batch = $pdo->query("SELECT * FROM batch WHERE selesai >= CURDATE() ORDER BY daftar_buka, id")->fetchAll();

header('Cache-Control: private, max-age=60');
json_out(['success' => true, 'hari_ini' => date('Y-m-d'), 'batch' => array_map('batch_publik', $batch)]);
