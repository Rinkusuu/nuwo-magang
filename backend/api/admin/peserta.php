<?php
require '../../config.php';
require '../../app_helpers.php';

require_admin();

$rows = $pdo->query("
    SELECT a.id, a.no_pendaftaran, a.nama, a.email, a.no_wa, a.universitas, a.fakultas_prodi, a.batch_id,
           a.kesediaan, a.kesediaan_at, a.decided_at, b.nama AS batch, b.mulai, b.selesai,
           (SELECT COUNT(*) FROM logbook l WHERE l.application_id = a.id) AS hari_logbook
    FROM applications a LEFT JOIN batch b ON b.id = a.batch_id
    WHERE a.hasil_akhir = 'diterima'
    ORDER BY b.mulai DESC, a.nama
")->fetchAll();
foreach ($rows as &$r) $r['berkas_pkl'] = berkas_pkl($pdo, (int) $r['id']);
unset($r);

$batch = $pdo->query("SELECT id, nama FROM batch ORDER BY daftar_buka DESC, id DESC")->fetchAll();
json_out(['success' => true, 'data' => $rows, 'batch' => $batch]);
