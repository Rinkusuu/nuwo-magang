# Portal Magang Mahasiswa — KPwBI Provinsi Lampung

Pendaftaran, seleksi, dan pengumuman Program Magang Mahasiswa S1.
Frontend HTML/CSS/JavaScript tanpa proses build, backend PHP 8 + MySQL/MariaDB.

## Menjalankan di laptop

```bash
mysql -uroot < setup.sql                       # membuat ulang database magang_bi + data contoh
cp backend/config.local.example.php backend/config.local.php   # lalu sesuaikan DB_USER/DB_PASS
php -d upload_max_filesize=11M -d post_max_size=12M -S 0.0.0.0:8740 deploy/router-lokal.php   # buka http://127.0.0.1:8740
```

Akun contoh: pelamar `rahmat@email.com` / `Demo1234`, admin `admin@bi.go.id` / `AdminBI2026`.
Selama `SMTP_HOST` kosong, email tidak dikirim melainkan dicatat di `backend/logs/email.log`.

> `php -S` tidak membaca `.htaccess`. Karena itu server uji dijalankan bersama
> `deploy/router-lokal.php`, yang meniru aturannya: `/` membuka beranda, alamat salah
> menampilkan 404, dan berkas sensitif diblokir. Dari HP di Wi-Fi yang sama, buka
> `http://<IP laptop>:8740` (cek IP dengan `ipconfig getifaddr en0`).

## Memasang di server

1. Salin seluruh folder ke server (portal diasumsikan di akar domain).
2. Buat database dan user khusus, lalu impor `setup.sql` (hapus data contoh terlebih dahulu).
   Database yang sudah ada sebelumnya: jalankan berkas di `backend/migrasi/` secara berurutan (001, 002, …).
3. Buat `backend/config.local.php` dari contohnya: `APP_URL`, kredensial database, SMTP.
4. Ganti kata sandi admin: `php backend/atur_password_admin.php admin@bi.go.id`.
   Akun pewawancara dan admin lain dibuat dari menu **Pengguna dan Hak Akses** di backoffice.
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
