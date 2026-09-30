# Portal Magang Mahasiswa — KPwBI Provinsi Lampung

Pendaftaran, seleksi, dan pengumuman Program Magang Mahasiswa S1.
Frontend HTML/CSS/JavaScript tanpa proses build, backend PHP 8 + MySQL/MariaDB.

## Menjalankan di laptop

```bash
mysql -uroot < setup.sql                       # membuat ulang database magang_bi + data contoh
cp backend/config.local.example.php backend/config.local.php   # lalu sesuaikan DB_USER/DB_PASS
php -S 127.0.0.1:8740                          # buka http://127.0.0.1:8740/dashboard.html
```

Akun contoh: pelamar `rahmat@email.com` / `Demo1234`, admin `admin@bi.go.id` / `AdminBI2026`.
Selama `SMTP_HOST` kosong, email tidak dikirim melainkan dicatat di `backend/logs/email.log`.

> `php -S` tidak membaca `.htaccess`, jadi halaman 404 dan pemblokiran berkas baru
> berlaku di Apache atau Nginx.

## Memasang di server

1. Salin seluruh folder ke server (portal diasumsikan di akar domain).
2. Buat database dan user khusus, lalu impor `setup.sql` (hapus data contoh terlebih dahulu).
   Database yang sudah ada sebelum Oktober 2026: jalankan `backend/migrasi/001_keamanan_dan_reset_sandi.sql`.
3. Buat `backend/config.local.php` dari contohnya: `APP_URL`, kredensial database, SMTP.
4. Ganti kata sandi admin: `php backend/atur_password_admin.php admin@bi.go.id`
5. Pastikan `backend/uploads/` dan `backend/logs/` bisa ditulis oleh PHP.
6. Web server:
   - **Apache**: `.htaccess` di folder utama sudah berisi aturan (butuh `mod_headers`, `mod_alias`).
   - **Nginx + PHP-FPM**: lihat `deploy/nginx-portal-magang.conf`.
7. Pasang HTTPS, lalu aktifkan HSTS.

Daftar pekerjaan yang masih tertunda ada di [CATATAN-TINDAK-LANJUT.md](CATATAN-TINDAK-LANJUT.md).

## Struktur singkat

| Lokasi | Isi |
| --- | --- |
| `*.html` | Halaman pelamar dan backoffice |
| `assets/portal.css` | Token desain dan komponen bersama |
| `assets/gerak.js` | Animasi (mati otomatis bila "kurangi gerakan" aktif) |
| `assets/common.js` | `api()` dengan token CSRF, format tanggal, toast, indikator kata sandi |
| `assets/ikon.js` | Set ikon garis (sama dengan TUMBUH) |
| `backend/config.php` | Koneksi DB, sesi, CSRF, helper |
| `backend/mailer.php` | Pengiriman dan templat email (PHPMailer) |
| `backend/api/` | Endpoint JSON |
| `backend/vendor/` | PHPMailer (disertakan agar tidak perlu Composer di server) |
