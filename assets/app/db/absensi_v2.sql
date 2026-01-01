SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- ABSENSI MODULE V2 - COMPREHENSIVE SCHEMA
-- ============================================

-- ----------------------------
-- Table: master_shift (unchanged)
-- ----------------------------
DROP TABLE IF EXISTS `master_shift`;
CREATE TABLE `master_shift` (
  `id_shift` int(11) NOT NULL AUTO_INCREMENT,
  `nama_shift` varchar(50) NOT NULL,
  `kode_shift` varchar(20) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `lintas_hari` tinyint(1) DEFAULT 0 COMMENT '1=Ya (shift malam), 0=Tidak',
  `jam_awal_checkin` time DEFAULT NULL COMMENT 'Batas awal boleh check-in',
  `jam_akhir_checkin` time DEFAULT NULL COMMENT 'Batas akhir dianggap terlambat berat',
  `jam_awal_checkout` time DEFAULT NULL COMMENT 'Batas awal boleh check-out',
  `jam_akhir_checkout` time DEFAULT NULL COMMENT 'Auto cut-off overtime',
  `toleransi_terlambat` int(11) DEFAULT 0 COMMENT 'Menit toleransi sebelum dianggap terlambat',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_shift`),
  UNIQUE KEY `kode_shift` (`kode_shift`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `master_shift` (`nama_shift`, `kode_shift`, `jam_masuk`, `jam_pulang`, `lintas_hari`, `toleransi_terlambat`) VALUES
('Regular Pagi', 'REG_PAGI', '07:00:00', '15:00:00', 0, 15),
('Regular Siang', 'REG_SIANG', '08:00:00', '16:00:00', 0, 15),
('Shift Satpam Pagi', 'SEC_PAGI', '06:00:00', '14:00:00', 0, 0),
('Shift Satpam Siang', 'SEC_SIANG', '14:00:00', '22:00:00', 0, 0),
('Shift Satpam Malam', 'SEC_MALAM', '22:00:00', '06:00:00', 1, 0);

-- ----------------------------
-- Table: absensi_lokasi (NEW - Multiple office locations)
-- ----------------------------
DROP TABLE IF EXISTS `absensi_lokasi`;
CREATE TABLE `absensi_lokasi` (
  `id_lokasi` int(11) NOT NULL AUTO_INCREMENT,
  `nama_lokasi` varchar(100) NOT NULL,
  `kode_lokasi` varchar(20) NOT NULL,
  `alamat` text,
  `latitude` decimal(10,8) NOT NULL,
  `longitude` decimal(11,8) NOT NULL,
  `radius_meter` int(11) DEFAULT 100,
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'Lokasi utama/default',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_lokasi`),
  UNIQUE KEY `kode_lokasi` (`kode_lokasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `absensi_lokasi` (`nama_lokasi`, `kode_lokasi`, `alamat`, `latitude`, `longitude`, `radius_meter`, `is_default`) VALUES
('Kantor Pusat', 'HQ', 'Jl. Contoh No. 123', -6.17539200, 106.82715300, 100, 1);

-- ----------------------------
-- Table: absensi_config (NEW - Per-group attendance settings)
-- ----------------------------
DROP TABLE IF EXISTS `absensi_config`;
CREATE TABLE `absensi_config` (
  `id_config` int(11) NOT NULL AUTO_INCREMENT,
  `config_key` varchar(50) NOT NULL,
  `config_value` text,
  `config_type` enum('string','int','boolean','json') DEFAULT 'string',
  `description` varchar(255),
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_config`),
  UNIQUE KEY `config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

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
('late_threshold_minutes', '30', 'int', 'Minutes late before marked as "Terlambat Berat"'),
('working_days', '["1","2","3","4","5"]', 'json', 'Working days (1=Monday, 7=Sunday)'),
('timezone', 'Asia/Jakarta', 'string', 'System timezone');

-- ----------------------------
-- Table: absensi_group_config (NEW - Override config per user group)
-- ----------------------------
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
  UNIQUE KEY `id_group` (`id_group`),
  KEY `id_lokasi_default` (`id_lokasi_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: absensi_qr_token (NEW - QR code generation & tracking)
-- ----------------------------
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
  UNIQUE KEY `token_code` (`token_code`),
  KEY `tanggal` (`tanggal`),
  KEY `valid_until` (`valid_until`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: absensi_bypass_request (NEW - Bypass radius requests)
-- ----------------------------
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
  KEY `id_user` (`id_user`),
  KEY `tanggal` (`tanggal`),
  KEY `status` (`status`),
  CONSTRAINT `fk_bypass_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: master_karyawan (updated)
-- ----------------------------
DROP TABLE IF EXISTS `master_karyawan`;
CREATE TABLE `master_karyawan` (
  `id_karyawan` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED DEFAULT NULL,
  `nama_karyawan` varchar(100) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT NULL,
  `departemen` varchar(50) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text,
  `foto` varchar(255) DEFAULT NULL,
  `tanggal_masuk` date DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_karyawan`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `fk_karyawan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: pegawai_shift (unchanged)
-- ----------------------------
DROP TABLE IF EXISTS `pegawai_shift`;
CREATE TABLE `pegawai_shift` (
  `id_pegawai_shift` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `tipe_shift` enum('fixed','rotating') NOT NULL DEFAULT 'fixed',
  `id_shift_fixed` int(11) DEFAULT NULL,
  `tgl_efektif` date NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pegawai_shift`),
  KEY `id_user` (`id_user`),
  KEY `id_shift_fixed` (`id_shift_fixed`),
  CONSTRAINT `fk_ps_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_shift` FOREIGN KEY (`id_shift_fixed`) REFERENCES `master_shift` (`id_shift`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: shift_jadwal (unchanged)
-- ----------------------------
DROP TABLE IF EXISTS `shift_jadwal`;
CREATE TABLE `shift_jadwal` (
  `id_jadwal` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `id_shift` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_jadwal`),
  UNIQUE KEY `user_tanggal` (`id_user`, `tanggal`),
  KEY `id_shift` (`id_shift`),
  CONSTRAINT `fk_sj_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_sj_shift` FOREIGN KEY (`id_shift`) REFERENCES `master_shift` (`id_shift`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: absensi_logs (UPDATED - added new columns)
-- ----------------------------
DROP TABLE IF EXISTS `absensi_logs`;
CREATE TABLE `absensi_logs` (
  `id_log` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `id_shift` int(11) DEFAULT NULL,
  `id_lokasi` int(11) DEFAULT NULL COMMENT 'Which office location',
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
  `bypass_id` int(11) DEFAULT NULL COMMENT 'If used bypass request',
  `device_info` varchar(255) DEFAULT NULL,
  `keterangan` text,
  `terlambat_menit` int(11) DEFAULT 0,
  `pulang_awal_menit` int(11) DEFAULT 0,
  `is_overnight` tinyint(1) DEFAULT 0,
  `is_manual_entry` tinyint(1) DEFAULT 0,
  `manual_entry_by` int(11) UNSIGNED DEFAULT NULL,
  `manual_entry_reason` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `id_user` (`id_user`),
  KEY `tanggal` (`tanggal`),
  KEY `id_lokasi` (`id_lokasi`),
  KEY `status_kehadiran` (`status_kehadiran`),
  CONSTRAINT `fk_al_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_al_lokasi` FOREIGN KEY (`id_lokasi`) REFERENCES `absensi_lokasi` (`id_lokasi`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: absensi_audit_log (NEW - Audit trail for all actions)
-- ----------------------------
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
  KEY `id_log` (`id_log`),
  KEY `id_user_target` (`id_user_target`),
  KEY `action` (`action`),
  KEY `created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: master_jenis_izin (unchanged)
-- ----------------------------
DROP TABLE IF EXISTS `master_jenis_izin`;
CREATE TABLE `master_jenis_izin` (
  `id_jenis` int(11) NOT NULL AUTO_INCREMENT,
  `nama_izin` varchar(50) NOT NULL,
  `kode_izin` varchar(20) NOT NULL,
  `kurangi_cuti` tinyint(1) DEFAULT 0,
  `butuh_file` tinyint(1) DEFAULT 0,
  `max_hari` int(11) DEFAULT NULL COMMENT 'Maximum days per request',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_jenis`),
  UNIQUE KEY `kode_izin` (`kode_izin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `master_jenis_izin` (`nama_izin`, `kode_izin`, `kurangi_cuti`, `butuh_file`, `max_hari`) VALUES
('Sakit', 'SAKIT', 0, 1, 14),
('Izin Pribadi', 'IZIN', 0, 0, 3),
('Cuti Tahunan', 'CUTI', 1, 0, 12),
('Dinas Luar', 'DINAS', 0, 1, NULL),
('Cuti Melahirkan', 'MELAHIRKAN', 0, 1, 90),
('Cuti Menikah', 'NIKAH', 0, 1, 3);

-- ----------------------------
-- Table: absensi_pengajuan (UPDATED)
-- ----------------------------
DROP TABLE IF EXISTS `absensi_pengajuan`;
CREATE TABLE `absensi_pengajuan` (
  `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `tipe_pengajuan` enum('Izin','Lembur','Koreksi') NOT NULL,
  `id_jenis_izin` int(11) DEFAULT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `jam_mulai` time DEFAULT NULL,
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
  KEY `id_user` (`id_user`),
  KEY `status` (`status`),
  KEY `tgl_mulai` (`tgl_mulai`),
  CONSTRAINT `fk_ap_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table: master_hari_libur (NEW - Holiday calendar)
-- ----------------------------
DROP TABLE IF EXISTS `master_hari_libur`;
CREATE TABLE `master_hari_libur` (
  `id_libur` int(11) NOT NULL AUTO_INCREMENT,
  `tanggal` date NOT NULL,
  `nama_libur` varchar(100) NOT NULL,
  `is_recurring` tinyint(1) DEFAULT 0 COMMENT '1=Recurring yearly (e.g. 17 Agustus)',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_libur`),
  UNIQUE KEY `tanggal` (`tanggal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;

-- ----------------------------
-- Views for reporting
-- ----------------------------

-- View: Daily attendance summary
CREATE OR REPLACE VIEW `v_absensi_harian` AS
SELECT 
  al.tanggal,
  al.id_user,
  COALESCE(g.nama_guru, k.nama_karyawan, u.username) as nama,
  CASE 
    WHEN g.id_guru IS NOT NULL THEN 'Guru'
    WHEN k.id_karyawan IS NOT NULL THEN 'Karyawan'
    ELSE 'Admin'
  END as tipe_user,
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

-- View: Monthly recap per user
CREATE OR REPLACE VIEW `v_rekap_bulanan` AS
SELECT 
  al.id_user,
  YEAR(al.tanggal) as tahun,
  MONTH(al.tanggal) as bulan,
  COUNT(*) as total_hari,
  SUM(CASE WHEN al.status_kehadiran = 'Hadir' THEN 1 ELSE 0 END) as hadir,
  SUM(CASE WHEN al.status_kehadiran = 'Terlambat' THEN 1 ELSE 0 END) as terlambat,
  SUM(CASE WHEN al.status_kehadiran = 'Pulang Awal' THEN 1 ELSE 0 END) as pulang_awal,
  SUM(CASE WHEN al.status_kehadiran = 'Terlambat + Pulang Awal' THEN 1 ELSE 0 END) as terlambat_pulang_awal,
  SUM(CASE WHEN al.status_kehadiran = 'Alpha' THEN 1 ELSE 0 END) as alpha,
  SUM(CASE WHEN al.status_kehadiran IN ('Izin','Sakit','Cuti','Dinas Luar') THEN 1 ELSE 0 END) as izin_total,
  SUM(al.terlambat_menit) as total_menit_terlambat,
  SUM(al.pulang_awal_menit) as total_menit_pulang_awal
FROM absensi_logs al
GROUP BY al.id_user, YEAR(al.tanggal), MONTH(al.tanggal);
