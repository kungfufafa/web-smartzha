-- ============================================================
-- ABSENSI MODULE - COMPLETE DATABASE SCHEMA
-- Version: 2.0
-- Description: Comprehensive attendance system with GPS, QR,
--              bypass workflow, multi-location, and audit trail
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- PART 1: CORE MASTER TABLES
-- ============================================================

-- -----------------------------------------------------------
-- Table: master_shift
-- Description: Shift definitions with time windows
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `master_shift`;
CREATE TABLE `master_shift` (
  `id_shift` int(11) NOT NULL AUTO_INCREMENT,
  `nama_shift` varchar(50) NOT NULL,
  `kode_shift` varchar(20) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `lintas_hari` tinyint(1) DEFAULT 0 COMMENT '1=Yes (night shift crosses midnight), 0=No',
  `jam_awal_checkin` time DEFAULT NULL COMMENT 'Earliest check-in allowed',
  `jam_akhir_checkin` time DEFAULT NULL COMMENT 'Latest check-in (severely late)',
  `jam_awal_checkout` time DEFAULT NULL COMMENT 'Earliest checkout allowed',
  `jam_akhir_checkout` time DEFAULT NULL COMMENT 'Auto cut-off for overtime',
  `toleransi_terlambat` int(11) DEFAULT 0 COMMENT 'Grace period in minutes before marked late',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_shift`),
  UNIQUE KEY `uk_kode_shift` (`kode_shift`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default shifts
INSERT INTO `master_shift` (`nama_shift`, `kode_shift`, `jam_masuk`, `jam_pulang`, `lintas_hari`, `toleransi_terlambat`) VALUES
('Regular Pagi', 'REG_PAGI', '07:00:00', '15:00:00', 0, 15),
('Regular Siang', 'REG_SIANG', '08:00:00', '16:00:00', 0, 15),
('Shift Satpam Pagi', 'SEC_PAGI', '06:00:00', '14:00:00', 0, 0),
('Shift Satpam Siang', 'SEC_SIANG', '14:00:00', '22:00:00', 0, 0),
('Shift Satpam Malam', 'SEC_MALAM', '22:00:00', '06:00:00', 1, 0);

-- -----------------------------------------------------------
-- Table: master_jenis_izin
-- Description: Leave/absence types
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `master_jenis_izin`;
CREATE TABLE `master_jenis_izin` (
  `id_jenis` int(11) NOT NULL AUTO_INCREMENT,
  `nama_izin` varchar(50) NOT NULL,
  `kode_izin` varchar(20) NOT NULL,
  `kurangi_cuti` tinyint(1) DEFAULT 0 COMMENT '1=Deduct from annual leave quota',
  `butuh_file` tinyint(1) DEFAULT 0 COMMENT '1=Requires attachment',
  `max_hari` int(11) DEFAULT NULL COMMENT 'Maximum days per request (NULL=unlimited)',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_jenis`),
  UNIQUE KEY `uk_kode_izin` (`kode_izin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default leave types
INSERT INTO `master_jenis_izin` (`nama_izin`, `kode_izin`, `kurangi_cuti`, `butuh_file`, `max_hari`) VALUES
('Sakit', 'SAKIT', 0, 1, 14),
('Izin Pribadi', 'IZIN', 0, 0, 3),
('Cuti Tahunan', 'CUTI', 1, 0, 12),
('Dinas Luar', 'DINAS', 0, 1, NULL),
('Cuti Melahirkan', 'MELAHIRKAN', 0, 1, 90),
('Cuti Menikah', 'NIKAH', 0, 1, 3);

-- -----------------------------------------------------------
-- Table: master_hari_libur
-- Description: Holiday calendar
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `master_hari_libur`;
CREATE TABLE `master_hari_libur` (
  `id_libur` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `nama_libur` varchar(100) NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 0 COMMENT '1=Recurring yearly (e.g., Independence Day)',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_libur`),
  UNIQUE KEY `uk_tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: master_karyawan
-- Description: Staff/employee profiles (non-teacher)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `master_karyawan`;
CREATE TABLE `master_karyawan` (
  `id_karyawan` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED DEFAULT NULL,
  `nama_karyawan` varchar(100) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT NULL COMMENT 'Security, Cleaning, Admin, etc.',
  `departemen` varchar(50) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text,
  `foto` varchar(255) DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_karyawan`),
  KEY `idx_id_user` (`id_user`),
  CONSTRAINT `fk_karyawan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 2: CONFIGURATION TABLES
-- ============================================================

-- -----------------------------------------------------------
-- Table: absensi_config
-- Description: Global attendance configuration (key-value)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `absensi_config`;
CREATE TABLE `absensi_config` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL,
  `config_value` text,
  `config_type` enum('string','int','boolean','json') DEFAULT 'string',
  `description` varchar(255),
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_config`),
  UNIQUE KEY `uk_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default configuration
INSERT INTO `absensi_config` (`config_key`, `config_value`, `config_type`, `description`) VALUES
('enable_gps', '1', 'boolean', 'Enable GPS-based attendance'),
('enable_qr', '1', 'boolean', 'Enable QR Code attendance'),
('enable_manual', '1', 'boolean', 'Enable manual entry by admin'),
('require_photo_checkin', '1', 'boolean', 'Require selfie photo on check-in'),
('require_photo_checkout', '0', 'boolean', 'Require selfie photo on check-out'),
('allow_bypass_request', '1', 'boolean', 'Allow users to request radius bypass'),
('bypass_auto_approve', '0', 'boolean', 'Auto-approve bypass requests'),
('qr_validity_minutes', '5', 'int', 'QR code validity in minutes'),
('qr_refresh_interval', '60', 'int', 'QR refresh interval in seconds'),
('default_radius_meter', '100', 'int', 'Default GPS radius in meters'),
('max_bypass_per_month', '5', 'int', 'Maximum bypass requests per user per month'),
('late_threshold_minutes', '30', 'int', 'Minutes late before marked as severely late'),
('working_days', '[\"1\",\"2\",\"3\",\"4\",\"5\"]', 'json', 'Working days (1=Monday, 7=Sunday)'),
('timezone', 'Asia/Jakarta', 'string', 'System timezone');

-- -----------------------------------------------------------
-- Table: absensi_group_config
-- Description: Per-group configuration overrides
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `absensi_group_config`;
CREATE TABLE `absensi_group_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_group` mediumint(8) UNSIGNED NOT NULL COMMENT 'From groups table',
  `enable_gps` tinyint(1) DEFAULT 1,
  `enable_qr` tinyint(1) DEFAULT 1,
  `enable_manual` tinyint(1) DEFAULT 0,
  `require_photo` tinyint(1) DEFAULT 1,
  `allow_bypass` tinyint(1) DEFAULT 1,
  `id_lokasi_default` int(11) DEFAULT NULL COMMENT 'Default location for this group',
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_id_group` (`id_group`),
  KEY `idx_id_lokasi_default` (`id_lokasi_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 3: LOCATION TABLES
-- ============================================================

-- -----------------------------------------------------------
-- Table: absensi_lokasi
-- Description: School/campus locations for GPS validation
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `absensi_lokasi`;
CREATE TABLE `absensi_lokasi` (
  `id_lokasi` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(100) NOT NULL,
  `kode_lokasi` varchar(20) NOT NULL,
  `alamat` text,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius_meter` int(11) DEFAULT 100,
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'Primary/default location',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lokasi`),
  UNIQUE KEY `uk_kode_lokasi` (`kode_lokasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Default location (update with actual coordinates)
INSERT INTO `absensi_lokasi` (`nama_lokasi`, `kode_lokasi`, `alamat`, `latitude`, `longitude`, `radius_meter`, `is_default`) VALUES
('Gedung Utama Sekolah', 'SEKOLAH', 'Jl. Pendidikan No. 1', -6.17539200, 106.82715300, 100, 1);

-- ============================================================
-- PART 4: SHIFT ASSIGNMENT TABLES
-- ============================================================

-- -----------------------------------------------------------
-- Table: pegawai_shift
-- Description: User shift assignment (fixed or rotating)
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `pegawai_shift`;
CREATE TABLE `pegawai_shift` (
  `id_pegawai_shift` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `tipe_shift` enum('fixed','rotating') NOT NULL DEFAULT 'fixed',
  `id_shift_fixed` int(11) DEFAULT NULL COMMENT 'Used if tipe_shift=fixed',
  `tgl_efektif` date NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pegawai_shift`),
  KEY `idx_id_user` (`id_user`),
  KEY `idx_id_shift_fixed` (`id_shift_fixed`),
  CONSTRAINT `fk_ps_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_shift` FOREIGN KEY (`id_shift_fixed`) REFERENCES `master_shift` (`id_shift`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: shift_jadwal
-- Description: Daily shift schedule for rotating shifts
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `shift_jadwal`;
CREATE TABLE `shift_jadwal` (
  `id_jadwal` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `id_shift` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_jadwal`),
  UNIQUE KEY `uk_user_tanggal` (`id_user`, `tanggal`),
  KEY `idx_id_shift` (`id_shift`),
  CONSTRAINT `fk_sj_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sj_shift` FOREIGN KEY (`id_shift`) REFERENCES `master_shift` (`id_shift`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 5: ATTENDANCE LOGS
-- ============================================================

-- -----------------------------------------------------------
-- Table: absensi_logs
-- Description: Main attendance records
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `absensi_logs`;
CREATE TABLE `absensi_logs` (
  `id_log` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `id_shift` int(11) DEFAULT NULL,
  `id_lokasi` int(11) DEFAULT NULL COMMENT 'Office location used',
  `tanggal` date NOT NULL,
  `jam_masuk` datetime DEFAULT NULL,
  `jam_pulang` datetime DEFAULT NULL,
  `status_kehadiran` enum('Hadir','Terlambat','Pulang Awal','Terlambat + Pulang Awal','Alpha','Izin','Sakit','Cuti','Dinas Luar') DEFAULT 'Alpha',
  `metode_masuk` enum('GPS','QR','Manual') DEFAULT NULL,
  `metode_pulang` enum('GPS','QR','Manual') DEFAULT NULL,
  `lat_masuk` decimal(10,8) DEFAULT NULL,
  `long_masuk` decimal(11,8) DEFAULT NULL,
  `lat_pulang` decimal(10,8) DEFAULT NULL,
  `long_pulang` decimal(11,8) DEFAULT NULL,
  `foto_masuk` varchar(255) DEFAULT NULL,
  `foto_pulang` varchar(255) DEFAULT NULL,
  `qr_token_masuk` varchar(64) DEFAULT NULL,
  `qr_token_pulang` varchar(64) DEFAULT NULL,
  `bypass_id` int(11) DEFAULT NULL COMMENT 'If bypass request was used',
  `device_info` varchar(255) DEFAULT NULL,
  `keterangan` text,
  `terlambat_menit` int(11) DEFAULT 0,
  `pulang_awal_menit` int(11) DEFAULT 0,
  `is_overnight` tinyint(1) DEFAULT 0 COMMENT 'Night shift crossing midnight',
  `is_manual_entry` tinyint(1) DEFAULT 0,
  `manual_entry_by` int(11) UNSIGNED DEFAULT NULL,
  `manual_entry_reason` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  UNIQUE KEY `uk_user_tanggal` (`id_user`, `tanggal`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_id_lokasi` (`id_lokasi`),
  KEY `idx_status_kehadiran` (`status_kehadiran`),
  KEY `idx_id_shift` (`id_shift`),
  CONSTRAINT `fk_al_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_al_lokasi` FOREIGN KEY (`id_lokasi`) REFERENCES `absensi_lokasi` (`id_lokasi`) ON DELETE SET NULL,
  CONSTRAINT `fk_al_shift` FOREIGN KEY (`id_shift`) REFERENCES `master_shift` (`id_shift`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 6: QR CODE SYSTEM
-- ============================================================

-- -----------------------------------------------------------
-- Table: absensi_qr_token
-- Description: QR code tokens for attendance
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `absensi_qr_token`;
CREATE TABLE `absensi_qr_token` (
  `id_token` int(11) NOT NULL AUTO_INCREMENT,
  `token_code` varchar(64) NOT NULL COMMENT 'Unique token string',
  `token_type` enum('checkin','checkout','both') DEFAULT 'both',
  `id_lokasi` int(11) DEFAULT NULL,
  `id_shift` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `valid_from` datetime NOT NULL,
  `valid_until` datetime NOT NULL,
  `created_by` int(11) UNSIGNED NOT NULL,
  `used_count` int(11) DEFAULT 0,
  `max_usage` int(11) DEFAULT NULL COMMENT 'NULL = unlimited',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_token`),
  UNIQUE KEY `uk_token_code` (`token_code`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_valid_until` (`valid_until`),
  KEY `idx_id_lokasi` (`id_lokasi`),
  CONSTRAINT `fk_qt_lokasi` FOREIGN KEY (`id_lokasi`) REFERENCES `absensi_lokasi` (`id_lokasi`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 7: BYPASS REQUEST SYSTEM
-- ============================================================

-- -----------------------------------------------------------
-- Table: absensi_bypass_request
-- Description: Requests to bypass GPS radius requirement
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `absensi_bypass_request`;
CREATE TABLE `absensi_bypass_request` (
  `id_bypass` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `tanggal` date NOT NULL,
  `tipe_bypass` enum('checkin','checkout','both') DEFAULT 'both',
  `alasan` text NOT NULL,
  `lokasi_alternatif` varchar(255) DEFAULT NULL COMMENT 'Description of alternative location',
  `latitude` decimal(10,8) DEFAULT NULL,
  `longitude` decimal(11,8) DEFAULT NULL,
  `foto_bukti` varchar(255) DEFAULT NULL,
  `status` enum('pending','approved','rejected','used','expired') DEFAULT 'pending',
  `approved_by` int(11) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `catatan_admin` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_bypass`),
  KEY `idx_id_user` (`id_user`),
  KEY `idx_tanggal` (`tanggal`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_bypass_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 8: LEAVE/PERMISSION REQUESTS
-- ============================================================

-- -----------------------------------------------------------
-- Table: absensi_pengajuan
-- Description: Leave, overtime, and correction requests
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `absensi_pengajuan`;
CREATE TABLE `absensi_pengajuan` (
  `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `tipe_pengajuan` enum('Izin','Lembur','Koreksi') NOT NULL,
  `id_jenis_izin` int(11) DEFAULT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `jam_mulai` time DEFAULT NULL COMMENT 'For overtime/hourly leave',
  `jam_selesai` time DEFAULT NULL,
  `jumlah_hari` int(11) DEFAULT 1,
  `keterangan` text NOT NULL,
  `file_bukti` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Disetujui','Ditolak','Dibatalkan') DEFAULT 'Pending',
  `approved_by` int(11) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `alasan_tolak` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengajuan`),
  KEY `idx_id_user` (`id_user`),
  KEY `idx_status` (`status`),
  KEY `idx_tgl_mulai` (`tgl_mulai`),
  KEY `idx_id_jenis_izin` (`id_jenis_izin`),
  CONSTRAINT `fk_ap_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ap_jenis` FOREIGN KEY (`id_jenis_izin`) REFERENCES `master_jenis_izin` (`id_jenis`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 9: AUDIT TRAIL
-- ============================================================

-- -----------------------------------------------------------
-- Table: absensi_audit_log
-- Description: Audit trail for all attendance actions
-- -----------------------------------------------------------
DROP TABLE IF EXISTS `absensi_audit_log`;
CREATE TABLE `absensi_audit_log` (
  `id_audit` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_log` bigint(20) DEFAULT NULL COMMENT 'Related attendance log',
  `id_user_target` int(11) UNSIGNED NOT NULL COMMENT 'User being affected',
  `action` varchar(50) NOT NULL COMMENT 'checkin, checkout, manual_entry, edit, delete, approve, reject',
  `action_by` int(11) UNSIGNED NOT NULL COMMENT 'Who performed the action',
  `action_by_role` varchar(20) DEFAULT NULL,
  `data_before` text COMMENT 'JSON snapshot before',
  `data_after` text COMMENT 'JSON snapshot after',
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_audit`),
  KEY `idx_id_log` (`id_log`),
  KEY `idx_id_user_target` (`id_user_target`),
  KEY `idx_action` (`action`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- PART 10: VIEWS FOR REPORTING
-- ============================================================

-- -----------------------------------------------------------
-- View: v_absensi_harian
-- Description: Daily attendance summary with user details
-- -----------------------------------------------------------
CREATE OR REPLACE VIEW `v_absensi_harian` AS
SELECT 
  al.tanggal,
  al.id_user,
  COALESCE(g.nama_guru, k.nama_karyawan, u.username) AS nama,
  CASE 
    WHEN g.id_guru IS NOT NULL THEN 'Guru'
    WHEN k.id_karyawan IS NOT NULL THEN 'Karyawan'
    ELSE 'Admin'
  END AS tipe_user,
  s.nama_shift,
  al.jam_masuk,
  al.jam_pulang,
  al.status_kehadiran,
  al.metode_masuk,
  al.metode_pulang,
  al.terlambat_menit,
  al.pulang_awal_menit,
  l.nama_lokasi
FROM absensi_logs al
LEFT JOIN users u ON al.id_user = u.id
LEFT JOIN master_guru g ON u.id = g.id_user
LEFT JOIN master_karyawan k ON u.id = k.id_user
LEFT JOIN master_shift s ON al.id_shift = s.id_shift
LEFT JOIN absensi_lokasi l ON al.id_lokasi = l.id_lokasi;

-- -----------------------------------------------------------
-- View: v_rekap_bulanan
-- Description: Monthly attendance recap per user
-- -----------------------------------------------------------
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
  SUM(CASE WHEN al.status_kehadiran IN ('Izin','Sakit','Cuti','Dinas Luar') THEN 1 ELSE 0 END) AS izin_total,
  SUM(al.terlambat_menit) AS total_menit_terlambat,
  SUM(al.pulang_awal_menit) AS total_menit_pulang_awal
FROM absensi_logs al
GROUP BY al.id_user, YEAR(al.tanggal), MONTH(al.tanggal);

-- ============================================================
-- END OF SCHEMA
-- ============================================================
