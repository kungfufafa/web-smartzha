-- ============================================================
-- PRESENSI V3 - COMPLETE SCHEMA
-- New attendance system with linear validation flow
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- PART 1: MASTER DATA (Single Source of Truth)
-- ============================================================

-- -----------------------------------------------------------
-- Table: presensi_shift
-- Description: Core shift definition with BUILT-IN tolerance
-- -----------------------------------------------------------
CREATE TABLE `presensi_shift` (
    `id_shift` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_shift` VARCHAR(10) NOT NULL,
    `nama_shift` VARCHAR(50) NOT NULL,
    `deskripsi` TEXT,

    -- Core timing
    `jam_masuk` TIME NOT NULL,
    `jam_pulang` TIME NOT NULL,
    `is_lintas_hari` TINYINT(1) DEFAULT 0 COMMENT 'Shift crosses midnight',

    -- TOLERANCE BUILT-IN (single source!)
    `toleransi_masuk_menit` INT(3) UNSIGNED DEFAULT 15 COMMENT 'Late tolerance in minutes',
    `toleransi_pulang_menit` INT(3) UNSIGNED DEFAULT 0 COMMENT 'Early leave tolerance in minutes',

    -- Check-in window boundaries (optional)
    `earliest_checkin` TIME DEFAULT NULL COMMENT 'Earliest allowed check-in',
    `latest_checkin` TIME DEFAULT NULL COMMENT 'Latest allowed check-in (after = absent)',
    `earliest_checkout` TIME DEFAULT NULL COMMENT 'Earliest allowed check-out',

    -- Calculated work duration (generated column)
    `durasi_kerja_menit` INT UNSIGNED GENERATED ALWAYS AS (
        CASE
            WHEN is_lintas_hari =1 THEN
                TIMESTAMPDIFF(MINUTE, jam_masuk, ADDTIME(jam_pulang, '24:00:00'))
            ELSE
                TIMESTAMPDIFF(MINUTE, jam_masuk, jam_pulang)
        END
    ) STORED,

    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_shift`),
    UNIQUE KEY `uk_kode_shift` (`kode_shift`),
    KEY `idx_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_lokasi
-- Description: Office locations with geofencing
-- -----------------------------------------------------------
CREATE TABLE `presensi_lokasi` (
    `id_lokasi` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_lokasi` VARCHAR(10) NOT NULL,
    `nama_lokasi` VARCHAR(100) NOT NULL,
    `alamat` TEXT,
    `latitude` DECIMAL(10, 8) NOT NULL,
    `longitude` DECIMAL(11, 8) NOT NULL,
    `radius_meter` INT UNSIGNED DEFAULT 100,
    `is_default` TINYINT(1) DEFAULT 0,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_lokasi`),
    UNIQUE KEY `uk_kode_lokasi` (`kode_lokasi`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_jenis_izin
-- Description: Leave/permission types
-- -----------------------------------------------------------
CREATE TABLE `presensi_jenis_izin` (
    `id_jenis` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `nama_izin` VARCHAR(50) NOT NULL,
    `kode_izin` VARCHAR(20) NOT NULL,
    `kurangi_cuti` TINYINT(1) DEFAULT 0 COMMENT 'Deduct from annual leave quota',
    `butuh_file` TINYINT(1) DEFAULT 0 COMMENT 'Requires attachment',
    `max_hari` INT(11) DEFAULT NULL COMMENT 'Max days per request (NULL = unlimited)',
    `status_presensi` VARCHAR(30) DEFAULT NULL COMMENT 'Status to set in presensi_logs',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_jenis`),
    UNIQUE KEY `uk_kode_izin` (`kode_izin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_hari_libur
-- Description: Holiday definitions
-- -----------------------------------------------------------
CREATE TABLE `presensi_hari_libur` (
    `id_libur` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `tanggal` DATE NOT NULL,
    `nama_libur` VARCHAR(100) NOT NULL,
    `tipe_libur` ENUM('NASIONAL', 'AKADEMIK', 'KANTOR') DEFAULT 'NASIONAL',
    `is_recurring` TINYINT(1) DEFAULT 0 COMMENT 'Recurring yearly',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_libur`),
    UNIQUE KEY `uk_tanggal` (`tanggal`),
    KEY `idx_tipe` (`tipe_libur`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 2: CONFIGURATION (Global → Group)
-- Notes: Per-user overrides sengaja dihindari untuk menjaga aturan tetap sederhana.
-- ============================================================

-- -----------------------------------------------------------
-- Table: presensi_config_global
-- Description: System-wide defaults (no time-related settings here)
-- -----------------------------------------------------------
CREATE TABLE `presensi_config_global` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `config_key` VARCHAR(50) NOT NULL,
    `config_value` TEXT,
    `config_type` ENUM('string', 'int', 'boolean', 'json') DEFAULT 'string',
    `description` VARCHAR(255),
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_config_group
-- Description: Group-level configuration (per role: guru, tendik, siswa)
-- -----------------------------------------------------------
CREATE TABLE `presensi_config_group` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_group` mediumint(8) UNSIGNED NOT NULL COMMENT 'FK to groups table',
    `nama_konfigurasi` VARCHAR(100) DEFAULT NULL COMMENT 'Display name',

    -- Shift assignment
    `id_shift_default` INT UNSIGNED DEFAULT NULL,
    `id_lokasi_default` INT UNSIGNED DEFAULT NULL,

    -- Validation method (single setting, no overlap!)
    `validation_mode` ENUM('gps', 'qr', 'gps_or_qr', 'manual', 'any') DEFAULT 'gps',

    -- Feature overrides (NULL = inherit from global)
    `require_photo` TINYINT(1) DEFAULT 0,
    `require_checkout` TINYINT(1) DEFAULT NULL,
    `allow_bypass` TINYINT(1) DEFAULT NULL,
    `enable_overtime` TINYINT(1) DEFAULT NULL,
    `overtime_require_approval` TINYINT(1) DEFAULT NULL,

    -- Calendar settings
    `holiday_mode` ENUM('all', 'national_only', 'none') DEFAULT 'all',
    `follow_academic_calendar` TINYINT(1) DEFAULT 0,

    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group` (`id_group`),
    KEY `fk_shift` (`id_shift_default`),
    KEY `fk_lokasi` (`id_lokasi_default`),

    CONSTRAINT `fk_cfg_group` FOREIGN KEY (`id_group`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cfg_shift` FOREIGN KEY (`id_shift_default`) REFERENCES `presensi_shift` (`id_shift`) ON DELETE SET NULL,
    CONSTRAINT `fk_cfg_lokasi` FOREIGN KEY (`id_lokasi_default`) REFERENCES `presensi_lokasi` (`id_lokasi`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 3: WORKING SCHEDULE (Explicit, not JSON)
-- ============================================================

-- -----------------------------------------------------------
-- Table: presensi_jadwal_kerja
-- Description: Explicit working days per group (replaces JSON working_days)
-- -----------------------------------------------------------
CREATE TABLE `presensi_jadwal_kerja` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_group` mediumint(8) UNSIGNED NOT NULL COMMENT 'FK to groups table',
    `day_of_week` TINYINT(1) NOT NULL COMMENT '1=Monday, 7=Sunday',
    `id_shift` INT UNSIGNED NOT NULL COMMENT 'Shift for this day',
    `is_active` TINYINT(1) DEFAULT 1,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_group_day` (`id_group`, `day_of_week`),
    KEY `fk_jadwal_shift` (`id_shift`),

    CONSTRAINT `fk_jadwal_group` FOREIGN KEY (`id_group`) REFERENCES `groups` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_jadwal_shift` FOREIGN KEY (`id_shift`) REFERENCES `presensi_shift` (`id_shift`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_jadwal_tendik
-- Description: Explicit working days per tipe_tendik (edge cases within Tendik group)
-- Notes: Satpam/TU/penjaga/dll tetap 1 group 'tendik', bedanya di tipe_tendik.
-- -----------------------------------------------------------
CREATE TABLE `presensi_jadwal_tendik` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `tipe_tendik` VARCHAR(20) NOT NULL COMMENT 'Matches master_tendik.tipe_tendik',
    `day_of_week` TINYINT(1) NOT NULL COMMENT '1=Monday, 7=Sunday',
    `id_shift` INT UNSIGNED NOT NULL COMMENT 'Shift for this day',
    `is_active` TINYINT(1) DEFAULT 1,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_tipe_day` (`tipe_tendik`, `day_of_week`),
    KEY `fk_jadwal_tendik_shift` (`id_shift`),

    CONSTRAINT `fk_jadwal_tendik_shift` FOREIGN KEY (`id_shift`) REFERENCES `presensi_shift` (`id_shift`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_jadwal_user
-- Description: Weekly schedule overrides per user (works for guru/siswa/tendik)
-- Notes: Use this for edge cases (satpam pagi vs satpam malam, guru panggilan, siswa sesi 1/2, dll).
--        NULL id_shift means explicit day off for that day.
-- -----------------------------------------------------------
CREATE TABLE `presensi_jadwal_user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED NOT NULL COMMENT 'FK to users table',
    `day_of_week` TINYINT(1) NOT NULL COMMENT '1=Monday, 7=Sunday',
    `id_shift` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = day off',
    `is_active` TINYINT(1) DEFAULT 1,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_day` (`id_user`, `day_of_week`),
    KEY `fk_jadwal_user_shift` (`id_shift`),

    CONSTRAINT `fk_jadwal_user_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_jadwal_user_shift` FOREIGN KEY (`id_shift`) REFERENCES `presensi_shift` (`id_shift`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_jadwal_override
-- Description: Date-specific shift changes (rotating shifts, holidays, special schedules)
-- -----------------------------------------------------------
CREATE TABLE `presensi_jadwal_override` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = applies to entire group',
    `id_group` mediumint(8) UNSIGNED DEFAULT NULL COMMENT 'NULL = user-specific only',
    `tanggal` DATE NOT NULL,
    `id_shift` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = day off (no shift)',
    `keterangan` VARCHAR(255) DEFAULT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user_date` (`id_user`, `tanggal`),
    UNIQUE KEY `uk_group_date` (`id_group`, `tanggal`),
    KEY `idx_tanggal` (`tanggal`),

    CONSTRAINT `fk_override_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_override_shift` FOREIGN KEY (`id_shift`) REFERENCES `presensi_shift` (`id_shift`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 4: TRANSACTION TABLES
-- ============================================================

-- -----------------------------------------------------------
-- Table: presensi_logs
-- Description: Core attendance records with clean structure
-- -----------------------------------------------------------
CREATE TABLE `presensi_logs` (
    `id_log` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED NOT NULL,
    `tanggal` DATE NOT NULL,
    `id_shift` INT UNSIGNED DEFAULT NULL,
    `id_lokasi` INT UNSIGNED DEFAULT NULL,

    -- Check-in details
    `jam_masuk` DATETIME DEFAULT NULL,
    `metode_masuk` ENUM('gps', 'qr', 'manual', 'bypass') DEFAULT NULL,
    `lat_masuk` DECIMAL(10, 8) DEFAULT NULL,
    `long_masuk` DECIMAL(11, 8) DEFAULT NULL,
    `foto_masuk` VARCHAR(255) DEFAULT NULL,
    `qr_token_masuk` VARCHAR(64) DEFAULT NULL,

    -- Check-out details
    `jam_pulang` DATETIME DEFAULT NULL,
    `metode_pulang` ENUM('gps', 'qr', 'manual', 'bypass') DEFAULT NULL,
    `lat_pulang` DECIMAL(10, 8) DEFAULT NULL,
    `long_pulang` DECIMAL(11, 8) DEFAULT NULL,
    `foto_pulang` VARCHAR(255) DEFAULT NULL,
    `qr_token_pulang` VARCHAR(64) DEFAULT NULL,

    -- Status (calculated)
    `status_kehadiran` ENUM(
        'Hadir', 'Terlambat', 'Pulang Awal',
        'Terlambat + Pulang Awal',
        'Izin', 'Sakit', 'Cuti', 'Dinas Luar', 'Alpha'
    ) DEFAULT 'Alpha',
    `keterangan` TEXT,

    `terlambat_menit` INT UNSIGNED DEFAULT 0,
    `pulang_awal_menit` INT UNSIGNED DEFAULT 0,
    `lembur_menit` INT UNSIGNED DEFAULT 0,
    `is_overnight` TINYINT(1) DEFAULT 0,

    -- Bypass reference
    `id_bypass` INT UNSIGNED DEFAULT NULL,

    -- Manual entry tracking
    `is_manual_entry` TINYINT(1) DEFAULT 0,
    `manual_entry_by` INT UNSIGNED DEFAULT NULL,
    `manual_entry_reason` TEXT,

    `device_info` VARCHAR(255) DEFAULT NULL,

    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_log`),
    UNIQUE KEY `uk_user_date` (`id_user`, `tanggal`),
    KEY `idx_tanggal` (`tanggal`),
    KEY `idx_status` (`status_kehadiran`),
    KEY `idx_shift` (`id_shift`),
    KEY `fk_log_shift` (`id_shift`),
    KEY `fk_log_lokasi` (`id_lokasi`),

    CONSTRAINT `fk_log_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_log_shift` FOREIGN KEY (`id_shift`) REFERENCES `presensi_shift` (`id_shift`) ON DELETE SET NULL,
    CONSTRAINT `fk_log_lokasi` FOREIGN KEY (`id_lokasi`) REFERENCES `presensi_lokasi` (`id_lokasi`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_qr_token
-- Description: QR code tokens for attendance
-- -----------------------------------------------------------
CREATE TABLE `presensi_qr_token` (
    `id_token` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `token_code` VARCHAR(64) NOT NULL,
    `token_type` ENUM('checkin', 'checkout', 'both') DEFAULT 'both',
    `id_lokasi` INT UNSIGNED DEFAULT NULL,
    `id_shift` INT UNSIGNED DEFAULT NULL,
    `tanggal` DATE NOT NULL,
    `valid_from` DATETIME NOT NULL,
    `valid_until` DATETIME NOT NULL,
    `created_by` INT UNSIGNED DEFAULT NULL COMMENT 'FK to users table',
    `used_count` INT UNSIGNED DEFAULT 0,
    `max_usage` INT UNSIGNED DEFAULT NULL COMMENT 'NULL = unlimited',
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_token`),
    UNIQUE KEY `uk_token` (`token_code`),
    KEY `idx_date_active` (`tanggal`, `is_active`, `valid_until`),
    KEY `fk_qt_lokasi` (`id_lokasi`),

    CONSTRAINT `fk_qt_lokasi` FOREIGN KEY (`id_lokasi`) REFERENCES `presensi_lokasi` (`id_lokasi`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_bypass
-- Description: Bypass requests for location/method restrictions
-- -----------------------------------------------------------
CREATE TABLE `presensi_bypass` (
    `id_bypass` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED NOT NULL,
    `tanggal` DATE NOT NULL,
    `tipe_bypass` ENUM('checkin', 'checkout', 'both') DEFAULT 'both',
    `alasan` TEXT NOT NULL,
    `lokasi_alternatif` VARCHAR(255) DEFAULT NULL,
    `latitude` DECIMAL(10, 8) DEFAULT NULL,
    `longitude` DECIMAL(11, 8) DEFAULT NULL,
    `foto_bukti` VARCHAR(255) DEFAULT NULL,

    `status` ENUM('pending', 'approved', 'rejected', 'used', 'expired') DEFAULT 'pending',
    `approved_by` INT UNSIGNED DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `catatan_admin` TEXT DEFAULT NULL,

    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_bypass`),
    KEY `idx_user_date` (`id_user`, `tanggal`),
    KEY `idx_status` (`status`),

    CONSTRAINT `fk_bypass_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_pengajuan
-- Description: Leave/permission requests
-- -----------------------------------------------------------
CREATE TABLE `presensi_pengajuan` (
    `id_pengajuan` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED NOT NULL,
    `tipe_pengajuan` ENUM('Izin', 'Sakit', 'Cuti', 'Dinas', 'Lembur', 'Koreksi', 'IzinKeluar') NOT NULL,
    `id_jenis_izin` INT UNSIGNED DEFAULT NULL,

    `tgl_mulai` DATE NOT NULL,
    `tgl_selesai` DATE NOT NULL,
    `jam_mulai` TIME DEFAULT NULL COMMENT 'For overtime/hourly leave',
    `jam_selesai` TIME DEFAULT NULL,
    `jumlah_hari` INT(11) DEFAULT 1,
    `keterangan` TEXT NOT NULL,
    `file_bukti` VARCHAR(255) DEFAULT NULL,

    `status` ENUM('Pending', 'Disetujui', 'Ditolak', 'Dibatalkan') DEFAULT 'Pending',
    `approved_by` INT UNSIGNED DEFAULT NULL,
    `approved_at` DATETIME DEFAULT NULL,
    `alasan_tolak` TEXT DEFAULT NULL,

    `is_synced` TINYINT(1) DEFAULT 0 COMMENT 'Synced to presensi_logs',
    `synced_at` DATETIME DEFAULT NULL,

    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_pengajuan`),
    KEY `idx_id_user` (`id_user`),
    KEY `idx_status` (`status`),
    KEY `idx_tgl_mulai` (`tgl_mulai`),
    KEY `idx_id_jenis_izin` (`id_jenis_izin`),

    CONSTRAINT `fk_pengajuan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_pengajuan_jenis` FOREIGN KEY (`id_jenis_izin`) REFERENCES `presensi_jenis_izin` (`id_jenis`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -----------------------------------------------------------
-- Table: presensi_audit_log
-- Description: Audit trail for all attendance actions
-- -----------------------------------------------------------
CREATE TABLE `presensi_audit_log` (
    `id_audit` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_log` BIGINT UNSIGNED DEFAULT NULL COMMENT 'Related attendance log',
    `id_user_target` INT UNSIGNED NOT NULL COMMENT 'User being affected',
    `action` VARCHAR(50) NOT NULL COMMENT 'checkin, checkout, manual_entry, edit, delete, approve, reject',
    `action_by` INT UNSIGNED NOT NULL COMMENT 'Who performed action',
    `action_by_role` VARCHAR(20) DEFAULT NULL,
    `data_before` JSON DEFAULT NULL COMMENT 'Snapshot before change',
    `data_after` JSON DEFAULT NULL COMMENT 'Snapshot after change',
    `ip_address` VARCHAR(45) DEFAULT NULL,
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,

    PRIMARY KEY (`id_audit`),
    KEY `idx_id_log` (`id_log`),
    KEY `idx_id_user_target` (`id_user_target`),
    KEY `idx_action` (`action`),
    KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- PART 5: SEED / DEFAULT DATA
-- ============================================================

-- Default shifts
INSERT INTO `presensi_shift` (`kode_shift`, `nama_shift`, `jam_masuk`, `jam_pulang`, `toleransi_masuk_menit`, `is_lintas_hari`) VALUES
('REG_PAGI', 'Regular Pagi', '07:00:00', '15:00:00', 15, 0),
('REG_SIANG', 'Regular Siang', '08:00:00', '16:00:00', 15, 0),
('SEC_PAGI', 'Shift Satpam Pagi', '06:00:00', '14:00:00', 0, 0),
('SEC_SIANG', 'Shift Satpam Siang', '14:00:00', '22:00:00', 0, 0),
('SEC_MALAM', 'Shift Satpam Malam', '22:00:00', '06:00:00', 0, 1);

-- Default location (Jakarta Monas as placeholder)
INSERT INTO `presensi_lokasi` (`kode_lokasi`, `nama_lokasi`, `alamat`, `latitude`, `longitude`, `radius_meter`, `is_default`) VALUES
('SEKOLAH', 'SMA Islam Al Azhar 5', 'Jalan Pilang Setrayasa No.31, Sukapura, Kec. Kejaksan, Kota Cirebon, Jawa Barat 45122', -6.698278097737746, 108.54418499259577, 1000, 1);

-- Default leave types
INSERT INTO `presensi_jenis_izin` (`nama_izin`, `kode_izin`, `kurangi_cuti`, `butuh_file`, `max_hari`, `status_presensi`) VALUES
('Sakit', 'SAKIT', 0, 1, 14, 'Sakit'),
('Izin Pribadi', 'IZIN', 0, 0, 3, 'Izin'),
('Cuti Tahunan', 'CUTI', 1, 0, 12, 'Cuti'),
('Dinas Luar', 'DINAS', 0, 1, NULL, 'Dinas Luar'),
('Cuti Melahirkan', 'MELAHIRKAN', 0, 1, 90, 'Cuti'),
('Cuti Menikah', 'NIKAH', 0, 1, 3, 'Cuti');

-- Global configuration (system-level only)
INSERT INTO `presensi_config_global` (`config_key`, `config_value`, `config_type`, `description`) VALUES
('max_bypass_per_month', '3', 'int', 'Maximum bypass requests per user per month'),
('bypass_auto_approve', '0', 'boolean', 'Auto-approve bypass requests'),
('qr_validity_minutes', '5', 'int', 'QR code validity duration in minutes'),
('qr_refresh_interval', '60', 'int', 'QR refresh interval in seconds'),
('enable_overtime', '0', 'boolean', 'Enable overtime tracking'),
('overtime_require_approval', '1', 'boolean', 'Require approval for overtime'),
('min_overtime_minutes', '30', 'int', 'Minimum minutes to count as overtime'),
('auto_alpha_enabled', '1', 'boolean', 'Automatically mark absent as Alpha'),
('auto_alpha_time', '23:00', 'string', 'Time to run auto-alpha process'),
('timezone', 'Asia/Jakarta', 'string', 'System timezone');

-- Default group configurations (if groups exist)
INSERT INTO `presensi_config_group` (`id_group`, `nama_konfigurasi`, `id_shift_default`, `validation_mode`, `holiday_mode`, `follow_academic_calendar`)
SELECT g.id, 'Guru', 1, 'manual', 'all', 1
FROM `groups` g
WHERE g.name = 'guru'
LIMIT 1;

INSERT INTO `presensi_config_group` (`id_group`, `nama_konfigurasi`, `id_shift_default`, `validation_mode`, `holiday_mode`, `follow_academic_calendar`)
SELECT g.id, 'Tendik', 1, 'manual', 'all', 0
FROM `groups` g
WHERE g.name = 'tendik'
LIMIT 1;

INSERT INTO `presensi_config_group` (`id_group`, `nama_konfigurasi`, `id_shift_default`, `validation_mode`, `holiday_mode`, `follow_academic_calendar`)
SELECT g.id, 'Siswa', 1, 'manual', 'national_only', 1
FROM `groups` g
WHERE g.name = 'siswa'
LIMIT 1;

-- Default presensi schedules (Mon-Fri)
INSERT INTO `presensi_jadwal_kerja` (`id_group`, `day_of_week`, `id_shift`)
SELECT g.id, d.day_num, 1
FROM `groups` g
CROSS JOIN (SELECT 1 AS day_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) d
WHERE g.name IN ('guru', 'tendik', 'siswa');

SET FOREIGN_KEY_CHECKS = 1;
