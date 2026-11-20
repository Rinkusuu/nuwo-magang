<?php
require __DIR__ . '/../../../backend/config.php';
require __DIR__ . '/../../../backend/app_helpers.php';

$adminId = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $batch = $pdo->query("
        SELECT b.*,
               (SELECT COUNT(*) FROM applications a WHERE a.batch_id = b.id AND a.submitted_at IS NOT NULL) AS pendaftar,
               (SELECT COUNT(*) FROM applications a WHERE a.batch_id = b.id AND a.status_seleksi = 'lolos') AS lolos_seleksi,
               (SELECT COUNT(*) FROM applications a WHERE a.batch_id = b.id AND a.hasil_akhir = 'diterima') AS diterima,
               (SELECT COUNT(*) FROM applications a WHERE a.batch_id = b.id AND a.kesediaan = 'bersedia') AS bersedia,
               (SELECT COUNT(*) FROM applications a WHERE a.batch_id = b.id) AS dipakai
        FROM batch b ORDER BY b.daftar_buka DESC, b.id DESC
    ")->fetchAll();
    foreach ($batch as &$b) $b['terbuka'] = batch_terbuka($b);
    unset($b);
    json_out(['success' => true, 'batch' => $batch, 'hari_ini' => date('Y-m-d')]);
}

$data = body();

if (isset($data['hapus'])) {
    $id = (int) $data['hapus'];
    $q = $pdo->prepare("SELECT COUNT(*) FROM applications WHERE batch_id = ?");
    $q->execute([$id]);
    if ((int) $q->fetchColumn() > 0) fail('Batch ini sudah dipilih pelamar sehingga tidak dapat dihapus.');
    $pdo->prepare("DELETE FROM batch WHERE id = ?")->execute([$id]);
    json_out(['success' => true, 'message' => 'Batch dihapus.']);
}

$tanggal = fn($k) => preg_match('/^\d{4}-\d{2}-\d{2}$/', trim((string) ($data[$k] ?? ''))) ? trim($data[$k]) : null;
$nama = trim((string) ($data['nama'] ?? ''));
$buka = $tanggal('daftar_buka');
$tutup = $tanggal('daftar_tutup');
$mulai = $tanggal('mulai');
$selesai = $tanggal('selesai');
$wawancara = $tanggal('wawancara_tanggal');
$jam = preg_match('/^\d{2}:\d{2}/', (string) ($data['wawancara_jam'] ?? '')) ? substr($data['wawancara_jam'], 0, 5) : null;
$tautan = function (string $k, string $label) use ($data) {
    $v = trim((string) ($data[$k] ?? ''));
    if ($v === '') return null;
    if (!preg_match('#^https://#i', $v) || !filter_var($v, FILTER_VALIDATE_URL)) fail("$label harus berupa tautan lengkap yang diawali https://");
    return mb_substr($v, 0, 500);
};

if ($nama === '' || mb_strlen($nama) > 100) fail('Nama batch wajib diisi (maksimal 100 karakter).');
if (!$buka || !$tutup || !$mulai || !$selesai) fail('Tanggal pendaftaran dan pelaksanaan wajib diisi.');
if ($tutup < $buka) fail('Tanggal tutup pendaftaran harus sama atau setelah tanggal buka.');
if ($selesai <= $mulai) fail('Tanggal selesai pelaksanaan harus setelah tanggal mulai.');
if ($mulai <= $tutup) fail('Pelaksanaan harus dimulai setelah pendaftaran ditutup.');
$zoom = $tautan('link_zoom', 'Link Zoom');
$wa = $tautan('link_wa', 'Link grup WhatsApp');

$nilai = [$nama, $buka, $tutup, $mulai, $selesai, $wawancara, $jam, $zoom, $wa];
if (!empty($data['id'])) {
    $pdo->prepare("UPDATE batch SET nama = ?, daftar_buka = ?, daftar_tutup = ?, mulai = ?, selesai = ?,
        wawancara_tanggal = ?, wawancara_jam = ?, link_zoom = ?, link_wa = ? WHERE id = ?")
        ->execute([...$nilai, (int) $data['id']]);
    json_out(['success' => true, 'message' => 'Batch diperbarui.']);
}
$pdo->prepare("INSERT INTO batch (nama, daftar_buka, daftar_tutup, mulai, selesai, wawancara_tanggal, wawancara_jam, link_zoom, link_wa)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")->execute($nilai);
json_out(['success' => true, 'message' => 'Batch ditambahkan.', 'id' => (int) $pdo->lastInsertId()]);
