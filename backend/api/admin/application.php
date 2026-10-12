<?php
require '../../config.php';
require '../../app_helpers.php';

$adminId = require_staf();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM applications WHERE id = ? AND submitted_at IS NOT NULL");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app || (staf_pewawancara() && $app['status_seleksi'] !== 'lolos')) fail('Data pelamar tidak ditemukan.', 404);

$app['batch'] = batch_by_id($pdo, $app['batch_id'] ? (int) $app['batch_id'] : null);

$stmt = $pdo->prepare("
    SELECT ia.admin_id, ad.nama AS pewawancara, ia.skor, ia.catatan_aspek, ia.rekomendasi, ia.catatan,
           ia.nilai_akhir, ia.lengkap, ia.updated_at
    FROM interview_assessments ia JOIN admins ad ON ad.id = ia.admin_id
    WHERE ia.application_id = ? ORDER BY ia.id
");
$stmt->execute([$id]);
$penilaian = [];
foreach ($stmt->fetchAll() as $p) {
    $p['skor'] = json_decode($p['skor'], true);
    $p['catatan_aspek'] = json_decode($p['catatan_aspek'] ?? '[]', true);
    $p['milik_saya'] = (int) $p['admin_id'] === $adminId;
    $penilaian[] = $p;
}

json_out([
    'success' => true,
    'application' => $app,
    'files' => app_files($pdo, $id),
    'penilaian' => $penilaian,
    'admin' => ['id' => $adminId, 'nama' => $_SESSION['admin_nama']],
    'periode' => get_periode($pdo),
]);
