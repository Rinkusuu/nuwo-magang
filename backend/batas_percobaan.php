<?php
// Pembatasan percobaan untuk login dan permintaan atur ulang kata sandi.
// Dihitung per kunci (mis. "login:email") dan per alamat IP, dalam jendela waktu bergulir.

function ip_klien(): string {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

// Hentikan permintaan bila percobaan untuk kunci ini atau dari IP ini sudah melewati batas
function cek_batas(PDO $pdo, string $kunci, int $maks, int $menit, int $maksIp = 0): void {
    if (!BATAS_PERCOBAAN_AKTIF) return;
    $pdo->prepare("DELETE FROM batas_percobaan WHERE waktu < NOW() - INTERVAL 1 DAY")->execute();
    $q = $pdo->prepare("SELECT COUNT(*) FROM batas_percobaan WHERE kunci = ? AND waktu > NOW() - INTERVAL ? MINUTE");
    $q->execute([$kunci, $menit]);
    $lewat = (int) $q->fetchColumn() >= $maks;
    if (!$lewat && $maksIp > 0) {
        $q = $pdo->prepare("SELECT COUNT(*) FROM batas_percobaan WHERE ip = ? AND waktu > NOW() - INTERVAL ? MINUTE");
        $q->execute([ip_klien(), $menit]);
        $lewat = (int) $q->fetchColumn() >= $maksIp;
    }
    if ($lewat) fail("Terlalu banyak percobaan. Coba lagi dalam $menit menit.", 429);
}

function catat_percobaan(PDO $pdo, string $kunci): void {
    $pdo->prepare("INSERT INTO batas_percobaan (kunci, ip) VALUES (?, ?)")->execute([$kunci, ip_klien()]);
}

function hapus_percobaan(PDO $pdo, string $kunci): void {
    $pdo->prepare("DELETE FROM batas_percobaan WHERE kunci = ?")->execute([$kunci]);
}
