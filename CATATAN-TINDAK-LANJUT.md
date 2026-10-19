# Catatan Tindak Lanjut — Portal Magang Mahasiswa

Daftar pekerjaan yang **sengaja ditunda** atau **menunggu data/keputusan**, supaya
pengerjaan berikutnya jelas mulai dari mana. Diperbarui 19 Oktober 2026.

---

## A. Ditunda atas keputusan pengguna

- [ ] **Menu yang belum dibangun** (Dasbor, Penempatan dan Pembimbing, Sertifikat, Log
      Aktivitas) sudah dihapus dari sidebar pada 14 Oktober 2026. Bangun kembali bila diperlukan.
- [ ] **Pratinjau tautan WhatsApp / media sosial (Open Graph).** Tag `og:title`,
      `og:description`, `og:image` (gambar 1200×630) di beranda, supaya tautan yang
      dibagikan tampil sebagai kartu bergambar, bukan teks polos.

- [ ] **Ide lanjutan absen dan fitur peserta** (dibahas 14 Oktober 2026, belum dipilih).
      Rincian lengkap ada di rencana absen; ringkasnya:
      - *Anti-manipulasi tambahan:* tantangan acak saat selfie (mis. "tunjukkan 3 jari"),
        deteksi foto berulang, penanda GPS janggal / lompatan mustahil / satu perangkat banyak
        akun / absen dari komputer, galeri pemeriksaan admin (Sah/Tidak sah), pernyataan dan
        sanksi, penghapusan foto otomatis setelah PKL.
      - *Fitur besar:* dasbor peserta, izin/sakit, rekap kehadiran untuk kampus, logbook di web
        yang diunduh sebagai Excel, peran pembimbing lapangan, sertifikat dengan QR verifikasi,
        surat otomatis, pengumuman per batch, aplikasi terpasang (PWA) dengan notifikasi.
      - *Sampingan:* pengingat lupa absen pulang, info pakaian hari ini, peta dan kontak, kalender
        libur, hitung mundur, templat laporan, riwayat absen, ubah profil, survei akhir PKL.

- [ ] **Ide lanjutan logbook harian** (dibahas 16 Oktober 2026, belum dipilih): riwayat logbook
      peserta dengan tombol "salin kemarin", tanggapan/paraf pembimbing, rekap mingguan sebagai
      kerangka laporan akhir, penanda logbook asal-asalan atau salin-tempel, pencarian logbook di
      backoffice, kategori kegiatan, cetak PDF berkop, pengingat belum absen pulang.
- [ ] **Kolom Excel logbook yang dibiarkan kosong**: "Email Pembimbing" dan "Unit/Bagian" (belum ada
      di sistem). Bila nanti diisi admin per peserta, keduanya bisa langsung masuk ke Excel.

## B. Menunggu data dari pengguna

- [ ] **Foto** gedung KPwBI Provinsi Lampung atau kegiatan magang angkatan
      sebelumnya, beserta kepastian boleh dipublikasikan.
- [ ] **Kontak di footer beranda**: alamat kantor, telepon, email, jam layanan,
      akun media sosial resmi. Kolomnya sengaja belum dibuat supaya tidak menebak.
- [ ] **Format hak cipta resmi** di footer. Saat ini tertulis
      "© 2026 Kantor Perwakilan Bank Indonesia Provinsi Lampung".
- [ ] **Data alumni / testimoni** bila ingin ditampilkan (jangan dikarang).

## C. Wajib sebelum portal online

- [ ] **Atur titik kantor untuk absen** di Batch dan Jadwal → Pengaturan umum (paling mudah
      dari HP saat berada di kantor, tombol "Gunakan lokasi saya sekarang"). Selama kosong,
      peserta tidak bisa absen.
- [ ] **Absen butuh HTTPS.** Kamera dan GPS hanya diizinkan peramban di alamat HTTPS (atau
      localhost). Uji absen di HP sungguhan baru bisa setelah portal online dengan HTTPS.
      Keterbatasan yang perlu diketahui kantor: GPS dapat dipalsukan dengan aplikasi khusus;
      lapisan saat ini (kamera langsung, token sekali pakai, waktu server, stempel foto, radius,
      riwayat tidak bisa diubah) menyulitkan, tetapi tidak menjamin 100%.

- [ ] **Pasang cron pembersihan akun** (`backend/bersihkan_akun.php`, contoh baris di README).
      Tanpa cron, akun pelamar yang tidak lolos tidak pernah terhapus.
- [ ] **Jalankan migrasi `006`** di database server (tabel `logbook` dan `pembersihan_log`).

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

- Logbook harian teks sebagai syarat absen pulang (terkunci setelah absen pulang), Excel logbook
  otomatis sesuai format kantor dengan jam masuk/keluar dari absen, pembersihan akun pelamar yang
  tidak lolos H+7 lewat cron beserta pemberitahuan tanggal di halaman status.
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
