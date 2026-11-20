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
  foto VARCHAR(64) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  peran ENUM('admin','pewawancara') NOT NULL DEFAULT 'admin',
  aktif TINYINT(1) NOT NULL DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE pengaturan (
  id INT PRIMARY KEY,
  hari_perbaikan TINYINT NOT NULL DEFAULT 3,
  kantor_lat DECIMAL(10,7) NULL,
  kantor_lng DECIMAL(10,7) NULL,
  radius_m SMALLINT NOT NULL DEFAULT 150,
  akurasi_maks_m SMALLINT NOT NULL DEFAULT 100,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE bidang (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(150) NOT NULL,
  singkat VARCHAR(80) NOT NULL,
  deskripsi VARCHAR(500) NULL,
  urutan TINYINT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE batch (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nama VARCHAR(100) NOT NULL,
  daftar_buka DATE NOT NULL,
  daftar_tutup DATE NOT NULL,
  mulai DATE NOT NULL,
  selesai DATE NOT NULL,
  wawancara_tanggal DATE NULL,
  wawancara_jam TIME NULL,
  link_zoom VARCHAR(500) NULL,
  link_wa VARCHAR(500) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_daftar (daftar_buka, daftar_tutup)
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
  batch_id INT NULL,
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
  status_seleksi ENUM('lolos','tidak_lolos') NULL,
  seleksi_at TIMESTAMP NULL,
  seleksi_by INT NULL,
  hasil_akhir ENUM('diterima','tidak_diterima') NULL,
  bidang_penempatan_id INT NULL,
  decided_at TIMESTAMP NULL,
  kesediaan ENUM('bersedia','tidak_bersedia') NULL,
  kesediaan_at TIMESTAMP NULL,
  
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_app_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_app_bidang1 FOREIGN KEY (bidang_utama_id) REFERENCES bidang(id) ON DELETE SET NULL,
  CONSTRAINT fk_app_bidang2 FOREIGN KEY (bidang_alternatif_id) REFERENCES bidang(id) ON DELETE SET NULL,
  CONSTRAINT fk_app_bidang3 FOREIGN KEY (bidang_penempatan_id) REFERENCES bidang(id) ON DELETE SET NULL,
  CONSTRAINT fk_app_admin FOREIGN KEY (verified_by) REFERENCES admins(id) ON DELETE SET NULL,
  CONSTRAINT fk_app_batch FOREIGN KEY (batch_id) REFERENCES batch(id),
  CONSTRAINT fk_app_seleksi FOREIGN KEY (seleksi_by) REFERENCES admins(id) ON DELETE SET NULL,
  INDEX idx_status_admin (status_admin),
  INDEX idx_batch (batch_id)
) ENGINE=InnoDB;

CREATE TABLE application_files (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  jenis ENUM('cv','motivation','pengantar','transkrip','ktm') NOT NULL,
  stored_name VARCHAR(100) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  size INT NOT NULL,
  status_verifikasi ENUM('sesuai','perbaikan','tidak_sesuai') NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_app_jenis (application_id, jenis),
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE berkas_pkl (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  jenis ENUM('laporan') NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  size INT NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_app_jenis (application_id, jenis),
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE absen (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  tanggal DATE NOT NULL,
  jenis ENUM('datang','pulang') NOT NULL,
  waktu DATETIME NOT NULL,
  lat DECIMAL(10,7) NULL,
  lng DECIMAL(10,7) NULL,
  akurasi_m SMALLINT NULL,
  jarak_m INT NULL,
  foto VARCHAR(255) NULL,
  ip VARCHAR(45) NULL,
  user_agent VARCHAR(255) NULL,
  dikoreksi_by INT NULL,
  catatan_koreksi VARCHAR(500) NULL,
  dikoreksi_at TIMESTAMP NULL,
  UNIQUE KEY uq_absen_hari (application_id, tanggal, jenis),
  INDEX idx_tanggal (tanggal),
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
  FOREIGN KEY (dikoreksi_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE absen_token (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  jenis ENUM('datang','pulang') NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  kedaluwarsa DATETIME NOT NULL,
  dipakai_at DATETIME NULL,
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;

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

CREATE TABLE pembersihan_log (
  id INT AUTO_INCREMENT PRIMARY KEY,
  waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  kategori VARCHAR(40) NOT NULL,
  jumlah INT NOT NULL
) ENGINE=InnoDB;

CREATE TABLE interview_assessments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  admin_id INT NOT NULL,
  skor TEXT NOT NULL,          -- JSON: [s1..s7], nilai 1-5 atau null
  catatan_aspek TEXT NULL,     -- JSON: [c1..c7]
  rekomendasi ENUM('sangat','direkomendasikan','tidak') NULL,
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

CREATE TABLE IF NOT EXISTS batas_percobaan (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  kunci VARCHAR(190) NOT NULL,
  ip VARCHAR(45) NOT NULL,
  waktu TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_kunci_waktu (kunci, waktu),
  INDEX idx_ip_waktu (ip, waktu)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS reset_sandi (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  token_hash CHAR(64) NOT NULL UNIQUE,
  kedaluwarsa DATETIME NOT NULL,
  dibuat TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO pengaturan (id, hari_perbaikan) VALUES (1, 3);

INSERT INTO batch (nama, daftar_buka, daftar_tutup, mulai, selesai) VALUES
('Periode 1 2027', '2027-01-01', '2027-01-20', '2027-02-01', '2027-03-31'),
('Periode 2 2027', '2027-03-01', '2027-03-20', '2027-04-01', '2027-05-31'),
('Periode 3 2027', '2027-05-01', '2027-05-20', '2027-06-01', '2027-07-30'),
('Periode 4 2027', '2027-07-01', '2027-07-20', '2027-08-02', '2027-09-30'),
('Periode 5 2027', '2027-09-01', '2027-09-20', '2027-10-01', '2027-11-30');

INSERT INTO bidang (nama, singkat, deskripsi, urutan) VALUES
('Asesmen Ekonomi dan Keuangan', 'Asesmen Ekonomi', 'Membantu pemantauan dan analisis perekonomian daerah, inflasi, serta stabilitas sistem keuangan. Relevan untuk Ekonomi Pembangunan, Statistika, Manajemen, dan Akuntansi.', 1),
('Sistem Pembayaran dan Pengelolaan Uang Rupiah', 'Sistem Pembayaran', 'Mendukung pengawasan sistem pembayaran, digitalisasi transaksi, serta pengelolaan uang Rupiah. Relevan untuk Ekonomi, Sistem Informasi, dan Sains Data.', 2),
('Pengembangan UMKM dan Ekonomi Syariah', 'UMKM dan Ekonomi Syariah', 'Mendukung program pengembangan UMKM binaan dan ekonomi-keuangan syariah. Relevan untuk Ekonomi Syariah, Manajemen, dan Agribisnis.', 3),
('Komunikasi dan Hubungan Masyarakat', 'Komunikasi dan Humas', 'Membantu publikasi, kehumasan, dan edukasi kebanksentralan kepada masyarakat. Relevan untuk Ilmu Komunikasi dan Desain Komunikasi Visual.', 4),
('Manajemen Intern', 'Manajemen Intern', 'Mendukung administrasi, logistik, keuangan internal, dan sumber daya manusia. Relevan untuk Akuntansi, Manajemen, Hukum, dan Administrasi.', 5);
