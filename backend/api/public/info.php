<?php
require '../../config.php';

// Informasi periode dan daftar bidang untuk beranda dan formulir (tanpa login)
$periode = get_periode($pdo);
$bidang = $pdo->query("SELECT id, nama, singkat, deskripsi FROM bidang ORDER BY urutan, id")->fetchAll();

json_out(['success' => true, 'periode' => $periode, 'bidang' => $bidang]);
