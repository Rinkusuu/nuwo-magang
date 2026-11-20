# Nuwo Magang

Portal pendaftaran dan pengelolaan magang mahasiswa S1 di Kantor Perwakilan Bank Indonesia Provinsi Lampung: pendaftaran, verifikasi berkas, wawancara, pengumuman, lalu absen harian dan logbook selama magang.

HTML, CSS, dan JavaScript biasa tanpa build. Backend PHP 8 dengan MySQL/MariaDB. Email lewat PHPMailer, yang ikut disimpan di `backend/vendor/` supaya server tidak perlu Composer.

## Jalan di laptop

```bash
mysql -uroot < setup.sql
mysql -uroot < contoh_data.sql
cp backend/config.local.example.php backend/config.local.php
php -d upload_max_filesize=11M -d post_max_size=12M -S 0.0.0.0:8740 deploy/router-lokal.php
```

Buka http://127.0.0.1:8740. Admin `admin@bi.go.id` / `AdminBI2026`, pelamar `rahmat@email.com` / `Demo1234`. Backoffice ada di `/login_admin.html`.

Selama `SMTP_HOST` kosong, email tidak dikirim dan hanya ditulis ke `backend/logs/email.log`.

## Pasang di server

1. Salin repo ke akar domain. Kalau dipasang di subfolder, path di `404.html` dan `.htaccess` perlu diubah.
2. Buat database dan user khusus (jangan `root`), lalu impor `setup.sql`. Jangan impor `contoh_data.sql`.
3. Buat `backend/config.local.php` dari contohnya, lalu isi `APP_URL`, database, dan SMTP.
4. Buat admin pertama: `php backend/atur_password_admin.php email@bi.go.id "Nama"`. Akun admin dan pewawancara lain dibuat dari menu Pengguna dan Hak Akses.
5. Pastikan `backend/uploads/` dan `backend/logs/` bisa ditulis PHP.
6. Web server: Apache cukup dengan `.htaccess` (butuh `mod_headers` dan `mod_alias`). Untuk Nginx + PHP-FPM, lihat `deploy/nginx-portal-magang.conf`.
7. Pasang HTTPS. Kamera dan GPS untuk absen hanya jalan di HTTPS.
8. Cron harian untuk menghapus akun pelamar yang tidak lolos (H+7 setelah keputusan):

   ```
   15 1 * * * php /path/nuwo-magang/backend/bersihkan_akun.php >> /path/nuwo-magang/backend/logs/pembersihan.log 2>&1
   ```

   `php backend/bersihkan_akun.php --uji` menampilkan daftarnya tanpa menghapus.

Database yang dibuat dari versi lama diperbarui dengan menjalankan `backend/migrasi/` secara berurutan.

## Sebelum online

- Atur titik kantor, radius, dan akurasi GPS di Batch dan Jadwal → Pengaturan umum. Selama kosong, peserta tidak bisa absen.
- Isi SMTP resmi dan pastikan domain pengirim punya SPF/DKIM.
- Kalau di belakang Cloudflare atau proxy lain, atur `set_real_ip_from` dan `real_ip_header` di Nginx. Tanpa itu semua pengunjung terbaca ber-IP sama, dan batas login per IP mengunci semua orang sekaligus.
- Aktifkan HSTS setelah HTTPS stabil (untuk Apache, baris yang dikomentari di `.htaccess`).
- Siapkan backup harian untuk database dan `backend/uploads/`.

## Struktur

| Lokasi | Isi |
| --- | --- |
| `*.html` | Halaman pelamar (`dashboard`, `formulir`, `status_pengajuan`, `absen`, ...) dan backoffice (`admin_*`) |
| `assets/` | `portal.css`, `common.js` (fetch dengan CSRF, toast, dialog), `admin.js` (sidebar dan cek peran), `ikon.js`, `gerak.js` |
| `backend/api/` | Endpoint JSON. Hanya folder ini yang menjalankan PHP dari web |
| `backend/config.php` | Koneksi database, sesi, CSRF, helper umum |
| `backend/mailer.php` | Templat dan pengiriman email |
| `backend/logbook_excel.php` | Excel logbook format kantor, ditulis dengan `xlsx_sederhana.php` |
| `backend/migrasi/` | Perubahan skema untuk database lama |
| `deploy/` | Contoh konfigurasi Nginx dan router untuk `php -S` |
