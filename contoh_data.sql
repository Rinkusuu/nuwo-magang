-- Data contoh untuk laptop, jangan diimpor di server. Admin: admin@bi.go.id / AdminBI2026, pelamar: Demo1234.
USE magang_bi;

INSERT INTO admins (nama, email, password) VALUES
('Admin Seleksi', 'admin@bi.go.id', '$2y$12$hL5kVeYAwz7HvJOG0jWXmufBCNn5MCbtG2wx0/VMTtC3GnozxXgvW');

INSERT INTO users (nama, email, password, no_wa) VALUES
('Rahmat Hidayat', 'rahmat@email.com', '$2y$12$QtviQDqeHCkfSibYoxhBhOqVH.SyTMJbMk2KexH0h0WGX7/7XGT6G', '0812-3456-7890'),
('Siti Aisyah', 'siti@email.com', '$2y$12$QtviQDqeHCkfSibYoxhBhOqVH.SyTMJbMk2KexH0h0WGX7/7XGT6G', '0813-2211-4455'),
('Dewi Anggraini', 'dewi@email.com', '$2y$12$QtviQDqeHCkfSibYoxhBhOqVH.SyTMJbMk2KexH0h0WGX7/7XGT6G', '0857-9988-1122');

INSERT INTO applications
(user_id, no_pendaftaran, nama, email, no_wa, domisili, universitas, fakultas_prodi, nim, ipk, jenjang, semester,
 batch_id, pernyataan, submitted_at,
 syarat_s1, syarat_semester, syarat_aktif, status_admin, verified_at, verified_by,
 status_seleksi, seleksi_at, seleksi_by)
VALUES
(1, 'MG-2026-0001', 'Rahmat Hidayat', 'rahmat@email.com', '0812-3456-7890', 'Bandar Lampung',
 'Universitas Lampung', 'Fakultas Ekonomi dan Bisnis / Ekonomi Pembangunan', '2211021001', 3.52, 'S1', 6,
 1, 1, NOW(),
 'memenuhi', 'memenuhi', 'memenuhi', 'memenuhi', NOW(), 1,
 'lolos', NOW(), 1);

INSERT INTO applications
(user_id, no_pendaftaran, nama, email, no_wa, domisili, universitas, fakultas_prodi, nim, ipk, jenjang, semester,
 batch_id, pernyataan, submitted_at)
VALUES
(2, 'MG-2026-0002', 'Siti Aisyah', 'siti@email.com', '0813-2211-4455', 'Lampung Selatan',
 'Institut Teknologi Sumatera', 'Fakultas Sains / Sains Data', '122450011', 3.61, 'S1', 5,
 1, 1, NOW());

INSERT INTO application_files (application_id, jenis, stored_name, original_name, size, status_verifikasi) VALUES
(1, 'cv', 'contoh.pdf', 'CV_Rahmat_Hidayat.pdf', 600, 'sesuai'),
(1, 'motivation', 'contoh.pdf', 'Motivation_Letter.pdf', 600, 'sesuai'),
(1, 'pengantar', 'contoh.pdf', 'Surat_Pengantar.pdf', 600, 'sesuai'),
(1, 'transkrip', 'contoh.pdf', 'Transkrip.pdf', 600, 'sesuai'),
(1, 'ktm', 'contoh.pdf', 'KTM_Rahmat_Hidayat.pdf', 600, 'sesuai'),
(2, 'cv', 'contoh.pdf', 'CV_Siti_Aisyah.pdf', 600, NULL),
(2, 'motivation', 'contoh.pdf', 'Motivation_Letter.pdf', 600, NULL),
(2, 'pengantar', 'contoh.pdf', 'Surat_Pengantar.pdf', 600, NULL),
(2, 'transkrip', 'contoh.pdf', 'Transkrip.pdf', 600, NULL),
(2, 'ktm', 'contoh.pdf', 'KTM_Siti_Aisyah.pdf', 600, NULL);
