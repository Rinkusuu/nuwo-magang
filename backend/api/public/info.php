<?php
require '../../config.php';

// Informasi periode dan daftar bidang untuk beranda dan formulir (tanpa login)
$periode = get_periode($pdo);
$bidang = $pdo->query("SELECT id, nama, singkat, deskripsi FROM bidang ORDER BY urutan, id")->fetchAll();

// Data publik yang jarang berubah: boleh disimpan peramban 60 detik, sehingga
// permintaan yang sudah di-preload di <head> beranda bisa langsung dipakai
header('Cache-Control: private, max-age=60');
json_out(['success' => true, 'periode' => $periode, 'bidang' => $bidang]);
