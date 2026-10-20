<?php
// Pembatasan percobaan untuk login dan permintaan atur ulang kata sandi.
// Dihitung per kunci (mis. "login:email") dan per alamat IP, dalam jendela waktu bergulir.

function ip_klien(): string {
    return substr($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0', 0, 45);
}

// Hentikan permintaan bila percobaan untuk kunci ini atau dari IP ini sudah melewati batas.
// Dengan $maksKunci > 0, $maks berlaku per kunci per IP dan $maksKunci untuk kunci itu dari semua IP:
// orang lain tidak bisa mengunci akun hanya dengan 5 kali salah sandi, tetapi tebakan dari banyak IP tetap dibatasi.
function cek_batas(PDO $pdo, string $kunci, int $maks, int $menit, int $maksIp = 0, int $maksKunci = 0): void {
    if (!BATAS_PERCOBAAN_AKTIF) return;
    $pdo->prepare("DELETE FROM batas_percobaan WHERE waktu < NOW() - INTERVAL 1 DAY")->execute();
    $hitung = function (string $where, array $param) use ($pdo, $menit): int {
        $q = $pdo->prepare("SELECT COUNT(*) FROM batas_percobaan WHERE $where AND waktu > NOW() - INTERVAL ? MINUTE");
        $q->execute([...$param, $menit]);
        return (int) $q->fetchColumn();
    };
    $lewat = $maksKunci > 0
        ? $hitung('kunci = ? AND ip = ?', [$kunci, ip_klien()]) >= $maks || $hitung('kunci = ?', [$kunci]) >= $maksKunci
        : $hitung('kunci = ?', [$kunci]) >= $maks;
    if (!$lewat && $maksIp > 0) $lewat = $hitung('ip = ?', [ip_klien()]) >= $maksIp;
    if ($lewat) fail("Terlalu banyak percobaan. Coba lagi dalam $menit menit.", 429);
}

function catat_percobaan(PDO $pdo, string $kunci): void {
    $pdo->prepare("INSERT INTO batas_percobaan (kunci, ip) VALUES (?, ?)")->execute([$kunci, ip_klien()]);
}

function hapus_percobaan(PDO $pdo, string $kunci): void {
    $pdo->prepare("DELETE FROM batas_percobaan WHERE kunci = ?")->execute([$kunci]);
}
