<?php
require '../../config.php';
require '../../app_helpers.php';
require '../../mailer.php';

// Hasil akhir setelah wawancara: diterima, tidak diterima, atau dibatalkan (null)
$adminId = require_admin();
$data = body();
$id = (int) ($data['id'] ?? 0);
$hasil = $data['hasil_akhir'] ?? null;
if ($hasil !== null && !in_array($hasil, ['diterima', 'tidak_diterima'], true)) fail('Hasil tidak valid.');

$stmt = $pdo->prepare("SELECT status_seleksi, hasil_akhir, kesediaan, batch_id, nama, email, no_pendaftaran FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) fail('Data pelamar tidak ditemukan.', 404);
if ($app['status_seleksi'] !== 'lolos') fail('Pelamar belum diloloskan ke tahap wawancara.');
if ($app['kesediaan'] !== null && $hasil !== $app['hasil_akhir']) {
    fail('Peserta sudah menyatakan kesediaan, sehingga hasilnya tidak dapat diubah lagi.');
}

$pdo->prepare("UPDATE applications SET hasil_akhir = ?, decided_at = ? WHERE id = ?")
    ->execute([$hasil, $hasil ? date('Y-m-d H:i:s') : null, $id]);
log_status($pdo, $id, $app['hasil_akhir'] ?? 'dinilai', $hasil ?? 'dibatalkan', $adminId);

// Email hanya saat hasil ditetapkan atau berubah (bukan saat dibatalkan)
if ($hasil !== null && $hasil !== $app['hasil_akhir']) {
    email_hasil_akhir($app, $hasil, batch_by_id($pdo, $app['batch_id'] ? (int) $app['batch_id'] : null));
}

json_out(['success' => true, 'message' => 'Hasil seleksi disimpan.']);
