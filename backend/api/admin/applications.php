<?php
require '../../config.php';
require '../../app_helpers.php';

$adminId = require_admin();

// Hanya pendaftaran yang sudah dikirim (draf tidak tampil)
$rows = $pdo->query("
    SELECT a.id, a.no_pendaftaran, a.nama, a.email, a.universitas, a.fakultas_prodi, a.nim, a.ipk,
           a.jenjang, a.semester, a.bidang_utama_id, a.bidang_penempatan_id, a.status_admin,
           a.syarat_s1, a.syarat_semester, a.syarat_aktif, a.jadwal_tanggal, a.jadwal_jam, a.jadwal_lokasi,
           a.hasil_akhir, a.submitted_at
    FROM applications a
    WHERE a.submitted_at IS NOT NULL
    ORDER BY a.submitted_at DESC
")->fetchAll();

// Rekap penilaian wawancara per pelamar (hanya penilaian lengkap)
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

$bidang = bidang_map($pdo);
foreach ($rows as &$r) {
    $r['bidang_utama'] = $bidang[$r['bidang_utama_id'] ?? '']['singkat'] ?? '-';
    $r['bidang_penempatan'] = $bidang[$r['bidang_penempatan_id'] ?? '']['singkat'] ?? null;
    $n = $nilai[$r['id']] ?? null;
    $r['nilai'] = $n ? round((float) $n['rata'], 2) : null;
    $r['jumlah_penilai'] = $n ? (int) $n['jumlah'] : 0;
    $r['kategori'] = $n ? kategori_nilai((float) $n['rata'], (int) $n['min_sikap']) : null;
    $r['sudah_saya_nilai'] = isset($sudahSaya[$r['id']]);

    // Alasan singkat bila tidak memenuhi syarat
    $r['alasan'] = null;
    if ($r['status_admin'] === 'tidak_memenuhi') {
        if ($r['syarat_s1'] === 'tidak') $r['alasan'] = 'Bukan jenjang S1';
        elseif ($r['syarat_semester'] === 'tidak') $r['alasan'] = 'Belum semester 4';
        elseif ($r['syarat_aktif'] === 'tidak') $r['alasan'] = 'Bukan mahasiswa aktif';
        else $r['alasan'] = 'Berkas tidak sesuai';
    }
}
unset($r);

$periode = get_periode($pdo);

json_out([
    'success' => true,
    'data' => $rows,
    'periode' => $periode,
    'bidang' => array_values($bidang),
]);
