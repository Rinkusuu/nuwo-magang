
ALTER TABLE admins
  ADD COLUMN peran ENUM('admin','pewawancara') NOT NULL DEFAULT 'admin' AFTER password,
  ADD COLUMN aktif TINYINT(1) NOT NULL DEFAULT 1 AFTER peran;
