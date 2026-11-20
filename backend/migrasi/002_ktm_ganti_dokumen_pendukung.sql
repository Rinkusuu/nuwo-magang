
ALTER TABLE application_files MODIFY jenis ENUM('cv','motivation','pengantar','transkrip','pendukung','ktm') NOT NULL;
UPDATE application_files SET jenis = 'ktm' WHERE jenis = 'pendukung';
ALTER TABLE application_files MODIFY jenis ENUM('cv','motivation','pengantar','transkrip','ktm') NOT NULL;
