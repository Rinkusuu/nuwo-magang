<?php
// Uji asap: membuat database magang_uji dari setup.sql + contoh_data.sql, menyalakan php -S,
// lalu mengetes alur utama lewat HTTP. Butuh klien mysql di PATH.
//
//   MAGANG_DB_USER=root MAGANG_DB_PASS=... php tests/asap.php

$akar = dirname(__DIR__);
$db = ['host' => getenv('MAGANG_DB_HOST') ?: '127.0.0.1', 'port' => getenv('MAGANG_DB_PORT') ?: '3306',
       'user' => getenv('MAGANG_DB_USER') ?: 'root', 'pass' => getenv('MAGANG_DB_PASS') ?: ''];
$port = 8790;
$base = "http://127.0.0.1:$port";
$gagal = 0;

function cek(bool $ok, string $nama): void {
    global $gagal;
    echo ($ok ? 'ok    ' : 'GAGAL ') . $nama . "\n";
    if (!$ok) $gagal++;
}

function sql(string $isi): void {
    global $db;
    $p = proc_open(['mysql', '-h', $db['host'], '-P', $db['port'], '-u', $db['user']],
        [0 => ['pipe', 'r'], 1 => STDOUT, 2 => STDERR], $pipa, null, ['MYSQL_PWD' => $db['pass']] + getenv());
    fwrite($pipa[0], $isi);
    fclose($pipa[0]);
    if (proc_close($p) !== 0) { fwrite(STDERR, "Impor SQL gagal.\n"); exit(1); }
}

function minta(string $url, ?array $json = null, string $jar = ''): array {
    global $base;
    static $token = [];
    $c = curl_init($base . $url);
    curl_setopt_array($c, [CURLOPT_RETURNTRANSFER => true, CURLOPT_COOKIEJAR => $jar, CURLOPT_COOKIEFILE => $jar]);
    if ($json !== null) {
        $token[$jar] ??= json_decode(minta('/backend/api/auth/csrf.php', null, $jar)['isi'], true)['token'];
        curl_setopt_array($c, [CURLOPT_POSTFIELDS => json_encode($json),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'X-CSRF-Token: ' . $token[$jar]]]);
    }
    $isi = curl_exec($c);
    $kode = curl_getinfo($c, CURLINFO_RESPONSE_CODE);
    $hasil = json_decode((string) $isi, true);
    if (isset($hasil['csrf'])) $token[$jar] = $hasil['csrf'];
    return ['kode' => $kode, 'isi' => (string) $isi, 'json' => $hasil];
}

preg_match('/const BOBOT_ASPEK = \[([^\]]*)\]/', file_get_contents("$akar/backend/config.php"), $m);
$bobotPhp = array_map('intval', explode(',', $m[1]));
preg_match_all('/bobot: (\d+)/', file_get_contents("$akar/assets/rubrik.js"), $m);
cek($bobotPhp === array_map('intval', $m[1]), 'bobot di assets/rubrik.js sama dengan BOBOT_ASPEK');

$ganti = fn($f) => str_replace('magang_bi', 'magang_uji', file_get_contents("$akar/$f"));
sql($ganti('setup.sql') . $ganti('contoh_data.sql'));
sql("USE magang_uji;
    UPDATE pengaturan SET kantor_lat = -5.4, kantor_lng = 105.26 WHERE id = 1;
    UPDATE batch SET mulai = CURDATE() - INTERVAL 7 DAY, selesai = CURDATE() + INTERVAL 30 DAY WHERE id = 1;
    UPDATE applications SET hasil_akhir = 'diterima', kesediaan = 'bersedia' WHERE id = 1;
    INSERT INTO logbook (application_id, tanggal, isi) VALUES (1, CURDATE() - INTERVAL 1 DAY, 'Kegiatan uji asap');");

$env = ['MAGANG_DB_HOST' => $db['host'], 'MAGANG_DB_PORT' => $db['port'], 'MAGANG_DB_USER' => $db['user'],
        'MAGANG_DB_PASS' => $db['pass'], 'MAGANG_DB_NAME' => 'magang_uji', 'MAGANG_BATAS_PERCOBAAN' => '0'] + getenv();
$server = proc_open([PHP_BINARY, '-S', "127.0.0.1:$port", 'deploy/router-lokal.php'],
    [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipa, $akar, $env);
register_shutdown_function(fn() => proc_terminate($server));
for ($i = 0; $i < 50 && !@fsockopen('127.0.0.1', $port); $i++) usleep(100000);

$r = minta('/');
cek($r['kode'] === 200 && str_contains($r['isi'], 'Nuwo Magang'), 'beranda tampil');
foreach (['/setup.sql', '/contoh_data.sql', '/backend/config.php', '/backend/config.local.php', '/deploy/nginx.conf', '/tests/asap.php'] as $p) {
    cek(minta($p)['kode'] === 404, "$p tertutup");
}

$admin = tempnam(sys_get_temp_dir(), 'admin');
$r = minta('/backend/api/auth/admin_login.php', ['email' => 'admin@contoh.test', 'password' => 'AdminBI2026'], $admin);
cek(($r['json']['success'] ?? false) === true, 'login admin');
$r = minta('/backend/api/admin/applications.php', null, $admin);
cek(count($r['json']['data'] ?? []) === 2, 'daftar pelamar berisi 2 data contoh');
cek(str_starts_with(minta('/backend/api/admin/export.php', null, $admin)['isi'], "\xEF\xBB\xBF"), 'ekspor CSV');
$r = minta('/backend/api/admin/pengaturan.php', null, $admin);
cek(isset($r['json']['pengaturan']['kantor_lat']), 'baca pengaturan');
$r = minta('/backend/api/admin/pengaturan.php', ['hari_perbaikan' => 4, 'kantor_lat' => '-5.4', 'kantor_lng' => '105.26', 'radius_m' => 150, 'akurasi_maks_m' => 100], $admin);
cek(($r['json']['success'] ?? false) === true, 'simpan pengaturan');

$pelamar = tempnam(sys_get_temp_dir(), 'pelamar');
$r = minta('/backend/api/auth/login.php', ['email' => 'rahmat@email.com', 'password' => 'Demo1234'], $pelamar);
cek(($r['json']['success'] ?? false) === true, 'login pelamar');
cek((minta('/backend/api/formulir/status.php', null, $pelamar)['json']['has_application'] ?? false) === true, 'status pendaftaran');
$hariKerja = date('N') < 6;
cek((minta('/backend/api/absen/status.php', null, $pelamar)['json']['boleh'] ?? null) === $hariKerja, $hariKerja ? 'peserta boleh absen' : 'absen ditolak di akhir pekan');
$r = minta('/backend/api/pkl/logbook_excel.php', null, $pelamar);
$xlsx = tempnam(sys_get_temp_dir(), 'lb');
file_put_contents($xlsx, $r['isi']);
$zip = new ZipArchive();
cek($zip->open($xlsx) === true && str_contains($zip->getFromName('xl/worksheets/sheet1.xml') . $zip->getFromName('xl/sharedStrings.xml'), 'Kegiatan uji asap'), 'Excel logbook berisi kegiatan');

sql('DROP DATABASE magang_uji;');
echo $gagal ? "\n$gagal pemeriksaan gagal\n" : "\nSemua lolos\n";
exit($gagal ? 1 : 0);
