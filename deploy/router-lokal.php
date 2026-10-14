<?php
// Router untuk server uji PHP, meniru aturan .htaccess di laptop:
//
//   php -S 0.0.0.0:8740 deploy/router-lokal.php
//
// - "/" membuka beranda (dashboard.html)
// - berkas sensitif (setup.sql, config, vendor, logs, .git, ...) dijawab 404
// - PHP hanya dijalankan di backend/api/
// - alamat yang tidak ada menampilkan 404.html
// Tidak dipakai di server produksi (Apache memakai .htaccess, Nginx memakai konfigurasinya sendiri).

$akar = dirname(__DIR__);
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');

function tidak_ditemukan(string $akar): bool {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    readfile($akar . '/404.html');
    return true;
}

if ($path === '/' || $path === '') {
    header('Content-Type: text/html; charset=utf-8');
    readfile($akar . '/dashboard.html');
    return true;
}

$diblokir = [
    '#(^|/)\.#',                                    // .git, .htaccess, berkas tersembunyi
    '#\.(sql|md|log|lock|dist)$#i',
    '#^/deploy/#',
    '#^/backend/(vendor|logs|migrasi|uploads|font)(/|$)#',
    '#^/backend/(config|config\.local|config\.local\.example|app_helpers|absen_helpers|mailer|batas_percobaan|atur_password_admin)\.php$#',
    '#^/backend/composer\.json$#',
];
foreach ($diblokir as $pola) {
    if (preg_match($pola, $path)) return tidak_ditemukan($akar);
}

$berkas = realpath($akar . $path);
if ($berkas === false || strpos($berkas, $akar) !== 0 || !is_file($berkas)) return tidak_ditemukan($akar);
if (str_ends_with($berkas, '.php') && !preg_match('#^/backend/api/.+\.php$#', $path)) return tidak_ditemukan($akar);

return false;   // biarkan server PHP melayani berkas statis atau menjalankan endpoint API
