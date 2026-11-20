<?php

$lokal = is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];
function cfg(array $lokal, string $kunci, $bawaan = null) {
    $env = getenv('MAGANG_' . $kunci);
    if ($env !== false) return $env;
    return array_key_exists($kunci, $lokal) ? $lokal[$kunci] : $bawaan;
}

define('APP_URL', rtrim((string) cfg($lokal, 'APP_URL', ''), '/'));
define('APP_DEBUG', (bool) cfg($lokal, 'DEBUG', false));
define('BATAS_PERCOBAAN_AKTIF', (bool) cfg($lokal, 'BATAS_PERCOBAAN', true));

date_default_timezone_set('Asia/Jakarta');

// galat dicetak akan merusak JSON dan membocorkan detail server
ini_set('display_errors', APP_DEBUG ? '1' : '0');
ini_set('log_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            cfg($lokal, 'DB_HOST', 'localhost'), cfg($lokal, 'DB_PORT', '3306'), cfg($lokal, 'DB_NAME', 'magang_bi')),
        cfg($lokal, 'DB_USER', 'root'),
        cfg($lokal, 'DB_PASS', ''),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
    $pdo->exec("SET time_zone = '+07:00'");
} catch (PDOException $e) {
    error_log('[magang] Koneksi database gagal: ' . $e->getMessage());
    http_response_code(503);
    die(json_encode(['success' => false, 'message' => 'Layanan sedang tidak tersedia. Coba beberapa saat lagi.']));
}

set_exception_handler(function (Throwable $e) {
    error_log('[magang] ' . $e);
    if (!headers_sent()) http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan di server. Coba beberapa saat lagi.']);
});

$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
session_name('magang_sesi');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'secure' => $https,
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', '1');
ini_set('session.gc_maxlifetime', '7200');
session_start();

if (isset($_SESSION['aktif_terakhir']) && time() - $_SESSION['aktif_terakhir'] > 7200) {
    session_unset();
    session_regenerate_id(true);
}
$_SESSION['aktif_terakhir'] = time();

function mulai_sesi_baru(): void {
    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'HEAD', 'OPTIONS'], true)) {
    $kiriman = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (empty($_SESSION['csrf']) || !is_string($kiriman) || !hash_equals($_SESSION['csrf'], $kiriman)) {
        http_response_code(419);
        die(json_encode(['success' => false, 'message' => 'Sesi halaman kedaluwarsa. Muat ulang halaman lalu coba lagi.', 'csrf' => true]));
    }
}

define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 2 * 1024 * 1024);

const JENIS_BERKAS = [
    'cv'         => 'Curriculum Vitae',
    'motivation' => 'Motivation Letter',
    'pengantar'  => 'Surat Pengantar',
    'transkrip'  => 'Transkrip Nilai',
    'ktm'        => 'Kartu Tanda Mahasiswa (KTM)',
];

const BOBOT_ASPEK = [15, 15, 15, 15, 15, 10, 15];

function json_out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function fail(string $message, int $code = 400): void {
    json_out(['success' => false, 'message' => $message], $code);
}

function body(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

// sidik berubah saat kata sandi diganti, sehingga sesi lain ikut berakhir
function sidik_sandi(string $hash): string {
    return hash('sha256', $hash);
}

function pengguna_aktif(): ?int {
    static $id = false;
    if ($id !== false) return $id;
    $id = null;
    if (isset($_SESSION['user_id'])) {
        $q = $GLOBALS['pdo']->prepare("SELECT password FROM users WHERE id = ?");
        $q->execute([$_SESSION['user_id']]);
        $hash = $q->fetchColumn();
        if ($hash !== false && !isset($_SESSION['sidik_user'])) $_SESSION['sidik_user'] = sidik_sandi($hash);
        if ($hash !== false && hash_equals($_SESSION['sidik_user'], sidik_sandi($hash))) {
            $id = (int) $_SESSION['user_id'];
        } else {
            unset($_SESSION['user_id'], $_SESSION['user_nama'], $_SESSION['user_email'], $_SESSION['sidik_user']);
        }
    }
    return $id;
}

function require_user(): int {
    $id = pengguna_aktif();
    if ($id === null) fail('Anda harus masuk terlebih dahulu.', 401);
    return $id;
}

// cek ke database tiap permintaan: akun yang dinonaktifkan langsung kehilangan akses
function staf_aktif(): ?array {
    static $staf = false;
    if ($staf !== false) return $staf;
    $staf = null;
    if (isset($_SESSION['admin_id'])) {
        $q = $GLOBALS['pdo']->prepare("SELECT id, nama, email, peran, password FROM admins WHERE id = ? AND aktif = 1");
        $q->execute([$_SESSION['admin_id']]);
        $staf = $q->fetch() ?: null;
        if ($staf && !isset($_SESSION['sidik_admin'])) $_SESSION['sidik_admin'] = sidik_sandi($staf['password']);
        if ($staf && !hash_equals($_SESSION['sidik_admin'], sidik_sandi($staf['password']))) $staf = null;
        if ($staf) unset($staf['password']);
        else unset($_SESSION['admin_id'], $_SESSION['admin_nama'], $_SESSION['admin_email'], $_SESSION['sidik_admin']);
    }
    return $staf;
}

function require_staf(): int {
    $s = staf_aktif();
    if (!$s) fail('Silakan masuk ke backoffice terlebih dahulu.', 401);
    return (int) $s['id'];
}

function require_admin(): int {
    $s = staf_aktif();
    if (!$s) fail('Silakan masuk ke backoffice terlebih dahulu.', 401);
    if ($s['peran'] !== 'admin') fail('Halaman ini hanya untuk admin.', 403);
    return (int) $s['id'];
}

function staf_pewawancara(): bool {
    return (staf_aktif()['peran'] ?? '') === 'pewawancara';
}

function get_pengaturan(PDO $pdo): array {
    return $pdo->query("SELECT * FROM pengaturan WHERE id = 1")->fetch() ?: [];
}

function log_status(PDO $pdo, int $appId, ?string $from, string $to, ?int $adminId, ?string $note = null): void {
    $pdo->prepare("INSERT INTO application_status_log (application_id, status_from, status_to, changed_by, note) VALUES (?, ?, ?, ?, ?)")
        ->execute([$appId, $from, $to, $adminId, $note]);
}

function hitung_nilai(array $skor): ?float {
    $total = 0;
    foreach (BOBOT_ASPEK as $i => $bobot) {
        if (empty($skor[$i])) return null;
        $total += $skor[$i] * $bobot / 100;
    }
    return round($total, 2);
}

function kategori_nilai(float $nilai, ?int $skorSikap): string {
    if ($skorSikap === 1) return 'tidak';
    if ($nilai >= 4.20) return 'sangat';
    if ($nilai >= 3.00) return 'direkomendasikan';
    return 'tidak';
}

function tambah_hari_kerja(int $n): string {
    $d = new DateTime('today');
    while ($n > 0) {
        $d->modify('+1 day');
        if ((int) $d->format('N') <= 5) $n--;
    }
    return $d->format('Y-m-d');
}

$GLOBALS['SMTP'] = [
    'host'   => cfg($lokal, 'SMTP_HOST', ''),
    'port'   => (int) cfg($lokal, 'SMTP_PORT', 587),
    'secure' => cfg($lokal, 'SMTP_SECURE', 'tls'),
    'user'   => cfg($lokal, 'SMTP_USER', ''),
    'pass'   => cfg($lokal, 'SMTP_PASS', ''),
    'from'   => cfg($lokal, 'SMTP_FROM', ''),
    'nama'   => cfg($lokal, 'SMTP_FROM_NAME', 'Nuwo Magang KPwBI Provinsi Lampung'),
];
unset($lokal);
