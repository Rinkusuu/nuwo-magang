<?php
require '../../config.php';
require '../../app_helpers.php';
require '../../logbook_excel.php';

// Unduh logbook sebagai Excel: peserta untuk miliknya sendiri, admin untuk peserta mana pun (?id=)
$admin = (staf_aktif()['peran'] ?? '') === 'admin';
if ($admin && isset($_GET['id'])) {
    $q = $pdo->prepare("SELECT * FROM applications WHERE id = ?");
    $q->execute([(int) $_GET['id']]);
    $app = $q->fetch() ?: null;
} else {
    $app = app_by_user($pdo, require_user());
}
if (!$app || $app['hasil_akhir'] !== 'diterima' || $app['kesediaan'] !== 'bersedia') fail('Logbook tidak ditemukan.', 404);
$batch = batch_by_id($pdo, $app['batch_id'] ? (int) $app['batch_id'] : null);
if (!$batch) fail('Periode PKL tidak ditemukan.', 404);

$tmp = tempnam(sys_get_temp_dir(), 'lb');
buat_logbook_excel($pdo, $app, $batch, $tmp);
$nama = 'Logbook Magang - ' . preg_replace('/[\\\\\/:*?"<>|\r\n]/', '', $app['nama']) . '.xlsx';
$ascii = preg_replace('/[^\x20-\x7E]/', '_', $nama);
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Length: ' . filesize($tmp));
header('Content-Disposition: attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . rawurlencode($nama));
header('Cache-Control: private, no-store');
readfile($tmp);
unlink($tmp);
