<?php
require __DIR__ . '/../../../backend/config.php';
require __DIR__ . '/../../../backend/app_helpers.php';

require_admin();

$rows = $pdo->query("
    SELECT a.*, b.nama AS batch,
           (SELECT ROUND(AVG(nilai_akhir), 2) FROM interview_assessments i WHERE i.application_id = a.id AND i.lengkap = 1) AS nilai
    FROM applications a LEFT JOIN batch b ON b.id = a.batch_id
    WHERE a.submitted_at IS NOT NULL ORDER BY b.daftar_buka, a.no_pendaftaran
")->fetchAll();

$label = [
    'menunggu' => 'Menunggu', 'memenuhi' => 'Memenuhi', 'perbaikan' => 'Perlu perbaikan', 'tidak_memenuhi' => 'Tidak memenuhi',
    'lolos' => 'Lolos ke wawancara', 'tidak_lolos' => 'Tidak lolos ke wawancara',
    'diterima' => 'Diterima', 'tidak_diterima' => 'Tidak diterima',
    'bersedia' => 'Bersedia', 'tidak_bersedia' => 'Mengundurkan diri',
];

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="daftar-pelamar-' . date('Ymd') . '.csv"');

$out = fopen('php://output', 'w');
fwrite($out, "\xEF\xBB\xBF"); // BOM agar Excel membaca UTF-8
$sep = ';';                    // Excel locale Indonesia memakai titik koma

// cegah CSV injection: isian berawalan = + - @ dibaca Excel sebagai rumus
$sel = fn($v) => is_string($v) && $v !== '' && strpbrk($v[0], "=+-@\t\r") !== false ? "'" . $v : $v;
fputcsv($out, ['No. Pendaftaran', 'Batch', 'Nama', 'Email', 'No. HP', 'Domisili', 'Perguruan Tinggi', 'Fakultas / Prodi', 'NPM',
    'IPK', 'Jenjang', 'Semester', 'Administrasi', 'Seleksi Wawancara', 'Nilai Wawancara', 'Hasil', 'Kesediaan', 'Tanggal Daftar'], $sep, '"', '');
foreach ($rows as $r) {
    fputcsv($out, array_map($sel, [
        $r['no_pendaftaran'], $r['batch'] ?? '', $r['nama'], $r['email'], $r['no_wa'], $r['domisili'], $r['universitas'], $r['fakultas_prodi'],
        $r['nim'], str_replace('.', ',', (string) $r['ipk']), $r['jenjang'], $r['semester'],
        $label[$r['status_admin']] ?? $r['status_admin'], $label[$r['status_seleksi'] ?? ''] ?? '',
        $r['nilai'] !== null ? str_replace('.', ',', (string) $r['nilai']) : '',
        $label[$r['hasil_akhir'] ?? ''] ?? '', $label[$r['kesediaan'] ?? ''] ?? '', $r['submitted_at'],
    ]), $sep, '"', '');
}
fclose($out);
