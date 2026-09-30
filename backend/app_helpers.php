<?php
// Helper bersama untuk data pendaftaran (dipakai endpoint pelamar dan admin)

const FIELD_FORMULIR = [
    'nama', 'email', 'no_wa', 'domisili',
    'universitas', 'fakultas_prodi', 'nim', 'ipk', 'jenjang', 'semester',
    'bidang_utama_id', 'bidang_alternatif_id', 'tanggal_mulai', 'tanggal_selesai',
    'pernyataan',
];

function app_by_user(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

// Buat draf pendaftaran bila belum ada, prefill dari data akun
function app_ensure(PDO $pdo, int $userId): array {
    $app = app_by_user($pdo, $userId);
    if ($app) return $app;
    $u = $pdo->prepare("SELECT nama, email, no_wa FROM users WHERE id = ?");
    $u->execute([$userId]);
    $user = $u->fetch();
    $pdo->prepare("INSERT INTO applications (user_id, nama, email, no_wa) VALUES (?, ?, ?, ?)")
        ->execute([$userId, $user['nama'], $user['email'], $user['no_wa']]);
    return app_by_user($pdo, $userId);
}

// Pelamar hanya dapat mengubah data selama draf atau saat diminta perbaikan
function app_editable(array $app): bool {
    return $app['submitted_at'] === null || $app['status_admin'] === 'perbaikan';
}

// Dikembalikan sebagai objek agar JSON selalu berbentuk {} walau kosong
function app_files(PDO $pdo, int $appId): object {
    $stmt = $pdo->prepare("SELECT id, jenis, original_name, size, status_verifikasi, uploaded_at FROM application_files WHERE application_id = ?");
    $stmt->execute([$appId]);
    $files = [];
    foreach ($stmt->fetchAll() as $f) $files[$f['jenis']] = $f;
    return (object) $files;
}

function bidang_map(PDO $pdo): array {
    $map = [];
    foreach ($pdo->query("SELECT id, nama, singkat FROM bidang") as $b) $map[$b['id']] = $b;
    return $map;
}
