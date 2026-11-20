<?php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }

$email = strtolower(trim($argv[1] ?? ''));
$namaBaru = trim($argv[2] ?? '');
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Pakai: php backend/atur_password_admin.php email@admin [\"Nama untuk admin baru\"]\n");
    exit(1);
}

$lokal = is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];
$ambil = fn($k, $d) => $lokal[$k] ?? (getenv('MAGANG_' . $k) !== false ? getenv('MAGANG_' . $k) : $d);
$pdo = new PDO(
    sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $ambil('DB_HOST', 'localhost'), $ambil('DB_PORT', '3306'), $ambil('DB_NAME', 'magang_bi')),
    $ambil('DB_USER', 'root'), $ambil('DB_PASS', ''),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

function baca_tersembunyi(string $label): string {
    fwrite(STDOUT, $label);
    $tty = stream_isatty(STDIN);
    if ($tty) shell_exec('stty -echo');
    $v = rtrim((string) fgets(STDIN), "\r\n");
    if ($tty) { shell_exec('stty echo'); fwrite(STDOUT, "\n"); }
    return $v;
}

$q = $pdo->prepare("SELECT id, nama FROM admins WHERE email = ?");
$q->execute([$email]);
$admin = $q->fetch(PDO::FETCH_ASSOC);
if (!$admin && $namaBaru === '') {
    fwrite(STDERR, "Admin $email belum ada. Tambahkan nama sebagai argumen kedua untuk membuatnya.\n");
    exit(1);
}

$sandi = baca_tersembunyi('Kata sandi baru: ');
if (strlen($sandi) < 12) { fwrite(STDERR, "Kata sandi minimal 12 karakter.\n"); exit(1); }
if ($sandi !== baca_tersembunyi('Ulangi kata sandi: ')) { fwrite(STDERR, "Kata sandi tidak sama.\n"); exit(1); }

$hash = password_hash($sandi, PASSWORD_BCRYPT);
if ($admin) {
    $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([$hash, $admin['id']]);
    echo "Kata sandi {$admin['nama']} <$email> diperbarui.\n";
} else {
    $pdo->prepare("INSERT INTO admins (nama, email, password) VALUES (?, ?, ?)")->execute([$namaBaru, $email, $hash]);
    echo "Admin baru $namaBaru <$email> dibuat.\n";
}
