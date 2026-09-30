<?php
require '../../config.php';
require '../../app_helpers.php';

// Simpan draf formulir (dipanggil otomatis saat pelamar mengisi)
$userId = require_user();
$app = app_ensure($pdo, $userId);
if (!app_editable($app)) fail('Pendaftaran sudah dikirim dan tidak dapat diubah.', 409);

$data = body();
$sets = [];
$vals = [];
foreach (FIELD_FORMULIR as $f) {
    if (!array_key_exists($f, $data)) continue;
    $v = is_string($data[$f]) ? trim($data[$f]) : $data[$f];
    if ($v === '' || $v === null) $v = null;

    if ($f === 'pernyataan') {
        $v = $v ? 1 : 0;
    } elseif ($f === 'jenjang' && $v !== null && !in_array($v, ['S1', 'D3', 'D4', 'S2'], true)) {
        $v = null;
    } elseif ($f === 'ipk' && $v !== null) {
        $num = str_replace(',', '.', (string) $v);
        $v = is_numeric($num) ? min(4, max(0, (float) $num)) : null;
    } elseif (in_array($f, ['semester', 'bidang_utama_id', 'bidang_alternatif_id'], true) && $v !== null) {
        $v = (int) $v ?: null;
    } elseif (in_array($f, ['tanggal_mulai', 'tanggal_selesai'], true) && $v !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)) {
        $v = null;
    }

    $sets[] = "$f = ?";
    $vals[] = $v;
}

if ($sets) {
    $vals[] = $app['id'];
    $pdo->prepare("UPDATE applications SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);
}

json_out(['success' => true, 'message' => 'Draf tersimpan.']);
