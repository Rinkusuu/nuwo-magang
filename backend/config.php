<?php
// ==========================================================
// Konfigurasi bersama seluruh endpoint API.
//
// Nilai rahasia (kata sandi database, SMTP) TIDAK ditulis di sini.
// Isi di backend/config.local.php (salin dari config.local.example.php,
// berkas itu diabaikan git) atau lewat variabel lingkungan server.
// ==========================================================

$lokal = is_file(__DIR__ . '/config.local.php') ? require __DIR__ . '/config.local.php' : [];
function cfg(array $lokal, string $kunci, $bawaan = null) {
    if (array_key_exists($kunci, $lokal)) return $lokal[$kunci];
    $env = getenv('MAGANG_' . $kunci);
    return $env !== false ? $env : $bawaan;
}

define('APP_URL', rtrim((string) cfg($lokal, 'APP_URL', ''), '/'));   // mis. https://magang.contoh.go.id
define('APP_DEBUG', (bool) cfg($lokal, 'DEBUG', false));
// Batas percobaan login dan sejenisnya; hanya boleh dimatikan di laptop untuk demo
define('BATAS_PERCOBAAN_AKTIF', (bool) cfg($lokal, 'BATAS_PERCOBAAN', true));

// Seluruh tanggal (periode pendaftaran, batas perbaikan) dihitung dalam WIB
date_default_timezone_set('Asia/Jakarta');

// Galat PHP tidak pernah dicetak ke respons (akan merusak JSON dan membocorkan detail server)
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

// Galat tak terduga: catat di log server, kirim pesan umum ke pengguna
set_exception_handler(function (Throwable $e) {
    error_log('[magang] ' . $e);
    if (!headers_sent()) http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Terjadi kesalahan di server. Coba beberapa saat lagi.']);
});

// ---------- Sesi ----------
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

// Sesi menganggur lebih dari 2 jam dianggap berakhir
if (isset($_SESSION['aktif_terakhir']) && time() - $_SESSION['aktif_terakhir'] > 7200) {
    session_unset();
    session_regenerate_id(true);
}
$_SESSION['aktif_terakhir'] = time();

// Dipanggil setiap kali seseorang berhasil masuk, mencegah session fixation
function mulai_sesi_baru(): void {
    session_regenerate_id(true);
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
}

// ---------- CSRF ----------
// Semua permintaan yang mengubah data wajib membawa header X-CSRF-Token yang
// cocok dengan token di sesi. common.js mengambil token dari api/auth/csrf.php.
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

// Lima berkas administrasi wajib
const JENIS_BERKAS = [
    'cv'         => 'Curriculum Vitae',
    'motivation' => 'Motivation Letter',
    'pengantar'  => 'Surat Pengantar',
    'transkrip'  => 'Transkrip Nilai',
    'pendukung'  => 'KTP, NPWP, Buku Tabungan',
];

// Bobot tujuh aspek penilaian wawancara (%)
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

function require_user(): int {
    if (!isset($_SESSION['user_id'])) fail('Anda harus masuk terlebih dahulu.', 401);
    return (int) $_SESSION['user_id'];
}

function require_admin(): int {
    if (!isset($_SESSION['admin_id'])) fail('Akses admin diperlukan.', 401);
    return (int) $_SESSION['admin_id'];
}

function get_periode(PDO $pdo): array {
    return $pdo->query("SELECT * FROM periode WHERE id = 1")->fetch() ?: [];
}

function log_status(PDO $pdo, int $appId, ?string $from, string $to, ?int $adminId, ?string $note = null): void {
    $pdo->prepare("INSERT INTO application_status_log (application_id, status_from, status_to, changed_by, note) VALUES (?, ?, ?, ?, ?)")
        ->execute([$appId, $from, $to, $adminId, $note]);
}

// Nilai akhir = Σ(skor × bobot); null bila ada aspek yang belum dinilai
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
    if ($nilai >= 3.40) return 'direkomendasikan';
    if ($nilai >= 2.60) return 'cadangan';
    return 'tidak';
}

// Tambah N hari kerja (Senin–Jumat) dari hari ini
function tambah_hari_kerja(int $n): string {
    $d = new DateTime('today');
    while ($n > 0) {
        $d->modify('+1 day');
        if ((int) $d->format('N') <= 5) $n--;
    }
    return $d->format('Y-m-d');
}

// Konfigurasi SMTP dibaca oleh mailer.php
$GLOBALS['SMTP'] = [
    'host'   => cfg($lokal, 'SMTP_HOST', ''),
    'port'   => (int) cfg($lokal, 'SMTP_PORT', 587),
    'secure' => cfg($lokal, 'SMTP_SECURE', 'tls'),     // 'tls' (STARTTLS), 'ssl', atau 'none' (relay internal)
    'user'   => cfg($lokal, 'SMTP_USER', ''),
    'pass'   => cfg($lokal, 'SMTP_PASS', ''),
    'from'   => cfg($lokal, 'SMTP_FROM', ''),
    'nama'   => cfg($lokal, 'SMTP_FROM_NAME', 'Portal Magang Mahasiswa KPwBI Provinsi Lampung'),
];
unset($lokal);
