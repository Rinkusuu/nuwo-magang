<?php

const HARI_SIMPAN_DITOLAK = 7;

const KATEGORI_DITOLAK = [
    'tidak_memenuhi_administrasi' => ["status_admin = 'tidak_memenuhi'", 'verified_at'],
    'tidak_lolos_wawancara'       => ["status_seleksi = 'tidak_lolos'", 'seleksi_at'],
    'tidak_diterima'              => ["hasil_akhir = 'tidak_diterima'", 'decided_at'],
    'mengundurkan_diri'           => ["kesediaan = 'tidak_bersedia'", 'kesediaan_at'],
];

function tanggal_hapus_akun(array $app): ?string {
    $cocok = [
        'verified_at' => $app['status_admin'] === 'tidak_memenuhi',
        'seleksi_at' => $app['status_seleksi'] === 'tidak_lolos',
        'decided_at' => $app['hasil_akhir'] === 'tidak_diterima',
        'kesediaan_at' => $app['kesediaan'] === 'tidak_bersedia',
    ];
    foreach ($cocok as $kolom => $ya) {
        if ($ya && !empty($app[$kolom])) {
            return date('Y-m-d', strtotime(substr($app[$kolom], 0, 10) . ' +' . HARI_SIMPAN_DITOLAK . ' days'));
        }
    }
    return null;
}

function akun_lewat_tenggat(PDO $pdo): array {
    $hasil = [];
    foreach (KATEGORI_DITOLAK as $kategori => [$kondisi, $kolom]) {
        $q = $pdo->query("SELECT user_id FROM applications
            WHERE $kondisi AND $kolom IS NOT NULL AND DATE($kolom) <= CURDATE() - INTERVAL " . HARI_SIMPAN_DITOLAK . " DAY");
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $id) $hasil[(int) $id] ??= $kategori;
    }

    $tutup = $pdo->query("SELECT MAX(daftar_tutup) FROM batch WHERE daftar_tutup <= CURDATE() - INTERVAL " . HARI_SIMPAN_DITOLAK . " DAY")->fetchColumn();
    if ($tutup) {
        $q = $pdo->prepare("SELECT u.id FROM users u LEFT JOIN applications a ON a.user_id = u.id
            WHERE (a.id IS NULL OR a.submitted_at IS NULL) AND DATE(u.created_at) <= ?");
        $q->execute([$tutup]);
        foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $id) $hasil[(int) $id] ??= 'tanpa_pendaftaran';
    }
    return $hasil;
}

function hapus_akun_pelamar(PDO $pdo, int $userId, string $dirUpload): void {
    $q = $pdo->prepare("
        SELECT f.stored_name FROM application_files f JOIN applications a ON a.id = f.application_id WHERE a.user_id = ?
        UNION ALL SELECT b.stored_name FROM berkas_pkl b JOIN applications a ON a.id = b.application_id WHERE a.user_id = ?
        UNION ALL SELECT CONCAT('absen/', ab.foto) FROM absen ab JOIN applications a ON a.id = ab.application_id WHERE a.user_id = ? AND ab.foto IS NOT NULL
        UNION ALL SELECT CONCAT('profil/', foto) FROM users WHERE id = ? AND foto IS NOT NULL
    ");
    $q->execute([$userId, $userId, $userId, $userId]);
    $berkas = $q->fetchAll(PDO::FETCH_COLUMN);

    $pdo->beginTransaction();
    $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
    $pdo->commit();

    foreach ($berkas as $nama) {
        // contoh.pdf dipakai bersama oleh contoh_data.sql
        if ($nama === 'contoh.pdf' || str_contains($nama, '..')) continue;
        if (is_file($dirUpload . $nama)) unlink($dirUpload . $nama);
    }
}
