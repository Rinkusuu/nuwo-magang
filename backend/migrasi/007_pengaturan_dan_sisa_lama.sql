RENAME TABLE periode TO pengaturan;
ALTER TABLE pengaturan DROP COLUMN tahun, DROP COLUMN tanggal_buka, DROP COLUMN tanggal_tutup, DROP COLUMN durasi, DROP COLUMN kuota;

-- gagal bila masih ada data lama yang memakai nilai ini; periksa dulu sebelum menghapusnya
ALTER TABLE berkas_pkl MODIFY jenis ENUM('laporan') NOT NULL;
ALTER TABLE interview_assessments MODIFY rekomendasi ENUM('sangat','direkomendasikan','tidak') NULL;
