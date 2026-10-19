-- Logbook harian teks dan catatan pembersihan akun (16 Oktober 2026). Jalankan sekali setelah 005.

-- Logbook harian peserta PKL: wajib diisi sebelum absen pulang, terkunci setelahnya
CREATE TABLE logbook (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  tanggal DATE NOT NULL,
  isi TEXT NOT NULL,
  dibuat_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  diubah_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_logbook_hari (application_id, tanggal),
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Ringkasan pembersihan akun pelamar yang tidak lolos (tanpa nama atau email)
CREATE TABLE pembersihan_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  kategori VARCHAR(40) NOT NULL,
  jumlah INT NOT NULL
) ENGINE=InnoDB;
