<?php
require '../../config.php';
require '../../app_helpers.php';

$adminId = require_staf();

$q = $pdo->prepare("
    SELECT a.id, a.no_pendaftaran, a.nama, a.email, a.universitas, a.fakultas_prodi, a.nim, a.ipk,
           a.jenjang, a.semester, a.batch_id, b.nama AS batch, a.status_admin,
           a.syarat_s1, a.syarat_semester, a.syarat_aktif, a.status_seleksi,
           a.hasil_akhir, a.kesediaan, a.submitted_at
    FROM applications a LEFT JOIN batch b ON b.id = a.batch_id
    WHERE a.submitted_at IS NOT NULL
      AND (? = 0 OR a.status_seleksi = 'lolos')   -- pewawancara hanya melihat pelamar yang diundang wawancara
    ORDER BY a.submitted_at DESC
");
$q->execute([staf_pewawancara() ? 1 : 0]);
$rows = $q->fetchAll();

$nilai = [];
foreach ($pdo->query("
    SELECT application_id, AVG(nilai_akhir) AS rata, COUNT(*) AS jumlah,
           MIN(CAST(JSON_EXTRACT(skor, '$[6]') AS UNSIGNED)) AS min_sikap
    FROM interview_assessments WHERE lengkap = 1 GROUP BY application_id
") as $n) {
    $nilai[$n['application_id']] = $n;
}
$mine = $pdo->prepare("SELECT application_id FROM interview_assessments WHERE admin_id = ? AND lengkap = 1");
$mine->execute([$adminId]);
$sudahSaya = array_flip($mine->fetchAll(PDO::FETCH_COLUMN));

foreach ($rows as &$r) {
    $n = $nilai[$r['id']] ?? null;
    $r['nilai'] = $n ? round((float) $n['rata'], 2) : null;
    $r['jumlah_penilai'] = $n ? (int) $n['jumlah'] : 0;
    $r['kategori'] = $n ? kategori_nilai((float) $n['rata'], (int) $n['min_sikap']) : null;
    $r['sudah_saya_nilai'] = isset($sudahSaya[$r['id']]);

    $r['alasan'] = null;
    if ($r['status_admin'] === 'tidak_memenuhi') {
        if ($r['syarat_s1'] === 'tidak') $r['alasan'] = 'Bukan jenjang S1';
        elseif ($r['syarat_semester'] === 'tidak') $r['alasan'] = 'Belum semester 4';
        elseif ($r['syarat_aktif'] === 'tidak') $r['alasan'] = 'Bukan mahasiswa aktif';
        else $r['alasan'] = 'Berkas tidak sesuai';
    }
}
unset($r);

$batch = $pdo->query("SELECT id, nama, daftar_buka, daftar_tutup, mulai, selesai, wawancara_tanggal, wawancara_jam, link_zoom FROM batch ORDER BY daftar_buka DESC, id DESC")->fetchAll();

json_out([
    'success' => true,
    'data' => $rows,
    'batch' => $batch,
]);
