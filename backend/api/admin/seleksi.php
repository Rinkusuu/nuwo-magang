<?php
require '../../config.php';
require '../../app_helpers.php';
require '../../mailer.php';

// Screening manual setelah berkas dinyatakan lengkap: lolos ke wawancara, tidak lolos,
// atau dibatalkan (null). Email hanya dikirim saat pelamar baru dinyatakan lolos.
$adminId = require_admin();
$data = body();
$id = (int) ($data['id'] ?? 0);
$keputusan = $data['keputusan'] ?? null;
if ($keputusan !== null && !in_array($keputusan, ['lolos', 'tidak_lolos'], true)) fail('Keputusan tidak valid.');

$q = $pdo->prepare("SELECT * FROM applications WHERE id = ? AND submitted_at IS NOT NULL");
$q->execute([$id]);
$app = $q->fetch();
if (!$app) fail('Data pelamar tidak ditemukan.', 404);
if ($app['status_admin'] !== 'memenuhi') fail('Hanya pelamar dengan berkas lengkap (memenuhi) yang dapat diseleksi.');
if ($app['hasil_akhir'] !== null) fail('Hasil akhir pelamar ini sudah ditetapkan. Batalkan hasilnya terlebih dahulu.');

$pdo->prepare("UPDATE applications SET status_seleksi = ?, seleksi_at = ?, seleksi_by = ? WHERE id = ?")
    ->execute([$keputusan, $keputusan ? date('Y-m-d H:i:s') : null, $keputusan ? $adminId : null, $id]);
log_status($pdo, $id, $app['status_seleksi'] ?? 'memenuhi', $keputusan ?? 'seleksi_dibatalkan', $adminId);

if ($keputusan === 'lolos' && $app['status_seleksi'] !== 'lolos') {
    email_lolos_wawancara($app, batch_by_id($pdo, $app['batch_id'] ? (int) $app['batch_id'] : null));
}

json_out(['success' => true, 'message' => $keputusan === 'lolos' ? 'Pelamar diloloskan ke wawancara.' : ($keputusan ? 'Pelamar ditandai tidak lolos ke wawancara.' : 'Keputusan seleksi dibatalkan.')]);
