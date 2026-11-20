
ALTER TABLE periode
  ADD COLUMN kantor_lat DECIMAL(10,7) NULL,
  ADD COLUMN kantor_lng DECIMAL(10,7) NULL,
  ADD COLUMN radius_m SMALLINT NOT NULL DEFAULT 150,
  ADD COLUMN akurasi_maks_m SMALLINT NOT NULL DEFAULT 100;

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
