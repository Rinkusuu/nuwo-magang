-- ============================================
-- DATABASE: Portal Magang Mahasiswa
-- Kantor Perwakilan Bank Indonesia Provinsi Lampung
-- ============================================
-- PERHATIAN: skrip ini menghapus lalu membuat ulang database magang_bi.
DROP DATABASE IF EXISTS magang_bi;
CREATE DATABASE magang_bi CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE magang_bi;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  no_wa VARCHAR(20),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Satu baris pengaturan periode yang sedang berjalan (id = 1)
CREATE TABLE periode (
  id INT PRIMARY KEY,
  tahun SMALLINT NOT NULL,
  tanggal_buka DATE NULL,
  tanggal_tutup DATE NULL,
  durasi VARCHAR(100) NULL,
  kuota INT NULL,
  hari_perbaikan TINYINT NOT NULL DEFAULT 3,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bidang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  singkat VARCHAR(80) NOT NULL,
  deskripsi VARCHAR(500) NULL,
  urutan TINYINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE applications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL UNIQUE,
  no_pendaftaran VARCHAR(20) NULL UNIQUE,
  nama VARCHAR(150) NULL,
  email VARCHAR(150) NULL,
  no_wa VARCHAR(20) NULL,
  domisili VARCHAR(100) NULL,
  universitas VARCHAR(150) NULL,
  fakultas_prodi VARCHAR(200) NULL,
  nim VARCHAR(30) NULL,
  ipk DECIMAL(3,2) NULL,
  jenjang ENUM('S1','D3','D4','S2') NULL,
  semester TINYINT NULL,
  bidang_utama_id INT NULL,
  bidang_alternatif_id INT NULL,
  tanggal_mulai DATE NULL,
  tanggal_selesai DATE NULL,
  pernyataan TINYINT(1) NOT NULL DEFAULT 0,
  submitted_at TIMESTAMP NULL,
  syarat_s1 ENUM('memenuhi','tidak') NULL,
  syarat_semester ENUM('memenuhi','tidak') NULL,
  syarat_aktif ENUM('memenuhi','tidak') NULL,
  status_admin ENUM('menunggu','memenuhi','perbaikan','tidak_memenuhi') NOT NULL DEFAULT 'menunggu',
  catatan_verifikasi TEXT NULL,
  batas_perbaikan DATE NULL,
  verified_at TIMESTAMP NULL,
  verified_by INT NULL,
  jadwal_tanggal DATE NULL,
  jadwal_jam TIME NULL,
  jadwal_lokasi VARCHAR(255) NULL,
  hasil_akhir ENUM('diterima','cadangan','tidak_diterima') NULL,
  bidang_penempatan_id INT NULL,
  decided_at TIMESTAMP NULL,
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_app_bidang1 FOREIGN KEY (bidang_utama_id) REFERENCES bidang(id) ON DELETE SET NULL,
  CONSTRAINT fk_app_bidang2 FOREIGN KEY (bidang_alternatif_id) REFERENCES bidang(id) ON DELETE SET NULL,
  CONSTRAINT fk_app_bidang3 FOREIGN KEY (bidang_penempatan_id) REFERENCES bidang(id) ON DELETE SET NULL,
  CONSTRAINT fk_app_admin FOREIGN KEY (verified_by) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_status_admin (status_admin)
) ENGINE=InnoDB;

-- Lima berkas administrasi (PDF, maks. 2 MB)
CREATE TABLE application_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  jenis ENUM('cv','motivation','pengantar','transkrip','pendukung') NOT NULL,
  stored_name VARCHAR(100) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  size INT NOT NULL,
  status_verifikasi ENUM('sesuai','perbaikan','tidak_sesuai') NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_app_jenis (application_id, jenis),
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Penilaian wawancara: satu baris per pewawancara per pelamar
CREATE TABLE interview_assessments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  admin_id INT NOT NULL,
  skor TEXT NOT NULL,          -- JSON: [s1..s7], nilai 1-5 atau null
  catatan_aspek TEXT NULL,     -- JSON: [c1..c7]
  rekomendasi ENUM('sangat','direkomendasikan','cadangan','tidak') NULL,
  catatan TEXT NULL,
  nilai_akhir DECIMAL(4,2) NULL,
  lengkap TINYINT(1) NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_app_admin (application_id, admin_id),
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE application_status_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  status_from VARCHAR(30),
  status_to VARCHAR(30) NOT NULL,
  changed_by INT,
  note VARCHAR(255),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  FOREIGN KEY (changed_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Pembatasan percobaan login, pendaftaran akun, dan lupa kata sandi
CREATE TABLE IF NOT EXISTS batas_percobaan (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kunci VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kunci_waktu (kunci, waktu),
  INDEX idx_ip_waktu (ip, waktu)
) ENGINE=InnoDB;

-- Token atur ulang kata sandi (disimpan sebagai hash SHA-256, berlaku 60 menit)
CREATE TABLE IF NOT EXISTS reset_sandi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  kedaluwarsa DATETIME NOT NULL,
  dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- DATA AWAL
-- ============================================

-- Kata sandi admin contoh: AdminBI2026 — WAJIB diganti sebelum online:
--   php backend/atur_password_admin.php admin@bi.go.id
INSERT INTO admins (nama, email, password) VALUES
('Admin Seleksi', 'admin@bi.go.id', '$2y$12$hL5kVeYAwz7HvJOG0jWXmufBCNn5MCbtG2wx0/VMTtC3GnozxXgvW');

INSERT INTO periode (id, tahun, tanggal_buka, tanggal_tutup, durasi, kuota, hari_perbaikan) VALUES
(1, 2026, '2026-09-01', '2026-10-31', '3 bulan', 20, 3);

INSERT INTO bidang (nama, singkat, deskripsi, urutan) VALUES
('Asesmen Ekonomi dan Keuangan', 'Asesmen Ekonomi', 'Membantu pemantauan dan analisis perekonomian daerah, inflasi, serta stabilitas sistem keuangan. Relevan untuk Ekonomi Pembangunan, Statistika, Manajemen, dan Akuntansi.', 1),
('Sistem Pembayaran dan Pengelolaan Uang Rupiah', 'Sistem Pembayaran', 'Mendukung pengawasan sistem pembayaran, digitalisasi transaksi, serta pengelolaan uang Rupiah. Relevan untuk Ekonomi, Sistem Informasi, dan Sains Data.', 2),
('Pengembangan UMKM dan Ekonomi Syariah', 'UMKM dan Ekonomi Syariah', 'Mendukung program pengembangan UMKM binaan dan ekonomi-keuangan syariah. Relevan untuk Ekonomi Syariah, Manajemen, dan Agribisnis.', 3),
('Komunikasi dan Hubungan Masyarakat', 'Komunikasi dan Humas', 'Membantu publikasi, kehumasan, dan edukasi kebanksentralan kepada masyarakat. Relevan untuk Ilmu Komunikasi dan Desain Komunikasi Visual.', 4),
('Manajemen Intern', 'Manajemen Intern', 'Mendukung administrasi, logistik, keuangan internal, dan sumber daya manusia. Relevan untuk Akuntansi, Manajemen, Hukum, dan Administrasi.', 5);

-- Akun pelamar contoh, kata sandi: Demo1234
INSERT INTO users (nama, email, password, no_wa) VALUES
('Rahmat Hidayat', 'rahmat@email.com', '$2y$12$QtviQDqeHCkfSibYoxhBhOqVH.SyTMJbMk2KexH0h0WGX7/7XGT6G', '0812-3456-7890'),
('Siti Aisyah', 'siti@email.com', '$2y$12$QtviQDqeHCkfSibYoxhBhOqVH.SyTMJbMk2KexH0h0WGX7/7XGT6G', '0813-2211-4455'),
('Dewi Anggraini', 'dewi@email.com', '$2y$12$QtviQDqeHCkfSibYoxhBhOqVH.SyTMJbMk2KexH0h0WGX7/7XGT6G', '0857-9988-1122');

INSERT INTO applications
(user_id, no_pendaftaran, nama, email, no_wa, domisili, universitas, fakultas_prodi, nim, ipk, jenjang, semester,
 bidang_utama_id, bidang_alternatif_id, tanggal_mulai, tanggal_selesai, pernyataan, submitted_at,
 syarat_s1, syarat_semester, syarat_aktif, status_admin, verified_at, verified_by,
 jadwal_tanggal, jadwal_jam, jadwal_lokasi)
VALUES
(1, 'MG-2026-0001', 'Rahmat Hidayat', 'rahmat@email.com', '0812-3456-7890', 'Bandar Lampung',
 'Universitas Lampung', 'Fakultas Ekonomi dan Bisnis / Ekonomi Pembangunan', '2211021001', 3.52, 'S1', 6,
 1, 4, '2027-01-04', '2027-03-31', 1, NOW(),
 'memenuhi', 'memenuhi', 'memenuhi', 'memenuhi', NOW(), 1,
 '2026-10-12', '09:00:00', 'Ruang Rapat Lt. 2 KPwBI Provinsi Lampung');

INSERT INTO applications
(user_id, no_pendaftaran, nama, email, no_wa, domisili, universitas, fakultas_prodi, nim, ipk, jenjang, semester,
 bidang_utama_id, bidang_alternatif_id, tanggal_mulai, tanggal_selesai, pernyataan, submitted_at)
VALUES
(2, 'MG-2026-0002', 'Siti Aisyah', 'siti@email.com', '0813-2211-4455', 'Lampung Selatan',
 'Institut Teknologi Sumatera', 'Fakultas Sains / Sains Data', '122450011', 3.61, 'S1', 5,
 2, 1, '2027-01-04', '2027-03-31', 1, NOW());



-- Berkas contoh (menunjuk ke backend/uploads/contoh.pdf)
INSERT INTO application_files (application_id, jenis, stored_name, original_name, size, status_verifikasi) VALUES
(1, 'cv', 'contoh.pdf', 'CV_Rahmat_Hidayat.pdf', 600, 'sesuai'),
(1, 'motivation', 'contoh.pdf', 'Motivation_Letter.pdf', 600, 'sesuai'),
(1, 'pengantar', 'contoh.pdf', 'Surat_Pengantar.pdf', 600, 'sesuai'),
(1, 'transkrip', 'contoh.pdf', 'Transkrip.pdf', 600, 'sesuai'),
(1, 'pendukung', 'contoh.pdf', 'KTP_NPWP_Tabungan.pdf', 600, 'sesuai'),
(2, 'cv', 'contoh.pdf', 'CV_Siti_Aisyah.pdf', 600, NULL),
(2, 'motivation', 'contoh.pdf', 'Motivation_Letter.pdf', 600, NULL),
(2, 'pengantar', 'contoh.pdf', 'Surat_Pengantar.pdf', 600, NULL),
(2, 'transkrip', 'contoh.pdf', 'Transkrip.pdf', 600, NULL),
(2, 'pendukung', 'contoh.pdf', 'KTP_NPWP_Tabungan.pdf', 600, NULL);
