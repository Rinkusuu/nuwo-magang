-- Peran akun backoffice (12 Oktober 2026): admin (akses penuh) dan pewawancara
-- (hanya daftar pelamar yang lolos ke wawancara, rubrik, dan penilaian). Jalankan sekali setelah 003.

ALTER TABLE admins
  ADD COLUMN peran ENUM('admin','pewawancara') NOT NULL DEFAULT 'admin' AFTER password,
  ADD COLUMN aktif TINYINT(1) NOT NULL DEFAULT 1 AFTER peran;
