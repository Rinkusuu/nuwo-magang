<?php
require '../../config.php';
require '../../absen_helpers.php';

$adminId = require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $tanggal = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['tanggal'] ?? '') ? $_GET['tanggal'] : date('Y-m-d');
    $batchId = (int) ($_GET['batch'] ?? 0);
    $q = $pdo->prepare("
        SELECT a.id, a.no_pendaftaran, a.nama, a.universitas, b.nama AS batch
        FROM applications a JOIN batch b ON b.id = a.batch_id
        WHERE a.hasil_akhir = 'diterima' AND a.kesediaan = 'bersedia'
          AND ? BETWEEN b.mulai AND b.selesai AND (? = 0 OR b.id = ?)
        ORDER BY a.nama
    ");
    $q->execute([$tanggal, $batchId, $batchId]);
    $peserta = hari_kerja($tanggal) ? $q->fetchAll() : [];

    $absen = [];
    if ($peserta) {
        $ids = implode(',', array_map(fn($p) => (int) $p['id'], $peserta));
        $r = $pdo->prepare("
            SELECT ab.id, ab.application_id, ab.jenis, ab.waktu, ab.jarak_m, ab.akurasi_m, ab.foto IS NOT NULL AS ada_foto,
                   ab.catatan_koreksi, ab.dikoreksi_at, ad.nama AS dikoreksi_oleh
            FROM absen ab LEFT JOIN admins ad ON ad.id = ab.dikoreksi_by
            WHERE ab.tanggal = ? AND ab.application_id IN ($ids)
        ");
        $r->execute([$tanggal]);
        foreach ($r->fetchAll() as $a) $absen[$a['application_id']][$a['jenis']] = $a;
    }
    $logbook = [];
    if ($peserta) {
        $l = $pdo->prepare("SELECT application_id, isi, diubah_at FROM logbook WHERE tanggal = ? AND application_id IN ($ids)");
        $l->execute([$tanggal]);
        foreach ($l->fetchAll() as $row) $logbook[$row['application_id']] = $row;
    }
    foreach ($peserta as &$p) {
        $p['datang'] = $absen[$p['id']]['datang'] ?? null;
        $p['pulang'] = $absen[$p['id']]['pulang'] ?? null;
        $p['logbook'] = isset($logbook[$p['id']]) ? ['isi' => $logbook[$p['id']]['isi'], 'diubah_at' => $logbook[$p['id']]['diubah_at']] : null;
    }
    unset($p);

    $batch = $pdo->query("SELECT id, nama, mulai, selesai FROM batch ORDER BY mulai DESC, id DESC")->fetchAll();
    json_out(['success' => true, 'tanggal' => $tanggal, 'hari_kerja' => hari_kerja($tanggal), 'data' => $peserta, 'batch' => $batch]);
}

$data = body();
$appId = (int) ($data['application_id'] ?? 0);
$jenis = $data['jenis'] ?? '';
$tanggal = $data['tanggal'] ?? '';
$jam = $data['jam'] ?? '';
$alasan = trim((string) ($data['alasan'] ?? ''));
if (!in_array($jenis, ['datang', 'pulang'], true)) fail('Jenis absen tidak valid.');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal) || !preg_match('/^\d{2}:\d{2}$/', $jam)) fail('Tanggal dan jam wajib diisi.');
if (mb_strlen($alasan) < 5) fail('Tuliskan alasan koreksi.');
if (!hari_kerja($tanggal)) fail('Sabtu dan Minggu bukan hari absen.');

$q = $pdo->prepare("SELECT a.id FROM applications a JOIN batch b ON b.id = a.batch_id
    WHERE a.id = ? AND a.hasil_akhir = 'diterima' AND a.kesediaan = 'bersedia' AND ? BETWEEN b.mulai AND b.selesai");
$q->execute([$appId, $tanggal]);
if (!$q->fetch()) fail('Peserta tidak aktif pada tanggal tersebut.');

$waktu = "$tanggal $jam:00";
$pdo->prepare("
    INSERT INTO absen (application_id, tanggal, jenis, waktu, dikoreksi_by, catatan_koreksi, dikoreksi_at)
    VALUES (?, ?, ?, ?, ?, ?, NOW())
    ON DUPLICATE KEY UPDATE waktu = VALUES(waktu), dikoreksi_by = VALUES(dikoreksi_by),
        catatan_koreksi = VALUES(catatan_koreksi), dikoreksi_at = NOW()
")->execute([$appId, $tanggal, $jenis, $waktu, $adminId, mb_substr($alasan, 0, 500)]);

json_out(['success' => true, 'message' => 'Koreksi absen disimpan.']);
