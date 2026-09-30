<?php
// Salin menjadi config.local.php lalu isi sesuai server. Berkas config.local.php
// diabaikan git, jadi kata sandi tidak ikut ke repositori.
// Setiap kunci juga bisa diisi lewat variabel lingkungan MAGANG_<KUNCI>.
return [
    // Alamat publik portal, dipakai untuk tautan di email (tanpa garis miring di akhir)
    'APP_URL'  => 'https://magang.contoh.go.id',
    'DEBUG'    => false,
    // Batas percobaan login/daftar/lupa sandi. JANGAN dimatikan di server; false hanya untuk demo di laptop
    'BATAS_PERCOBAAN' => true,

    // Database: buat user khusus, jangan pakai root
    'DB_HOST'  => 'localhost',
    'DB_PORT'  => '3306',
    'DB_NAME'  => 'magang_bi',
    'DB_USER'  => 'magang_app',
    'DB_PASS'  => 'ganti-dengan-kata-sandi-kuat',

    // SMTP untuk email notifikasi dan lupa kata sandi.
    // Selama SMTP_HOST kosong, email tidak dikirim melainkan dicatat di backend/logs/email.log.
    'SMTP_HOST'      => '',
    'SMTP_PORT'      => 587,
    'SMTP_SECURE'    => 'tls',          // 'tls' untuk port 587, 'ssl' untuk port 465, 'none' hanya untuk relay internal
    'SMTP_USER'      => '',
    'SMTP_PASS'      => '',
    'SMTP_FROM'      => '',             // alamat pengirim, mis. magang@contoh.go.id
    'SMTP_FROM_NAME' => 'Portal Magang Mahasiswa KPwBI Provinsi Lampung',
];
