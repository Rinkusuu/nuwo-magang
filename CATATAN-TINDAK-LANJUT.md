# Catatan Tindak Lanjut — Portal Magang Mahasiswa

Daftar pekerjaan yang **sengaja ditunda** atau **menunggu data/keputusan**, supaya
pengerjaan berikutnya jelas mulai dari mana. Diperbarui 30 September 2026.

---

## A. Ditunda atas keputusan pengguna

- [ ] **Menu backoffice berlabel "Segera"** (poin 6). Empat menu belum ada isinya:
      Dasbor, Penempatan dan Pembimbing, Sertifikat, Log Aktivitas. Pilihannya:
      sembunyikan dulu, atau bangun. (Logbook kini di menu Peserta PKL; Pengguna dan
      Hak Akses sudah dibangun 12 Oktober 2026.)
- [ ] **Pratinjau tautan WhatsApp / media sosial (Open Graph).** Tag `og:title`,
      `og:description`, `og:image` (gambar 1200×630) di beranda, supaya tautan yang
      dibagikan tampil sebagai kartu bergambar, bukan teks polos.

## B. Menunggu data dari pengguna

- [ ] **Foto** gedung KPwBI Provinsi Lampung atau kegiatan magang angkatan
      sebelumnya, beserta kepastian boleh dipublikasikan.
- [ ] **Kontak di footer beranda**: alamat kantor, telepon, email, jam layanan,
      akun media sosial resmi. Kolomnya sengaja belum dibuat supaya tidak menebak.
- [ ] **Format hak cipta resmi** di footer. Saat ini tertulis
      "© 2026 Kantor Perwakilan Bank Indonesia Provinsi Lampung".
- [ ] **Data alumni / testimoni** bila ingin ditampilkan (jangan dikarang).

## C. Wajib sebelum portal online

- [ ] **Akun SMTP resmi** (host, port, user, kata sandi, alamat pengirim) diisi di
      `backend/config.local.php`. Selama kosong, email hanya dicatat di
      `backend/logs/email.log` dan pelamar tidak menerima apa pun.
      Setelah diisi: uji kirim nyata, pastikan domain pengirim punya SPF/DKIM agar
      tidak masuk spam.
- [ ] **Domain dan `APP_URL`**. Tanpa `APP_URL`, tautan di email (termasuk tautan
      atur ulang kata sandi) tidak lengkap.
- [ ] **Hosting**: disarankan VPS dengan Nginx + PHP-FPM seperti rencana TUMBUH;
      contoh konfigurasi di `deploy/nginx-portal-magang.conf`. Pasang HTTPS, lalu
      aktifkan HSTS (untuk Apache, hapus tanda komentar di `.htaccess`).
- [ ] **User database khusus**, bukan `root`, dengan kata sandi kuat.
- [ ] **Ganti kata sandi admin contoh** (`AdminBI2026` tertulis di `setup.sql`):
      `php backend/atur_password_admin.php admin@bi.go.id`
- [ ] **Hapus data contoh** dari `setup.sql` sebelum dipakai di server produksi:
      pelamar Rahmat, Siti, Dewi (kata sandi `Demo1234`) beserta pendaftaran dan berkasnya.
- [ ] **Portal dipasang di akar domain** (mis. `https://magang.contoh.go.id/`).
      Halaman 404 dan aturan `.htaccess` mengandalkan itu; bila dipasang di subfolder,
      path di `404.html` dan `.htaccess` perlu disesuaikan.

## D. Disarankan setelah online

- [ ] **Uji beban** di server sungguhan (pelajaran dari TUMBUH dan lampungbegawi.id),
      terutama menjelang tanggal tutup pendaftaran.
- [ ] **Pemantau uptime**, **backup harian** database dan folder `backend/uploads/`,
      **perpanjangan otomatis** domain dan hosting.
- [ ] **Email dikirim langsung saat tindakan terjadi** (batas tunggu 10 detik). Kalau
      SMTP ternyata lambat, pindahkan ke antrean yang dikirim terpisah.
- [ ] **CSP masih mengizinkan skrip inline** (`'unsafe-inline'`) karena tiap halaman
      memuat skrip di dalam HTML. Memindahkan skrip ke berkas `.js` terpisah
      memungkinkan CSP yang lebih ketat.

---

## Sudah selesai (untuk rujukan)

- Perombakan tampilan (opsi A, susunan sesuai prototype), motif jalur seleksi,
  animasi halus, footer, logo dan rasio aset untuk HP.
- Email notifikasi: pendaftaran terkirim, hasil verifikasi (lolos / perbaikan /
  tidak memenuhi), jadwal wawancara (baru / berubah), pengumuman hasil.
- Lupa dan atur ulang kata sandi (token hash, berlaku 60 menit, sekali pakai).
- Keamanan: CSRF, pembatasan percobaan login / daftar / lupa sandi, sesi baru setelah
  login, cookie HttpOnly + SameSite (+ Secure di HTTPS), pesan galat umum, kredensial di
  `config.local.php`, pemblokiran berkas sensitif, header keamanan, zona waktu WIB.
- Performa: font judul dipangkas 122 KB → 35 KB, logo WebP (31,7 KB → 13 KB), judul hero
  langsung terlihat (LCP tidak lagi tertunda animasi), data periode di-preload.
- Interaktivitas: menu HP, penanda bagian aktif, tombol Daftar menempel di HP, unggah
  seret-dan-lepas dengan bilah progres, pratinjau PDF dalam dialog, validasi per kolom,
  dialog konfirmasi bergaya portal, tabel pelamar bisa diurutkan dan dibagi halaman,
  kerangka saat memuat, kartu bidang seimbang dengan ikon.
- Hitung mundur penutupan pendaftaran, halaman 404, ikon layar utama, bukti
  pendaftaran siap cetak, indikator kekuatan kata sandi, cek format email,
  peringatan draf belum tersimpan, font disimpan di server sendiri.
