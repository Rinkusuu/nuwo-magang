<?php
// Salin ke config.local.php. Tiap kunci juga bisa diisi lewat env MAGANG_<KUNCI>.
return [
    'APP_URL'  => 'https://magang.contoh.go.id',
    'DEBUG'    => false,
    // false hanya untuk demo di laptop
    'BATAS_PERCOBAAN' => true,

    'DB_HOST'  => 'localhost',
    'DB_PORT'  => '3306',
    'DB_NAME'  => 'magang_bi',
    'DB_USER'  => 'magang_app',
    'DB_PASS'  => 'ganti-dengan-kata-sandi-kuat',

    // SMTP_HOST kosong: email hanya ditulis ke backend/logs/email.log
    'SMTP_HOST'      => '',
    'SMTP_PORT'      => 587,
    'SMTP_SECURE'    => 'tls',          // tls (587), ssl (465), none
    'SMTP_USER'      => '',
    'SMTP_PASS'      => '',
    'SMTP_FROM'      => '',
    'SMTP_FROM_NAME' => 'Nuwo Magang KPwBI Provinsi Lampung',
];
