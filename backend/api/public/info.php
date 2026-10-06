<?php
require '../../config.php';
require '../../app_helpers.php';

// Jadwal batch untuk beranda dan formulir (tanpa login). Hanya batch yang pendaftarannya
// belum lewat atau pelaksanaannya belum selesai; link Zoom dan grup WhatsApp tidak ikut.
$batch = $pdo->query("SELECT * FROM batch WHERE selesai >= CURDATE() ORDER BY daftar_buka, id")->fetchAll();

// Data publik yang jarang berubah: boleh disimpan peramban 60 detik, sehingga
// permintaan yang sudah di-preload di <head> beranda bisa langsung dipakai
header('Cache-Control: private, max-age=60');
json_out(['success' => true, 'hari_ini' => date('Y-m-d'), 'batch' => array_map('batch_publik', $batch)]);
