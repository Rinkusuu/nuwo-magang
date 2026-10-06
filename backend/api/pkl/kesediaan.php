<?php
require '../../config.php';
require '../../app_helpers.php';

// Peserta yang diterima menyatakan bersedia atau tidak bersedia mengikuti PKL.
// Pilihan hanya bisa dibuat sekali; perubahan setelahnya melalui panitia.
$userId = require_user();
$pilihan = body()['kesediaan'] ?? '';
if (!in_array($pilihan, ['bersedia', 'tidak_bersedia'], true)) fail('Pilihan kesediaan tidak valid.');

$app = app_by_user($pdo, $userId);
if (!$app || $app['hasil_akhir'] !== 'diterima') fail('Konfirmasi kesediaan hanya untuk peserta yang diterima.', 403);
if ($app['kesediaan'] !== null) fail('Anda sudah menyatakan kesediaan sebelumnya.', 409);

$pdo->prepare("UPDATE applications SET kesediaan = ?, kesediaan_at = NOW() WHERE id = ?")->execute([$pilihan, $app['id']]);
log_status($pdo, (int) $app['id'], 'diterima', $pilihan, null, $pilihan === 'bersedia' ? 'Peserta bersedia mengikuti PKL' : 'Peserta mengundurkan diri');

json_out(['success' => true, 'message' => $pilihan === 'bersedia' ? 'Terima kasih, kesediaan Anda tercatat.' : 'Pengunduran diri Anda tercatat.']);
