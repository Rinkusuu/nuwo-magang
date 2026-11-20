<?php
require __DIR__ . '/../../../backend/config.php';
require __DIR__ . '/../../../backend/app_helpers.php';

$userId = require_user();
$app = app_ensure($pdo, $userId);
if (!app_editable($app)) fail('Pendaftaran sudah dikirim dan tidak dapat diubah.', 409);

$data = body();
$sets = [];
$vals = [];
foreach (FIELD_FORMULIR as $f) {
    // Email pendaftaran selalu sama dengan email akun (disinkronkan di bawah), supaya email
    // resmi portal tidak bisa diarahkan ke alamat orang lain
    if ($f === 'email' || !array_key_exists($f, $data)) continue;
    $v = is_string($data[$f]) ? trim($data[$f]) : $data[$f];
    if ($v === '' || $v === null) $v = null;
    if (is_array($v)) fail('Isian formulir tidak valid.');
    if (isset(PANJANG_MAKS[$f]) && $v !== null && mb_strlen((string) $v) > PANJANG_MAKS[$f]) {
        fail('Isian ' . LABEL_FORMULIR[$f] . ' terlalu panjang (maksimal ' . PANJANG_MAKS[$f] . ' karakter).');
    }

    if ($f === 'pernyataan') {
        $v = $v ? 1 : 0;
    } elseif ($f === 'jenjang' && $v !== null && !in_array($v, ['S1', 'D3', 'D4', 'S2'], true)) {
        $v = null;
    } elseif ($f === 'ipk' && $v !== null) {
        $num = str_replace(',', '.', (string) $v);
        $v = is_numeric($num) ? min(4, max(0, (float) $num)) : null;
    } elseif ($f === 'semester' && $v !== null) {
        $v = (int) $v >= 1 && (int) $v <= 14 ? (int) $v : null;
    } elseif ($f === 'batch_id' && $v !== null) {
        $v = batch_by_id($pdo, (int) $v) ? (int) $v : null;
    }

    $sets[] = "$f = ?";
    $vals[] = $v;
}

$sets[] = "email = (SELECT email FROM users WHERE id = ?)";
$vals[] = $userId;
$vals[] = $app['id'];
$pdo->prepare("UPDATE applications SET " . implode(', ', $sets) . " WHERE id = ?")->execute($vals);

json_out(['success' => true, 'message' => 'Draf tersimpan.']);
