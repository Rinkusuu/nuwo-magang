
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

INSERT INTO batch (nama, daftar_buka, daftar_tutup, mulai, selesai) VALUES
('Periode 1 2027', '2027-01-01', '2027-01-20', '2027-02-01', '2027-03-31'),
('Periode 2 2027', '2027-03-01', '2027-03-20', '2027-04-01', '2027-05-31'),
('Periode 3 2027', '2027-05-01', '2027-05-20', '2027-06-01', '2027-07-30'),
('Periode 4 2027', '2027-07-01', '2027-07-20', '2027-08-02', '2027-09-30'),
('Periode 5 2027', '2027-09-01', '2027-09-20', '2027-10-01', '2027-11-30');

ALTER TABLE applications
  ADD COLUMN batch_id INT NULL AFTER tanggal_selesai,
  ADD COLUMN status_seleksi ENUM('lolos','tidak_lolos') NULL AFTER jadwal_lokasi,
  ADD COLUMN seleksi_at TIMESTAMP NULL AFTER status_seleksi,
  ADD COLUMN seleksi_by INT NULL AFTER seleksi_at,
  ADD COLUMN kesediaan ENUM('bersedia','tidak_bersedia') NULL AFTER decided_at,
  ADD COLUMN kesediaan_at TIMESTAMP NULL AFTER kesediaan,
  ADD CONSTRAINT fk_app_batch FOREIGN KEY (batch_id) REFERENCES batch(id),
  ADD CONSTRAINT fk_app_seleksi FOREIGN KEY (seleksi_by) REFERENCES admins(id) ON DELETE SET NULL,
  ADD INDEX idx_batch (batch_id);

UPDATE applications SET hasil_akhir = NULL, decided_at = NULL WHERE hasil_akhir = 'cadangan';
ALTER TABLE applications MODIFY hasil_akhir ENUM('diterima','tidak_diterima') NULL;

CREATE TABLE berkas_pkl (
  id INT AUTO_INCREMENT PRIMARY KEY,
  application_id INT NOT NULL,
  jenis ENUM('logbook','laporan') NOT NULL,
  stored_name VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL,
  size INT NOT NULL,
  uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_app_jenis (application_id, jenis),
  FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE
) ENGINE=InnoDB;
