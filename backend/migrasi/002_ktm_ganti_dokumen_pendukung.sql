-- Permintaan kantor (2 Oktober 2026): KTP, NPWP, dan Buku Tabungan diganti KTM.
-- Jalankan sekali pada database yang dibuat sebelum perubahan ini.
-- Berkas "pendukung" lama tetap tersimpan sebagai KTM supaya tidak ada data hilang;
-- tim seleksi dapat meminta perbaikan bila isinya bukan KTM.

ALTER TABLE application_files MODIFY jenis ENUM('cv','motivation','pengantar','transkrip','pendukung','ktm') NOT NULL;
UPDATE application_files SET jenis = 'ktm' WHERE jenis = 'pendukung';
ALTER TABLE application_files MODIFY jenis ENUM('cv','motivation','pengantar','transkrip','ktm') NOT NULL;
