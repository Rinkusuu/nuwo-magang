<?php
// Hanya untuk laptop: php -S -t public deploy/router-lokal.php (php -S tidak membaca .htaccess).

$akar = dirname(__DIR__) . '/public';
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
if ($path === '/') $path = '/index.html';

$berkas = realpath($akar . $path);
if (preg_match('#(^|/)\.#', $path) || $berkas === false || !str_starts_with($berkas, $akar . '/') || !is_file($berkas)) {
    http_response_code(404);
    header('Content-Type: text/html; charset=utf-8');
    readfile($akar . '/404.html');
    return true;
}
if ($path === '/index.html') {
    header('Content-Type: text/html; charset=utf-8');
    readfile($berkas);
    return true;
}
return false;
