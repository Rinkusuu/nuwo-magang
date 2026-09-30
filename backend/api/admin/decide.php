<?php
require '../../config.php';
require '../../mailer.php';

$adminId = require_admin();
$data = body();
$id = (int) ($data['id'] ?? 0);
$hasil = $data['hasil_akhir'] ?? null;          // 'diterima' | 'cadangan' | 'tidak_diterima' | null (batalkan)
$penempatan = (int) ($data['bidang_penempatan_id'] ?? 0) ?: null;

if ($hasil !== null && !in_array($hasil, ['diterima', 'cadangan', 'tidak_diterima'], true)) fail('Hasil tidak valid.');
if ($hasil === 'diterima' && !$penempatan) fail('Pilih bidang penempatan untuk pelamar yang diterima.');

$stmt = $pdo->prepare("SELECT status_admin, hasil_akhir, bidang_penempatan_id, nama, email, no_pendaftaran FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) fail('Data pelamar tidak ditemukan.', 404);
if ($app['status_admin'] !== 'memenuhi') fail('Pelamar belum lolos verifikasi administrasi.');

$pdo->prepare("UPDATE applications SET hasil_akhir = ?, bidang_penempatan_id = ?, decided_at = ? WHERE id = ?")
    ->execute([$hasil, $hasil === 'diterima' ? $penempatan : null, $hasil ? date('Y-m-d H:i:s') : null, $id]);
log_status($pdo, $id, $app['hasil_akhir'] ?? 'dinilai', $hasil ?? 'dibatalkan', $adminId);

// Email hanya bila hasil ditetapkan atau berubah (bukan saat dibatalkan)
$berubah = $hasil !== $app['hasil_akhir'] || ($hasil === 'diterima' && (int) $app['bidang_penempatan_id'] !== (int) $penempatan);
if ($hasil !== null && $berubah) {
    $nama = null;
    if ($penempatan) {
        $b = $pdo->prepare("SELECT nama FROM bidang WHERE id = ?");
        $b->execute([$penempatan]);
        $nama = $b->fetchColumn() ?: null;
    }
    email_hasil_akhir($app, $hasil, $nama);
}

json_out(['success' => true, 'message' => 'Hasil seleksi disimpan.']);
