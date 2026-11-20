<?php

require_once __DIR__ . '/xlsx_sederhana.php';
require_once __DIR__ . '/absen_helpers.php';

const BARIS_BLOK_MIN = 5;

function tanggal_panjang(string $ymd, bool $denganTahun = true): string {
    $bulan = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    [$y, $m, $d] = array_map('intval', explode('-', substr($ymd, 0, 10)));
    return "$d {$bulan[$m - 1]}" . ($denganTahun ? " $y" : '');
}

function periode_magang(string $mulai, string $selesai): string {
    return tanggal_panjang($mulai, substr($mulai, 0, 4) !== substr($selesai, 0, 4)) . ' s/d ' . tanggal_panjang($selesai);
}

function daftar_hari_kerja(string $mulai, string $sampai): array {
    $hasil = [];
    for ($t = strtotime($mulai); $t <= strtotime($sampai); $t = strtotime('+1 day', $t)) {
        $tgl = date('Y-m-d', $t);
        if (hari_kerja($tgl)) $hasil[] = $tgl;
    }
    return $hasil;
}

function buat_logbook_excel(PDO $pdo, array $app, array $batch, string $path): void {
    $sampai = min(date('Y-m-d'), $batch['selesai']);
    $hariKerja = $sampai >= $batch['mulai'] ? daftar_hari_kerja($batch['mulai'], $sampai) : [];

    $q = $pdo->prepare("SELECT tanggal, isi FROM logbook WHERE application_id = ?");
    $q->execute([$app['id']]);
    $logbook = $q->fetchAll(PDO::FETCH_KEY_PAIR);

    $q = $pdo->prepare("SELECT tanggal, jenis, waktu FROM absen WHERE application_id = ?");
    $q->execute([$app['id']]);
    $jam = [];
    foreach ($q->fetchAll() as $a) $jam[$a['tanggal']][$a['jenis']] = str_replace(':', '.', substr($a['waktu'], 11, 5));

    $x = new XlsxSederhana('Logbook');
    $x->lebar(['A' => 9.83, 'B' => 1.5, 'C' => 21.16, 'E' => 15.83, 'F' => 1.5, 'G' => 14.16, 'I' => 95.5]);
    $tipis = 'thin';

    $bagian = array_map('trim', explode('/', (string) $app['fakultas_prodi'], 2));
    [$fakultas, $jurusan] = count($bagian) === 2 ? $bagian : ['', $bagian[0]];
    $kiri = [['Nama', $app['nama']], ['NPM', $app['nim']], ['Jurusan', $jurusan], ['Fakultas', $fakultas], ['Universitas', $app['universitas']]];
    $kanan = [['Email', $app['email']], ['Email Pembimbing', ''], ['Periode Magang', periode_magang($batch['mulai'], $batch['selesai'])], ['Unit/Bagian', ''], ['', '']];
    foreach ($kiri as $i => [$label, $nilai]) {
        $b = $i + 2;
        $atas = $b === 2 ? ['t' => $tipis] : [];
        $bawah = $b === 6 ? ['b' => $tipis] : [];
        $x->kotak("A$b", $label, [], ['l' => $tipis] + $atas + $bawah);
        $x->kotak("B$b", ':', ['rata' => 'center'], $atas + $bawah);
        $x->kotak("C$b:D$b", (string) ($nilai ?? ''), ['rata' => 'left'], $atas + $bawah);
        [$labelKanan, $nilaiKanan] = $kanan[$i];
        $x->kotak("E$b", $labelKanan, [], $atas + $bawah);
        $x->kotak("F$b", $labelKanan !== '' ? ':' : '', ['rata' => 'center'], $atas + $bawah);
        $x->kotak("G$b:I$b", (string) $nilaiKanan, ['rata' => 'left'], ['r' => $tipis] + $atas + $bawah);
    }

    $x->kotak('A8:I8', 'LAPORAN HARIAN (LOGBOOK) MAGANG KANTOR PERWAKILAN BANK INDONESIA PROVINSI LAMPUNG',
        ['tebal' => true, 'ukuran' => 10, 'rata' => 'center', 'tegak' => 'center']);

    $kepala = ['tebal' => true, 'isi' => 'D8D8D8', 'rata' => 'center', 'tegak' => 'center'];
    $semua = ['l' => $tipis, 'r' => $tipis, 't' => $tipis, 'b' => $tipis];
    $x->kotak('A9', 'No', $kepala, $semua);
    $x->kotak('B9:C9', 'Hari/ Tanggal', $kepala, $semua);
    $x->kotak('D9:I9', 'Kegiatan', $kepala, $semua);

    $hariNama = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => "Jum'at"];
    $baris = 10;
    foreach ($hariKerja as $no => $tgl) {
        $kegiatan = isset($logbook[$tgl]) ? explode("\n", $logbook[$tgl]) : [];
        $n = max(BARIS_BLOK_MIN, count($kegiatan));
        $akhir = $baris + $n - 1;

        $x->kotak("A$baris:A$akhir", $no + 1, ['rata' => 'center', 'tegak' => 'center'], $semua);
        $x->kotak("B$baris:C" . ($akhir - 2), $hariNama[(int) date('N', strtotime($tgl))] . ', ' . tanggal_panjang($tgl),
            ['rata' => 'left', 'tegak' => 'top'], ['l' => $tipis, 'r' => $tipis, 't' => $tipis]);
        $x->kotak('B' . ($akhir - 1) . ':C' . ($akhir - 1), 'Jam masuk: ' . ($jam[$tgl]['datang'] ?? '-'), ['rata' => 'left'], ['l' => $tipis, 'r' => $tipis]);
        $x->kotak("B$akhir:C$akhir", 'Jam keluar: ' . ($jam[$tgl]['pulang'] ?? '-'), ['rata' => 'left'], ['l' => $tipis, 'r' => $tipis, 'b' => $tipis]);

        for ($r = $baris; $r <= $akhir; $r++) {
            $garis = ['l' => $tipis, 'r' => $tipis, 't' => $r === $baris ? $tipis : 'hair', 'b' => $r === $akhir ? $tipis : 'hair'];
            $x->kotak("D$r:I$r", $kegiatan[$r - $baris] ?? '', ['rata' => 'left', 'tegak' => 'center', 'bungkus' => true], $garis);
        }
        $baris = $akhir + 1;
    }

    $x->simpan($path);
}
