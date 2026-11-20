<?php
require '../../config.php';

$adminId = require_staf();
$data = body();
$id = (int) ($data['id'] ?? 0);

$stmt = $pdo->prepare("SELECT status_admin, status_seleksi FROM applications WHERE id = ?");
$stmt->execute([$id]);
$app = $stmt->fetch();
if (!$app) fail('Data pelamar tidak ditemukan.', 404);
if ($app['status_seleksi'] !== 'lolos') fail('Pelamar belum diloloskan ke tahap wawancara.');

$skor = [];
foreach (array_keys(BOBOT_ASPEK) as $i) {
    $s = $data['skor'][$i] ?? null;
    $skor[$i] = ($s !== null && $s !== '' && (int) $s >= 1 && (int) $s <= 5) ? (int) $s : null;
}
$catatanAspek = [];
foreach (array_keys(BOBOT_ASPEK) as $i) $catatanAspek[$i] = mb_substr(trim($data['catatan_aspek'][$i] ?? ''), 0, 500);

$nilai = hitung_nilai($skor);
if ($nilai === null) fail('Semua aspek wajib diberi skor.');

$rekomendasi = kategori_nilai($nilai, $skor[6]);

$pdo->prepare("
    INSERT INTO interview_assessments (application_id, admin_id, skor, catatan_aspek, rekomendasi, catatan, nilai_akhir, lengkap)
    VALUES (?, ?, ?, ?, ?, ?, ?, 1)
    ON DUPLICATE KEY UPDATE skor = VALUES(skor), catatan_aspek = VALUES(catatan_aspek), rekomendasi = VALUES(rekomendasi),
        catatan = VALUES(catatan), nilai_akhir = VALUES(nilai_akhir), lengkap = 1
")->execute([$id, $adminId, json_encode($skor), json_encode($catatanAspek), $rekomendasi, trim($data['catatan'] ?? '') ?: null, $nilai]);

log_status($pdo, $id, 'wawancara', 'dinilai', $adminId, "Nilai $nilai · $rekomendasi");

json_out(['success' => true, 'message' => 'Penilaian disimpan.', 'nilai_akhir' => $nilai]);
