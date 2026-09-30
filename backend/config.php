<?php
$DB_HOST = getenv('MAGANG_DB_HOST') ?: 'localhost';
$DB_NAME = getenv('MAGANG_DB_NAME') ?: 'magang_bi';
$DB_USER = 'root';
$DB_PASS = '';

header('Content-Type: application/json');

try {
    $pdo = new PDO(
        "mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4",
        $DB_USER,
        $DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    die(json_encode(['success' => false, 'message' => 'Koneksi database gagal: ' . $e->getMessage()]));
}

session_start();

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
    return json_decode(file_get_contents('php://input'), true) ?? [];
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
