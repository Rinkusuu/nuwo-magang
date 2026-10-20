<?php
require '../../config.php';
require '../../pembersihan_helpers.php';

require_admin();
$id = (int) (body()['id'] ?? 0);
if (!$id) fail('ID pelamar tidak valid.');

$stmt = $pdo->prepare("SELECT id, nama, user_id FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) fail('Data pelamar tidak ditemukan.', 404);

try {
    // Menghapus akun ikut menghapus pendaftaran, berkas, penilaian, absen, logbook, dan log (ON DELETE CASCADE);
    // berkas di disk (dokumen pendaftaran, laporan akhir, foto absen) dihapus sesudahnya
    hapus_akun_pelamar($pdo, (int) $app['user_id'], UPLOAD_DIR);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log('[magang] Hapus pelamar ' . $id . ' gagal: ' . $e->getMessage());
    fail('Gagal menghapus data pelamar. Coba lagi.', 500);
}

json_out(['success' => true, 'message' => "Data pelamar '{$app['nama']}' beserta akunnya berhasil dihapus."]);
