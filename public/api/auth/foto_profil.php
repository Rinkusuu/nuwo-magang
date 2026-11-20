<?php
require __DIR__ . '/../../../backend/config.php';

$dir = UPLOAD_DIR . 'profil/';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : pengguna_aktif();
    if (!$id || ($id !== pengguna_aktif() && !staf_aktif())) fail('Foto tidak ditemukan.', 404);
    $q = $pdo->prepare("SELECT foto FROM users WHERE id = ?");
    $q->execute([$id]);
    $foto = $q->fetchColumn();
    if (!$foto || !is_file($dir . $foto)) fail('Foto tidak ditemukan.', 404);
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($dir . $foto));
    header('Cache-Control: private, max-age=86400');
    readfile($dir . $foto);
    exit;
}

$userId = require_user();
$q = $pdo->prepare("SELECT foto FROM users WHERE id = ?");
$q->execute([$userId]);
$lama = $q->fetchColumn() ?: null;

$baru = null;
$file = $_FILES['foto'] ?? null;
if ($file) {
    if ($file['error'] !== UPLOAD_ERR_OK || $file['size'] > 5 * 1024 * 1024) fail('Foto gagal diunggah. Ukuran maksimal 5 MB.');
    $info = @getimagesize($file['tmp_name']);
    $buka = ['image/jpeg' => 'imagecreatefromjpeg', 'image/png' => 'imagecreatefrompng', 'image/webp' => 'imagecreatefromwebp'];
    if (!$info || !isset($buka[$info['mime']])) fail('Foto harus berformat JPG, PNG, atau WebP.');
    if ($info[0] > 6000 || $info[1] > 6000) fail('Resolusi foto terlalu besar.');
    $img = @$buka[$info['mime']]($file['tmp_name']);
    if (!$img) fail('Foto tidak dapat dibaca.');

    $sisi = min($info[0], $info[1]);
    $kotak = imagecreatetruecolor(256, 256);
    imagefill($kotak, 0, 0, imagecolorallocate($kotak, 255, 255, 255));
    imagecopyresampled($kotak, $img, 0, 0, intdiv($info[0] - $sisi, 2), intdiv($info[1] - $sisi, 2), 256, 256, $sisi, $sisi);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $baru = $userId . '_' . bin2hex(random_bytes(6)) . '.jpg';
    if (!imagejpeg($kotak, $dir . $baru, 85)) fail('Foto gagal disimpan.', 500);
} elseif (empty(body()['hapus'])) {
    fail('Pilih foto terlebih dahulu.');
}

$pdo->prepare("UPDATE users SET foto = ? WHERE id = ?")->execute([$baru, $userId]);
if ($lama && is_file($dir . $lama)) unlink($dir . $lama);

json_out(['success' => true, 'message' => $baru ? 'Foto profil diperbarui.' : 'Foto profil dihapus.', 'foto' => $baru]);
