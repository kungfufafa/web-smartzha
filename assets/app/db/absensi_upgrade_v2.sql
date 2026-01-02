-- ============================================================
-- ABSENSI MODULE - UPGRADE MIGRATION v2.1
-- Description: Add missing fields and improve pengajuan integration
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- PART 1: ADD MISSING COLUMNS TO absensi_logs
-- ============================================================

-- Add lembur_menit column if not exists
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensi_logs' AND COLUMN_NAME = 'lembur_menit') = 0,
    'ALTER TABLE `absensi_logs` ADD COLUMN `lembur_menit` int(11) DEFAULT 0 COMMENT ''Overtime minutes'' AFTER `pulang_awal_menit`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add id_pengajuan column if not exists (link to pengajuan when status from leave request)
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensi_logs' AND COLUMN_NAME = 'id_pengajuan') = 0,
    'ALTER TABLE `absensi_logs` ADD COLUMN `id_pengajuan` int(11) DEFAULT NULL COMMENT ''Link to pengajuan if status from leave'' AFTER `bypass_id`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add index for id_pengajuan
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensi_logs' AND INDEX_NAME = 'idx_id_pengajuan') = 0,
    'ALTER TABLE `absensi_logs` ADD INDEX `idx_id_pengajuan` (`id_pengajuan`)',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 2: UPDATE absensi_pengajuan FOR BETTER LEAVE TYPES
-- ============================================================

ALTER TABLE `absensi_pengajuan` 
MODIFY COLUMN `tipe_pengajuan` enum('Izin','Sakit','Cuti','Dinas','Lembur','Koreksi','IzinKeluar') NOT NULL;

-- Add is_synced column to track if pengajuan has been synced to absensi_logs
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensi_pengajuan' AND COLUMN_NAME = 'is_synced') = 0,
    'ALTER TABLE `absensi_pengajuan` ADD COLUMN `is_synced` tinyint(1) DEFAULT 0 COMMENT ''1=Already synced to absensi_logs'' AFTER `alasan_tolak`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add synced_at column
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensi_pengajuan' AND COLUMN_NAME = 'synced_at') = 0,
    'ALTER TABLE `absensi_pengajuan` ADD COLUMN `synced_at` datetime DEFAULT NULL AFTER `is_synced`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================================
-- PART 3: UPDATE master_jenis_izin
-- ============================================================

-- Add status_absensi column to define what status to use in absensi_logs
SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'master_jenis_izin' AND COLUMN_NAME = 'status_absensi') = 0,
    'ALTER TABLE `master_jenis_izin` ADD COLUMN `status_absensi` varchar(30) DEFAULT NULL COMMENT ''Status to set in absensi_logs'' AFTER `is_active`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `master_jenis_izin` SET `status_absensi` = 'Sakit' WHERE `kode_izin` = 'SAKIT';
UPDATE `master_jenis_izin` SET `status_absensi` = 'Izin' WHERE `kode_izin` = 'IZIN';
UPDATE `master_jenis_izin` SET `status_absensi` = 'Cuti' WHERE `kode_izin` IN ('CUTI', 'MELAHIRKAN', 'NIKAH');
UPDATE `master_jenis_izin` SET `status_absensi` = 'Dinas Luar' WHERE `kode_izin` = 'DINAS';

-- ============================================================
-- PART 3B: ADD CHECKOUT & LEMBUR CONFIG TO absensi_group_config
-- ============================================================

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensi_group_config' AND COLUMN_NAME = 'require_checkout') = 0,
    'ALTER TABLE `absensi_group_config` ADD COLUMN `require_checkout` tinyint(1) DEFAULT 1 AFTER `id_lokasi_default`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensi_group_config' AND COLUMN_NAME = 'enable_lembur') = 0,
    'ALTER TABLE `absensi_group_config` ADD COLUMN `enable_lembur` tinyint(1) DEFAULT 0 AFTER `require_checkout`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'absensi_group_config' AND COLUMN_NAME = 'lembur_require_approval') = 0,
    'ALTER TABLE `absensi_group_config` ADD COLUMN `lembur_require_approval` tinyint(1) DEFAULT 1 AFTER `enable_lembur`',
    'SELECT 1'
));
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

UPDATE `absensi_group_config` SET `require_checkout` = 0, `enable_lembur` = 0 WHERE `kode_tipe` = 'SISWA';
UPDATE `absensi_group_config` SET `require_checkout` = 1, `enable_lembur` = 0 WHERE `kode_tipe` = 'GURU';
UPDATE `absensi_group_config` SET `require_checkout` = 1, `enable_lembur` = 1, `lembur_require_approval` = 1 WHERE `kode_tipe` IN ('TU', 'SATPAM', 'KEBUN');

INSERT INTO `master_jenis_izin` (`kode_izin`, `nama_izin`, `is_active`, `status_absensi`) VALUES
('SAKIT_KELUAR', 'Sakit (Pulang Awal)', 1, NULL),
('KELUARGA_KELUAR', 'Urusan Keluarga (Pulang Awal)', 1, NULL),
('LAINNYA_KELUAR', 'Lainnya (Pulang Awal)', 1, NULL)
ON DUPLICATE KEY UPDATE `nama_izin` = VALUES(`nama_izin`);

-- ============================================================
-- PART 4: UPDATE VIEW v_rekap_bulanan to include lembur
-- ============================================================

CREATE OR REPLACE VIEW `v_rekap_bulanan` AS
SELECT 
  al.id_user,
  YEAR(al.tanggal) AS tahun,
  MONTH(al.tanggal) AS bulan,
  COUNT(*) AS total_hari,
  SUM(CASE WHEN al.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) AS hadir,
  SUM(CASE WHEN al.status_kehadiran = 'Terlambat' THEN 1 ELSE 0 END) AS terlambat,
  SUM(CASE WHEN al.status_kehadiran = 'Pulang Awal' THEN 1 ELSE 0 END) AS pulang_awal,
  SUM(CASE WHEN al.status_kehadiran = 'Terlambat + Pulang Awal' THEN 1 ELSE 0 END) AS terlambat_pulang_awal,
  SUM(CASE WHEN al.status_kehadiran = 'Alpha' THEN 1 ELSE 0 END) AS alpha,
  SUM(CASE WHEN al.status_kehadiran = 'Izin' THEN 1 ELSE 0 END) AS izin,
  SUM(CASE WHEN al.status_kehadiran = 'Sakit' THEN 1 ELSE 0 END) AS sakit,
  SUM(CASE WHEN al.status_kehadiran = 'Cuti' THEN 1 ELSE 0 END) AS cuti,
  SUM(CASE WHEN al.status_kehadiran = 'Dinas Luar' THEN 1 ELSE 0 END) AS dinas,
  SUM(al.terlambat_menit) AS total_menit_terlambat,
  SUM(al.pulang_awal_menit) AS total_menit_pulang_awal,
  SUM(COALESCE(al.lembur_menit, 0)) AS total_menit_lembur
FROM absensi_logs al
GROUP BY al.id_user, YEAR(al.tanggal), MONTH(al.tanggal);

-- ============================================================
-- END OF MIGRATION
-- ============================================================
