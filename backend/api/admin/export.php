<?php
require '../../config.php';
require '../../app_helpers.php';

require_admin();

$rows = $pdo->query("
    SELECT a.*,
           (SELECT ROUND(AVG(nilai_akhir), 2) FROM interview_assessments i WHERE i.application_id = a.id AND i.lengkap = 1) AS nilai
    FROM applications a WHERE a.submitted_at IS NOT NULL ORDER BY a.no_pendaftaran
")->fetchAll();
$bidang = bidang_map($pdo);

$label = [
    'menunggu' => 'Menunggu', 'memenuhi' => 'Memenuhi', 'perbaikan' => 'Perlu perbaikan', 'tidak_memenuhi' => 'Tidak memenuhi',
    'diterima' => 'Diterima', 'cadangan' => 'Cadangan', 'tidak_diterima' => 'Tidak diterima',
];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="daftar-pelamar-' . date('Ymd') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
$sep = ';';                    // pemisah kolom Excel berbahasa Indonesia
fputcsv($out, ['No. Pendaftaran', 'Nama', 'Email', 'No. HP', 'Domisili', 'Perguruan Tinggi', 'Fakultas / Prodi', 'NIM',
    'IPK', 'Jenjang', 'Semester', 'Bidang Utama', 'Bidang Alternatif', 'Tanggal Mulai', 'Tanggal Selesai',
    'Administrasi', 'Jadwal Wawancara', 'Nilai Wawancara', 'Hasil', 'Penempatan', 'Tanggal Daftar'], $sep, '"', '');
foreach ($rows as $r) {
    fputcsv($out, [
        $r['no_pendaftaran'], $r['nama'], $r['email'], $r['no_wa'], $r['domisili'], $r['universitas'], $r['fakultas_prodi'],
        $r['nim'], str_replace('.', ',', (string) $r['ipk']), $r['jenjang'], $r['semester'],
        $bidang[$r['bidang_utama_id'] ?? '']['nama'] ?? '', $bidang[$r['bidang_alternatif_id'] ?? '']['nama'] ?? '',
        $r['tanggal_mulai'], $r['tanggal_selesai'], $label[$r['status_admin']] ?? $r['status_admin'],
        $r['jadwal_tanggal'] ? trim($r['jadwal_tanggal'] . ' ' . substr((string) $r['jadwal_jam'], 0, 5)) : '',
        $r['nilai'] !== null ? str_replace('.', ',', (string) $r['nilai']) : '',
        $label[$r['hasil_akhir'] ?? ''] ?? '', $bidang[$r['bidang_penempatan_id'] ?? '']['nama'] ?? '', $r['submitted_at'],
    ], $sep, '"', '');
}
fclose($out);
