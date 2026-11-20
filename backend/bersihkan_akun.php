<?php
// Cron harian. --uji untuk melihat daftar tanpa menghapus.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

require __DIR__ . '/pembersihan_helpers.php';
date_default_timezone_set('Asia/Jakarta');

$lokal = is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];
$ambil = fn($k, $d) => $lokal[$k] ?? (getenv('MAGANG_' . $k) !== false ? getenv('MAGANG_' . $k) : $d);
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $ambil('DB_HOST', 'localhost'), $ambil('DB_PORT', '3306'), $ambil('DB_NAME', 'magang_bi')),
    $ambil('DB_USER', 'root'), $ambil('DB_PASS', ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
);
$pdo->exec("SET time_zone = '+07:00'");

$uji = in_array('--uji', $argv, true);
$daftar = akun_lewat_tenggat($pdo);
$waktu = date('Y-m-d H:i');

if ($uji) {
    if (!$daftar) { echo "[$waktu] Tidak ada akun yang lewat tenggat.\n"; exit(0); }
    $q = $pdo->prepare("SELECT email FROM users WHERE id = ?");
    foreach ($daftar as $id => $kategori) {
        $q->execute([$id]);
        echo "$kategori\t#$id\t" . $q->fetchColumn() . "\n";
    }
    echo count($daftar) . " akun akan dihapus (mode uji, tidak ada yang dihapus).\n";
    exit(0);
}

$jumlah = [];
$gagal = 0;
foreach ($daftar as $id => $kategori) {
    try {
        hapus_akun_pelamar($pdo, $id, __DIR__ . '/uploads/');
        $jumlah[$kategori] = ($jumlah[$kategori] ?? 0) + 1;
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $gagal++;
        fwrite(STDERR, "[$waktu] Gagal menghapus akun #$id: " . $e->getMessage() . "\n");
    }
}
$catat = $pdo->prepare("INSERT INTO pembersihan_log (kategori, jumlah) VALUES (?, ?)");
foreach ($jumlah as $kategori => $n) $catat->execute([$kategori, $n]);

echo "[$waktu] " . array_sum($jumlah) . " akun dihapus" . ($jumlah ? ' (' . implode(', ', array_map(fn($k, $n) => "$k: $n", array_keys($jumlah), $jumlah)) . ')' : '') . ($gagal ? ", $gagal gagal" : '') . ".\n";
exit($gagal ? 1 : 0);
