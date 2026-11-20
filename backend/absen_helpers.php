<?php

require_once __DIR__ . '/app_helpers.php';

const FONT_STEMPEL = __DIR__ . '/font/PlusJakartaSans-Bold.ttf';
const LEBAR_FOTO_MAKS = 720;

function pengaturan_absen(PDO $pdo): array {
    $p = $pdo->query("SELECT kantor_lat, kantor_lng, radius_m, akurasi_maks_m FROM periode WHERE id = 1")->fetch() ?: [];
    return [
        'kantor_lat' => isset($p['kantor_lat']) ? (float) $p['kantor_lat'] : null,
        'kantor_lng' => isset($p['kantor_lng']) ? (float) $p['kantor_lng'] : null,
        'radius_m' => (int) ($p['radius_m'] ?? 150),
        'akurasi_maks_m' => (int) ($p['akurasi_maks_m'] ?? 100),
    ];
}

function peserta_aktif(PDO $pdo, int $userId): array {
    $app = app_by_user($pdo, $userId);
    if (!$app || $app['hasil_akhir'] !== 'diterima' || $app['kesediaan'] !== 'bersedia') {
        return [null, null, 'Absen hanya untuk peserta PKL yang sudah menyatakan bersedia.'];
    }
    $batch = batch_by_id($pdo, $app['batch_id'] ? (int) $app['batch_id'] : null);
    $hariIni = date('Y-m-d');
    if (!$batch) return [$app, null, 'Periode PKL Anda tidak ditemukan.'];
    if ($hariIni < $batch['mulai']) return [$app, $batch, 'Periode PKL Anda belum dimulai.'];
    if ($hariIni > $batch['selesai']) return [$app, $batch, 'Periode PKL Anda sudah selesai.'];
    if (!hari_kerja($hariIni)) return [$app, $batch, 'Absen hanya dapat dilakukan pada hari kerja (Senin–Jumat).'];
    return [$app, $batch, null];
}

function hari_kerja(string $tanggal): bool {
    return (int) date('N', strtotime($tanggal)) <= 5;
}

function absen_hari_ini(PDO $pdo, int $appId): array {
    $q = $pdo->prepare("SELECT id, jenis, waktu, jarak_m FROM absen WHERE application_id = ? AND tanggal = CURDATE()");
    $q->execute([$appId]);
    $hasil = ['datang' => null, 'pulang' => null];
    foreach ($q->fetchAll() as $a) $hasil[$a['jenis']] = $a;
    return $hasil;
}

function jarak_meter(float $lat1, float $lng1, float $lat2, float $lng2): int {
    $r = 6371000;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    return (int) round($r * 2 * atan2(sqrt($a), sqrt(1 - $a)));
}

function stempel_foto(string $sumber, string $tujuan, array $baris): bool {
    $img = @imagecreatefromjpeg($sumber);
    if (!$img) return false;
    $w = imagesx($img);
    $h = imagesy($img);
    if ($w > LEBAR_FOTO_MAKS) {
        $nh = (int) round($h * LEBAR_FOTO_MAKS / $w);
        // imagecopyresampled, bukan imagescale(IMG_BICUBIC): di sebagian versi GD mode bicubic gagal (false)
        $kecil = imagecreatetruecolor(LEBAR_FOTO_MAKS, $nh);
        if (!imagecopyresampled($kecil, $img, 0, 0, 0, 0, LEBAR_FOTO_MAKS, $nh, $w, $h)) return false;
        $img = $kecil;
        $w = LEBAR_FOTO_MAKS;
        $h = $nh;
    }

    $ukuran = max(11, (int) round($w / 42));
    $jarakBaris = (int) round($ukuran * 1.55);
    $tinggiPita = $jarakBaris * count($baris) + $ukuran;
    imagealphablending($img, true);
    $navy = imagecolorallocatealpha($img, 10, 42, 74, 30);
    imagefilledrectangle($img, 0, $h - $tinggiPita, $w, $h, $navy);
    $putih = imagecolorallocate($img, 255, 255, 255);
    $langit = imagecolorallocate($img, 185, 216, 243);
    $y = $h - $tinggiPita + $ukuran + (int) round($ukuran * 0.6);
    foreach ($baris as $i => $teks) {
        imagettftext($img, $i === 0 ? $ukuran + 1 : $ukuran, 0, (int) round($ukuran * 0.9), $y, $i === 0 ? $putih : $langit, FONT_STEMPEL, $teks);
        $y += $jarakBaris;
    }

    $dir = dirname($tujuan);
    if (!is_dir($dir)) mkdir($dir, 0775, true);
    $ok = imagejpeg($img, $tujuan, 82);
    return $ok;
}

const LOGBOOK_MIN = 10;
const LOGBOOK_MAKS = 200;
const LOGBOOK_KEGIATAN_MAKS = 5;

function logbook_hari_ini(PDO $pdo, int $appId): ?array {
    $q = $pdo->prepare("SELECT isi, dibuat_at, diubah_at FROM logbook WHERE application_id = ? AND tanggal = CURDATE()");
    $q->execute([$appId]);
    return $q->fetch() ?: null;
}

function validasi_logbook(string $teks): array {
    $baris = preg_split('/\R/u', $teks);
    $baris = array_values(array_filter(array_map(fn($b) => trim(preg_replace('/[ \t]+/u', ' ', $b)), $baris), fn($b) => $b !== ''));
    $rapi = implode("\n", $baris);
    if (!$baris) return [$rapi, 'Tuliskan minimal satu kegiatan hari ini.'];
    if (count($baris) > LOGBOOK_KEGIATAN_MAKS) return [$rapi, 'Logbook maksimal ' . LOGBOOK_KEGIATAN_MAKS . ' kegiatan.'];
    foreach ($baris as $i => $b) {
        $n = mb_strlen($b);
        if ($n < LOGBOOK_MIN) return [$rapi, 'Kegiatan ' . ($i + 1) . ' terlalu singkat, minimal ' . LOGBOOK_MIN . ' karakter.'];
        if ($n > LOGBOOK_MAKS) return [$rapi, 'Kegiatan ' . ($i + 1) . ' terlalu panjang, maksimal ' . LOGBOOK_MAKS . ' karakter.'];
    }
    return [$rapi, null];
}

function logbook_terkunci(array $absenHari): bool {
    return $absenHari['pulang'] !== null;
}

function logbook_status(PDO $pdo, ?array $app): array {
    if (!$app) return ['isi' => null, 'diubah_at' => null, 'terkunci' => false, 'min' => LOGBOOK_MIN, 'maks' => LOGBOOK_MAKS, 'kegiatan_maks' => LOGBOOK_KEGIATAN_MAKS];
    $l = logbook_hari_ini($pdo, (int) $app['id']);
    return [
        'isi' => $l['isi'] ?? null,
        'diubah_at' => $l['diubah_at'] ?? null,
        'terkunci' => logbook_terkunci(absen_hari_ini($pdo, (int) $app['id'])),
        'min' => LOGBOOK_MIN,
        'maks' => LOGBOOK_MAKS,
        'kegiatan_maks' => LOGBOOK_KEGIATAN_MAKS,
    ];
}
