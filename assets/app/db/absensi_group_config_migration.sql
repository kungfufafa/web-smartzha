-- ============================================================
-- ABSENSI GROUP CONFIG MIGRATION
-- Adds per-group/per-tipe working day configuration
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- -----------------------------------------------------------
-- Step 1: Drop existing table and recreate with new structure
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `absensi_group_config`;
CREATE TABLE `absensi_group_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_group` mediumint(8) UNSIGNED NOT NULL COMMENT 'From Ion Auth groups table',
  `kode_tipe` varchar(20) DEFAULT NULL COMMENT 'Sub-type within group (GURU, TU, SATPAM, KEBUN, etc.)',
  `nama_konfigurasi` varchar(100) DEFAULT NULL COMMENT 'Display name: Guru Tetap, Satpam, etc.',
  
  -- Working Schedule Config
  `working_days` varchar(50) DEFAULT '[1,2,3,4,5]' COMMENT 'JSON array: 1=Monday..7=Sunday',
  `id_shift_default` int(11) DEFAULT NULL COMMENT 'Default shift for this group/tipe',
  `follow_academic_calendar` tinyint(1) DEFAULT 0 COMMENT '1=Follows semester/academic breaks',
  `holiday_group` enum('all','academic','essential','none') DEFAULT 'all' 
    COMMENT 'all=all holidays, academic=school holidays, essential=only national, none=works all days',
  
  -- Attendance Method Config
  `enable_gps` tinyint(1) DEFAULT 1,
  `enable_qr` tinyint(1) DEFAULT 1,
  `enable_manual` tinyint(1) DEFAULT 0,
  `require_photo` tinyint(1) DEFAULT 1,
  `allow_bypass` tinyint(1) DEFAULT 1,
  
  -- Tolerance Config
  `toleransi_terlambat` int(11) DEFAULT NULL COMMENT 'Grace period minutes (NULL=use shift/global)',
  `id_lokasi_default` int(11) DEFAULT NULL COMMENT 'Default location for this group',
  
  -- Checkout & Lembur Config
  `require_checkout` tinyint(1) DEFAULT 1 COMMENT '1=Checkout wajib, 0=Checkout opsional/tidak perlu',
  `enable_lembur` tinyint(1) DEFAULT 0 COMMENT '1=Lembur diaktifkan, 0=Lembur tidak berlaku',
  `lembur_require_approval` tinyint(1) DEFAULT 1 COMMENT '1=Lembur harus ada pengajuan approved, 0=Otomatis dari checkout',
  
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_group_tipe` (`id_group`, `kode_tipe`),
  KEY `idx_id_shift_default` (`id_shift_default`),
  KEY `idx_id_lokasi_default` (`id_lokasi_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Step 2: Add tipe_karyawan column to master_karyawan
-- -----------------------------------------------------------
-- Check if column exists first (MySQL safe way)
SET @column_exists = (
  SELECT COUNT(*) 
  FROM information_schema.COLUMNS 
  WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'master_karyawan' 
    AND COLUMN_NAME = 'tipe_karyawan'
);

SET @alter_stmt = IF(@column_exists = 0,
  'ALTER TABLE `master_karyawan` ADD COLUMN `tipe_karyawan` varchar(20) DEFAULT NULL COMMENT ''TU, SATPAM, KEBUN, etc.'' AFTER `jabatan`',
  'SELECT 1'
);

PREPARE stmt FROM @alter_stmt;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- -----------------------------------------------------------
-- Step 3: Insert default configurations
-- -----------------------------------------------------------
-- Note: id_group values depend on your Ion Auth groups
-- Typically: 1=admin, 2=guru, 3=siswa, 4=karyawan (verify with SELECT * FROM groups)

-- Config for Siswa (group_id typically 3)
INSERT INTO `absensi_group_config` 
(`id_group`, `kode_tipe`, `nama_konfigurasi`, `working_days`, `id_shift_default`, `follow_academic_calendar`, `holiday_group`, `enable_gps`, `enable_qr`, `toleransi_terlambat`, `require_checkout`, `enable_lembur`, `lembur_require_approval`)
VALUES
(3, 'SISWA', 'Siswa', '[1,2,3,4,5,6]', NULL, 1, 'academic', 1, 1, 15, 0, 0, 0)
ON DUPLICATE KEY UPDATE 
  `nama_konfigurasi` = VALUES(`nama_konfigurasi`),
  `require_checkout` = VALUES(`require_checkout`),
  `enable_lembur` = VALUES(`enable_lembur`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Config for Guru (group_id typically 2)
INSERT INTO `absensi_group_config` 
(`id_group`, `kode_tipe`, `nama_konfigurasi`, `working_days`, `id_shift_default`, `follow_academic_calendar`, `holiday_group`, `enable_gps`, `enable_qr`, `toleransi_terlambat`, `require_checkout`, `enable_lembur`, `lembur_require_approval`)
VALUES
(2, 'GURU', 'Guru', '[1,2,3,4,5]', NULL, 1, 'academic', 1, 1, 15, 1, 0, 1)
ON DUPLICATE KEY UPDATE 
  `nama_konfigurasi` = VALUES(`nama_konfigurasi`),
  `require_checkout` = VALUES(`require_checkout`),
  `enable_lembur` = VALUES(`enable_lembur`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Config for Karyawan TU (group_id typically 4)
INSERT INTO `absensi_group_config` 
(`id_group`, `kode_tipe`, `nama_konfigurasi`, `working_days`, `id_shift_default`, `follow_academic_calendar`, `holiday_group`, `enable_gps`, `enable_qr`, `toleransi_terlambat`, `require_checkout`, `enable_lembur`, `lembur_require_approval`)
VALUES
(4, 'TU', 'Tata Usaha', '[1,2,3,4,5,6]', NULL, 0, 'all', 1, 1, 15, 1, 1, 1)
ON DUPLICATE KEY UPDATE 
  `nama_konfigurasi` = VALUES(`nama_konfigurasi`),
  `require_checkout` = VALUES(`require_checkout`),
  `enable_lembur` = VALUES(`enable_lembur`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Config for Satpam (group_id 4, different tipe)
INSERT INTO `absensi_group_config` 
(`id_group`, `kode_tipe`, `nama_konfigurasi`, `working_days`, `id_shift_default`, `follow_academic_calendar`, `holiday_group`, `enable_gps`, `enable_qr`, `toleransi_terlambat`, `require_checkout`, `enable_lembur`, `lembur_require_approval`)
VALUES
(4, 'SATPAM', 'Satpam', '[1,2,3,4,5,6,7]', NULL, 0, 'essential', 1, 1, 0, 1, 1, 1)
ON DUPLICATE KEY UPDATE 
  `nama_konfigurasi` = VALUES(`nama_konfigurasi`),
  `require_checkout` = VALUES(`require_checkout`),
  `enable_lembur` = VALUES(`enable_lembur`),
  `updated_at` = CURRENT_TIMESTAMP;

-- Config for Tukang Kebun (group_id 4, different tipe)
INSERT INTO `absensi_group_config` 
(`id_group`, `kode_tipe`, `nama_konfigurasi`, `working_days`, `id_shift_default`, `follow_academic_calendar`, `holiday_group`, `enable_gps`, `enable_qr`, `toleransi_terlambat`, `require_checkout`, `enable_lembur`, `lembur_require_approval`)
VALUES
(4, 'KEBUN', 'Tukang Kebun', '[1,2,3,4,5,6]', NULL, 0, 'all', 1, 0, 30, 1, 1, 1)
ON DUPLICATE KEY UPDATE 
  `nama_konfigurasi` = VALUES(`nama_konfigurasi`),
  `require_checkout` = VALUES(`require_checkout`),
  `enable_lembur` = VALUES(`enable_lembur`),
  `updated_at` = CURRENT_TIMESTAMP;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- USAGE NOTES
-- ============================================================
-- 
-- Resolution Priority for isWorkingDay($date, $id_user):
-- 1. Get user's group from users_groups
-- 2. If karyawan, get tipe_karyawan from master_karyawan
-- 3. Lookup absensi_group_config by (id_group, kode_tipe)
-- 4. If no match, lookup by (id_group, NULL) as fallback
-- 5. If still no match, use global absensi_config
--
-- Example lookup:
-- - User is in group "karyawan" (id_group=4) with tipe_karyawan="SATPAM"
-- - Query: WHERE id_group=4 AND kode_tipe='SATPAM'
-- - Gets: working_days=[1,2,3,4,5,6,7], holiday_group='essential'
-- - Result: Satpam works every day, only stops on national holidays
--
-- ============================================================
