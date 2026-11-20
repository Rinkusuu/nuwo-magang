<?php

const FIELD_FORMULIR = [
    'nama', 'email', 'no_wa', 'domisili',
    'universitas', 'fakultas_prodi', 'nim', 'ipk', 'jenjang', 'semester',
    'batch_id', 'pernyataan',
];

const PANJANG_MAKS = [
    'nama' => 150, 'no_wa' => 20, 'domisili' => 100, 'universitas' => 150, 'fakultas_prodi' => 200, 'nim' => 30,
];
const LABEL_FORMULIR = [
    'nama' => 'nama lengkap', 'no_wa' => 'nomor HP/WhatsApp', 'domisili' => 'domisili', 'universitas' => 'perguruan tinggi',
    'fakultas_prodi' => 'fakultas / program studi', 'nim' => 'NPM',
];

function app_by_user(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

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

function app_editable(array $app): bool {
    return $app['submitted_at'] === null || $app['status_admin'] === 'perbaikan';
}

function app_files(PDO $pdo, int $appId): object {
    $stmt = $pdo->prepare("SELECT id, jenis, original_name, size, status_verifikasi, uploaded_at FROM application_files WHERE application_id = ?");
    $stmt->execute([$appId]);
    $files = [];
    foreach ($stmt->fetchAll() as $f) $files[$f['jenis']] = $f;
    return (object) $files;
}

function batch_by_id(PDO $pdo, ?int $id): ?array {
    if (!$id) return null;
    $q = $pdo->prepare("SELECT * FROM batch WHERE id = ?");
    $q->execute([$id]);
    return $q->fetch() ?: null;
}

function batch_terbuka(array $batch): bool {
    $hariIni = date('Y-m-d');
    return $hariIni >= $batch['daftar_buka'] && $hariIni <= $batch['daftar_tutup'];
}

function batch_publik(array $b): array {
    return array_intersect_key($b, array_flip(['id', 'nama', 'daftar_buka', 'daftar_tutup', 'mulai', 'selesai']))
        + ['terbuka' => batch_terbuka($b)];
}

function berkas_pkl(PDO $pdo, int $appId): object {
    $q = $pdo->prepare("SELECT id, jenis, original_name, size, uploaded_at FROM berkas_pkl WHERE application_id = ?");
    $q->execute([$appId]);
    $hasil = [];
    foreach ($q->fetchAll() as $f) $hasil[$f['jenis']] = $f;
    return (object) $hasil;
}
