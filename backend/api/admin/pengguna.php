<?php
require '../../config.php';

$adminId = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("
        SELECT ad.id, ad.nama, ad.email, ad.peran, ad.aktif, ad.created_at,
               (SELECT COUNT(*) FROM interview_assessments i WHERE i.admin_id = ad.id AND i.lengkap = 1) AS jumlah_penilaian
        FROM admins ad ORDER BY ad.aktif DESC, ad.peran, ad.nama
    ")->fetchAll();
    json_out(['success' => true, 'data' => $rows, 'saya' => $adminId]);
}

$data = body();
$aksi = $data['aksi'] ?? '';
$id = (int) ($data['id'] ?? 0);

$sandiValid = function (string $sandi): void {
    if (strlen($sandi) < 12) fail('Kata sandi minimal 12 karakter.');
    if (strlen($sandi) > 72) fail('Kata sandi maksimal 72 karakter.');
};
$sisaAdminLain = function (int $kecuali) use ($pdo): int {
    $q = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE peran = 'admin' AND aktif = 1 AND id <> ?");
    $q->execute([$kecuali]);
    return (int) $q->fetchColumn();
};
$akun = function (int $id) use ($pdo): array {
    $q = $pdo->prepare("SELECT id, nama, email, peran, aktif FROM admins WHERE id = ?");
    $q->execute([$id]);
    $a = $q->fetch();
    if (!$a) fail('Akun tidak ditemukan.', 404);
    return $a;
};

if ($aksi === 'tambah') {
    $nama = trim((string) ($data['nama'] ?? ''));
    $email = strtolower(trim((string) ($data['email'] ?? '')));
    $peran = $data['peran'] ?? '';
    $sandi = (string) ($data['sandi'] ?? '');
    if ($nama === '' || mb_strlen($nama) > 150) fail('Nama wajib diisi.');
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) fail('Email tidak valid.');
    if (!in_array($peran, ['admin', 'pewawancara'], true)) fail('Pilih peran akun.');
    $sandiValid($sandi);
    $q = $pdo->prepare("SELECT id FROM admins WHERE email = ?");
    $q->execute([$email]);
    if ($q->fetch()) fail('Email tersebut sudah dipakai akun lain.', 409);
    $pdo->prepare("INSERT INTO admins (nama, email, password, peran) VALUES (?, ?, ?, ?)")
        ->execute([$nama, $email, password_hash($sandi, PASSWORD_BCRYPT), $peran]);
    json_out(['success' => true, 'message' => "Akun $nama ditambahkan."]);
}

if ($aksi === 'ubah') {
    $a = $akun($id);
    $nama = trim((string) ($data['nama'] ?? ''));
    $peran = $data['peran'] ?? '';
    if ($nama === '' || mb_strlen($nama) > 150) fail('Nama wajib diisi.');
    if (!in_array($peran, ['admin', 'pewawancara'], true)) fail('Pilih peran akun.');
    if ($a['peran'] === 'admin' && $peran !== 'admin' && $sisaAdminLain($id) === 0) {
        fail('Harus ada minimal satu admin aktif. Jadikan akun lain admin terlebih dahulu.');
    }
    $pdo->prepare("UPDATE admins SET nama = ?, peran = ? WHERE id = ?")->execute([$nama, $peran, $id]);
    json_out(['success' => true, 'message' => 'Akun diperbarui.']);
}

if ($aksi === 'sandi') {
    $akun($id);
    $sandi = (string) ($data['sandi'] ?? '');
    $sandiValid($sandi);
    $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?")->execute([password_hash($sandi, PASSWORD_BCRYPT), $id]);
    json_out(['success' => true, 'message' => 'Kata sandi diatur ulang. Sampaikan kata sandi baru kepada pemilik akun.']);
}

if ($aksi === 'aktif') {
    $a = $akun($id);
    $aktif = !empty($data['aktif']) ? 1 : 0;
    if (!$aktif && $id === $adminId) fail('Anda tidak dapat menonaktifkan akun sendiri.');
    if (!$aktif && $a['peran'] === 'admin' && $sisaAdminLain($id) === 0) fail('Harus ada minimal satu admin aktif.');
    $pdo->prepare("UPDATE admins SET aktif = ? WHERE id = ?")->execute([$aktif, $id]);
    json_out(['success' => true, 'message' => $aktif ? 'Akun diaktifkan kembali.' : 'Akun dinonaktifkan dan tidak dapat masuk lagi.']);
}

fail('Aksi tidak dikenal.');
