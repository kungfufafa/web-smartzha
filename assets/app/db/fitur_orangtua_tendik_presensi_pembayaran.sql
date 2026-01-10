-- ============================================================
-- SMARTZHA - Bundle Fitur Tambahan
-- Covers:
--   - orangtua.sql
--   - tendik.sql
--   - presensi.sql (Presensi V3)
--   - pembayaran.sql (QRIS statis)
--
-- Cara pakai:
--   1) Pastikan schema utama sudah di-import (mis. assets/app/db/master.sql)
--      (tabel minimal yang harus sudah ada: users, groups, master_siswa,
--       login_attempts, kelas_siswa, master_kelas)
--   2) Import file ini pada database yang SUDAH dipilih (phpMyAdmin: klik DB dulu)
--      atau via CLI:
--        mysql -uUSER -p NAMA_DB < assets/app/db/fitur_orangtua_tendik_presensi_pembayaran.sql
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1) GROUPS (roles)
-- ============================================================

-- Tendik group (karyawan is legacy, no longer used)
INSERT INTO `groups` (`name`, `description`)
VALUES ('tendik', 'Tenaga Kependidikan')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- Orangtua group (tendik group is created above)
INSERT INTO `groups` (`name`, `description`)
VALUES ('orangtua', 'Orang Tua Siswa')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- ============================================================
-- 2) MASTER DATA: TENDIK & ORANGTUA
-- ============================================================

CREATE TABLE IF NOT EXISTS `master_tendik` (
  `id_tendik` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED DEFAULT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `nama_tendik` varchar(100) NOT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `tempat_lahir` varchar(50) DEFAULT NULL,
  `tgl_lahir` date DEFAULT NULL,
  `agama` varchar(20) DEFAULT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `alamat` text,
  `tipe_tendik` enum('TU','PUSTAKAWAN','LABORAN','SATPAM','KEBERSIHAN','PENJAGA','TEKNISI','DRIVER','LAINNYA') DEFAULT 'LAINNYA',
  `jabatan` varchar(50) DEFAULT NULL,
  `status_kepegawaian` enum('PNS','PPPK','Honorer','Kontrak') DEFAULT 'Honorer',
  `tanggal_masuk` date DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tendik`),
  UNIQUE KEY `uk_nip` (`nip`),
  KEY `idx_id_user` (`id_user`),
  KEY `idx_tipe_tendik` (`tipe_tendik`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_tendik_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `master_orangtua` (
  `id_orangtua` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED DEFAULT NULL,
  `nama_lengkap` varchar(150) NOT NULL,
  `nik` varchar(16) DEFAULT NULL,
  `no_hp` varchar(20) NOT NULL,
  `email` varchar(100) DEFAULT NULL,
  `jenis_kelamin` enum('L','P') DEFAULT NULL,
  `agama` varchar(20) DEFAULT NULL,
  `pendidikan_terakhir` varchar(50) DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `alamat` text,
  `kota` varchar(100) DEFAULT NULL,
  `provinsi` varchar(100) DEFAULT NULL,
  `kode_pos` int(5) DEFAULT NULL,
  `foto` varchar(255) DEFAULT 'orangtua.png',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_orangtua`),
  UNIQUE KEY `uk_nik` (`nik`),
  UNIQUE KEY `uk_no_hp` (`no_hp`),
  KEY `idx_id_user` (`id_user`),
  KEY `idx_is_active` (`is_active`),
  CONSTRAINT `fk_orangtua_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `parent_siswa` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `id_siswa` int(7) NOT NULL,
  `id_orangtua` int(11) DEFAULT NULL,
  `relasi` enum('ayah','ibu','wali') NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_by` int(11) UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_siswa` (`id_user`, `id_siswa`),
  KEY `idx_siswa` (`id_siswa`),
  KEY `idx_user` (`id_user`),
  KEY `idx_id_orangtua` (`id_orangtua`),
  CONSTRAINT `fk_parent_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_parent_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `master_siswa` (`id_siswa`) ON DELETE CASCADE,
  CONSTRAINT `fk_parent_orangtua` FOREIGN KEY (`id_orangtua`) REFERENCES `master_orangtua` (`id_orangtua`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- master_siswa.id_user_orangtua + index + FK (dibuat idempotent)
SET @col_exists := (
  SELECT COUNT(1)
  FROM information_schema.columns
  WHERE table_schema = DATABASE()
    AND table_name = 'master_siswa'
    AND column_name = 'id_user_orangtua'
);
SET @sql := IF(
  @col_exists = 0,
  'ALTER TABLE `master_siswa` ADD COLUMN `id_user_orangtua` int(11) UNSIGNED DEFAULT NULL AFTER `username`',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'master_siswa'
    AND index_name = 'idx_id_user_orangtua'
);
SET @sql := IF(
  @idx_exists = 0,
  'CREATE INDEX `idx_id_user_orangtua` ON `master_siswa` (`id_user_orangtua`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @fk_exists := (
  SELECT COUNT(1)
  FROM information_schema.referential_constraints
  WHERE constraint_schema = DATABASE()
    AND constraint_name = 'fk_siswa_orangtua_user'
    AND table_name = 'master_siswa'
);
SET @sql := IF(
  @fk_exists = 0,
  'ALTER TABLE `master_siswa` ADD CONSTRAINT `fk_siswa_orangtua_user` FOREIGN KEY (`id_user_orangtua`) REFERENCES `users` (`id`) ON DELETE SET NULL',
  'SELECT 1'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ============================================================
-- 3) PRESENSI V3 - COMPLETE SCHEMA
-- ============================================================

CREATE TABLE IF NOT EXISTS `presensi_shift` (
    `id_shift` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `kode_shift` VARCHAR(10) NOT NULL,
    `nama_shift` VARCHAR(50) NOT NULL,
    `deskripsi` TEXT,
    `jam_masuk` TIME NOT NULL,
    `jam_pulang` TIME NOT NULL,
    `is_lintas_hari` TINYINT(1) DEFAULT 0 COMMENT 'Shift crosses midnight',
    `toleransi_masuk_menit` INT(3) UNSIGNED DEFAULT 15 COMMENT 'Late tolerance in minutes',
    `toleransi_pulang_menit` INT(3) UNSIGNED DEFAULT 0 COMMENT 'Early leave tolerance in minutes',
    `earliest_checkin` TIME DEFAULT NULL COMMENT 'Earliest allowed check-in',
    `latest_checkin` TIME DEFAULT NULL COMMENT 'Latest allowed check-in (after = absent)',
    `earliest_checkout` TIME DEFAULT NULL COMMENT 'Earliest allowed check-out',
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

CREATE TABLE IF NOT EXISTS `presensi_lokasi` (
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

CREATE TABLE IF NOT EXISTS `presensi_jenis_izin` (
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

CREATE TABLE IF NOT EXISTS `presensi_hari_libur` (
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

CREATE TABLE IF NOT EXISTS `presensi_config_global` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `config_key` VARCHAR(50) NOT NULL,
    `config_value` TEXT,
    `config_type` ENUM('string', 'int', 'boolean', 'json') DEFAULT 'string',
    `description` VARCHAR(255),
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_config_key` (`config_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `presensi_config_group` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_group` mediumint(8) UNSIGNED NOT NULL COMMENT 'FK to groups table',
    `nama_konfigurasi` VARCHAR(100) DEFAULT NULL COMMENT 'Display name',
    `id_shift_default` INT UNSIGNED DEFAULT NULL,
    `id_lokasi_default` INT UNSIGNED DEFAULT NULL,
    `validation_mode` ENUM('gps', 'qr', 'gps_or_qr', 'manual', 'any') DEFAULT 'gps',
    `require_photo` TINYINT(1) DEFAULT 0,
    `require_checkout` TINYINT(1) DEFAULT NULL,
    `allow_bypass` TINYINT(1) DEFAULT NULL,
    `enable_overtime` TINYINT(1) DEFAULT NULL,
    `overtime_require_approval` TINYINT(1) DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS `presensi_config_user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED NOT NULL COMMENT 'FK to users table',
    `validation_mode` ENUM('gps', 'qr', 'gps_or_qr', 'manual', 'any') DEFAULT NULL,
    `require_photo` TINYINT(1) DEFAULT 0,
    `require_checkout` TINYINT(1) DEFAULT NULL,
    `allow_bypass` TINYINT(1) DEFAULT NULL,
    `is_active` TINYINT(1) DEFAULT 1,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_user` (`id_user`),
    CONSTRAINT `fk_cfg_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `presensi_jadwal_kerja` (
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

CREATE TABLE IF NOT EXISTS `presensi_jadwal_tendik` (
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

CREATE TABLE IF NOT EXISTS `presensi_jadwal_user` (
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

CREATE TABLE IF NOT EXISTS `presensi_jadwal_override` (
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

CREATE TABLE IF NOT EXISTS `presensi_logs` (
    `id_log` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `id_user` INT UNSIGNED NOT NULL,
    `tanggal` DATE NOT NULL,
    `id_shift` INT UNSIGNED DEFAULT NULL,
    `id_lokasi` INT UNSIGNED DEFAULT NULL,
    `jam_masuk` DATETIME DEFAULT NULL,
    `metode_masuk` ENUM('gps', 'qr', 'manual', 'bypass') DEFAULT NULL,
    `lat_masuk` DECIMAL(10, 8) DEFAULT NULL,
    `long_masuk` DECIMAL(11, 8) DEFAULT NULL,
    `foto_masuk` VARCHAR(255) DEFAULT NULL,
    `qr_token_masuk` VARCHAR(64) DEFAULT NULL,
    `jam_pulang` DATETIME DEFAULT NULL,
    `metode_pulang` ENUM('gps', 'qr', 'manual', 'bypass') DEFAULT NULL,
    `lat_pulang` DECIMAL(10, 8) DEFAULT NULL,
    `long_pulang` DECIMAL(11, 8) DEFAULT NULL,
    `foto_pulang` VARCHAR(255) DEFAULT NULL,
    `qr_token_pulang` VARCHAR(64) DEFAULT NULL,
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
    `id_bypass` INT UNSIGNED DEFAULT NULL,
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

CREATE TABLE IF NOT EXISTS `presensi_qr_token` (
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

CREATE TABLE IF NOT EXISTS `presensi_bypass` (
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

CREATE TABLE IF NOT EXISTS `presensi_pengajuan` (
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

CREATE TABLE IF NOT EXISTS `presensi_audit_log` (
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

-- Presensi default data (idempotent)
INSERT INTO `presensi_shift` (`kode_shift`, `nama_shift`, `jam_masuk`, `jam_pulang`, `toleransi_masuk_menit`, `is_lintas_hari`)
VALUES
('REG_PAGI', 'Regular Pagi', '07:00:00', '15:00:00', 15, 0),
('REG_SIANG', 'Regular Siang', '08:00:00', '16:00:00', 15, 0),
('SEC_PAGI', 'Shift Satpam Pagi', '06:00:00', '14:00:00', 0, 0),
('SEC_SIANG', 'Shift Satpam Siang', '14:00:00', '22:00:00', 0, 0),
('SEC_MALAM', 'Shift Satpam Malam', '22:00:00', '06:00:00', 0, 1)
ON DUPLICATE KEY UPDATE
  `nama_shift` = VALUES(`nama_shift`),
  `jam_masuk` = VALUES(`jam_masuk`),
  `jam_pulang` = VALUES(`jam_pulang`),
  `toleransi_masuk_menit` = VALUES(`toleransi_masuk_menit`),
  `is_lintas_hari` = VALUES(`is_lintas_hari`);

INSERT INTO `presensi_lokasi` (`kode_lokasi`, `nama_lokasi`, `alamat`, `latitude`, `longitude`, `radius_meter`, `is_default`)
VALUES
('SEKOLAH', 'SMA Islam Al Azhar 5', 'Jalan Pilang Setrayasa No.31, Sukapura, Kec. Kejaksan, Kota Cirebon, Jawa Barat 45122', -6.698278097737746, 108.54418499259577, 1000, 1)
ON DUPLICATE KEY UPDATE
  `nama_lokasi` = VALUES(`nama_lokasi`),
  `alamat` = VALUES(`alamat`),
  `latitude` = VALUES(`latitude`),
  `longitude` = VALUES(`longitude`),
  `radius_meter` = VALUES(`radius_meter`),
  `is_default` = VALUES(`is_default`);

INSERT INTO `presensi_jenis_izin` (`nama_izin`, `kode_izin`, `kurangi_cuti`, `butuh_file`, `max_hari`, `status_presensi`)
VALUES
('Sakit', 'SAKIT', 0, 1, 14, 'Sakit'),
('Izin Pribadi', 'IZIN', 0, 0, 3, 'Izin'),
('Cuti Tahunan', 'CUTI', 1, 0, 12, 'Cuti'),
('Dinas Luar', 'DINAS', 0, 1, NULL, 'Dinas Luar'),
('Cuti Melahirkan', 'MELAHIRKAN', 0, 1, 90, 'Cuti'),
('Cuti Menikah', 'NIKAH', 0, 1, 3, 'Cuti')
ON DUPLICATE KEY UPDATE
  `nama_izin` = VALUES(`nama_izin`),
  `kurangi_cuti` = VALUES(`kurangi_cuti`),
  `butuh_file` = VALUES(`butuh_file`),
  `max_hari` = VALUES(`max_hari`),
  `status_presensi` = VALUES(`status_presensi`);

INSERT INTO `presensi_config_global` (`config_key`, `config_value`, `config_type`, `description`)
VALUES
('max_bypass_per_month', '3', 'int', 'Maximum bypass requests per user per month'),
('bypass_auto_approve', '0', 'boolean', 'Auto-approve bypass requests'),
('qr_validity_minutes', '5', 'int', 'QR code validity duration in minutes'),
('qr_refresh_interval', '60', 'int', 'QR refresh interval in seconds'),
('enable_overtime', '0', 'boolean', 'Enable overtime tracking'),
('overtime_require_approval', '1', 'boolean', 'Require approval for overtime'),
('min_overtime_minutes', '30', 'int', 'Minimum minutes to count as overtime'),
('auto_alpha_enabled', '1', 'boolean', 'Automatically mark absent as Alpha'),
('auto_alpha_time', '23:00', 'string', 'Time to run auto-alpha process'),
('timezone', 'Asia/Jakarta', 'string', 'System timezone')
ON DUPLICATE KEY UPDATE
  `config_value` = VALUES(`config_value`),
  `config_type` = VALUES(`config_type`),
  `description` = VALUES(`description`);

INSERT INTO `presensi_config_group` (`id_group`, `nama_konfigurasi`, `id_shift_default`, `validation_mode`, `holiday_mode`, `follow_academic_calendar`)
SELECT g.id, 'Guru', 1, 'manual', 'all', 1
FROM `groups` g
WHERE g.name = 'guru'
LIMIT 1
ON DUPLICATE KEY UPDATE
  `nama_konfigurasi` = VALUES(`nama_konfigurasi`),
  `id_shift_default` = VALUES(`id_shift_default`),
  `validation_mode` = VALUES(`validation_mode`),
  `holiday_mode` = VALUES(`holiday_mode`),
  `follow_academic_calendar` = VALUES(`follow_academic_calendar`);

INSERT INTO `presensi_config_group` (`id_group`, `nama_konfigurasi`, `id_shift_default`, `validation_mode`, `holiday_mode`, `follow_academic_calendar`)
SELECT g.id, 'Tendik', 1, 'manual', 'all', 0
FROM `groups` g
WHERE g.name = 'tendik'
LIMIT 1
ON DUPLICATE KEY UPDATE
  `nama_konfigurasi` = VALUES(`nama_konfigurasi`),
  `id_shift_default` = VALUES(`id_shift_default`),
  `validation_mode` = VALUES(`validation_mode`),
  `holiday_mode` = VALUES(`holiday_mode`),
  `follow_academic_calendar` = VALUES(`follow_academic_calendar`);

INSERT INTO `presensi_config_group` (`id_group`, `nama_konfigurasi`, `id_shift_default`, `validation_mode`, `holiday_mode`, `follow_academic_calendar`)
SELECT g.id, 'Siswa', 1, 'manual', 'national_only', 1
FROM `groups` g
WHERE g.name = 'siswa'
LIMIT 1
ON DUPLICATE KEY UPDATE
  `nama_konfigurasi` = VALUES(`nama_konfigurasi`),
  `id_shift_default` = VALUES(`id_shift_default`),
  `validation_mode` = VALUES(`validation_mode`),
  `holiday_mode` = VALUES(`holiday_mode`),
  `follow_academic_calendar` = VALUES(`follow_academic_calendar`);

INSERT INTO `presensi_jadwal_kerja` (`id_group`, `day_of_week`, `id_shift`)
SELECT g.id, d.day_num, 1
FROM `groups` g
CROSS JOIN (SELECT 1 AS day_num UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5) d
WHERE g.name IN ('guru', 'tendik', 'siswa')
ON DUPLICATE KEY UPDATE
  `id_shift` = VALUES(`id_shift`),
  `is_active` = 1;

-- ============================================================
-- 4) PEMBAYARAN SEKOLAH - QRIS STATIS
-- ============================================================

CREATE TABLE IF NOT EXISTS `pembayaran_config` (
  `id_config` INT(11) NOT NULL AUTO_INCREMENT,
  `qris_image` VARCHAR(255) DEFAULT NULL COMMENT 'Path ke gambar QRIS statis',
  `qris_string` TEXT DEFAULT NULL COMMENT 'String QRIS (EMV) untuk generate QRIS dinamis',
  `qris_merchant_name` VARCHAR(100) DEFAULT NULL COMMENT 'Nama merchant QRIS',
  `bank_name` VARCHAR(100) DEFAULT NULL COMMENT 'Nama bank untuk transfer',
  `bank_account` VARCHAR(50) DEFAULT NULL COMMENT 'Nomor rekening',
  `bank_holder` VARCHAR(100) DEFAULT NULL COMMENT 'Nama pemilik rekening',
  `payment_instruction` TEXT DEFAULT NULL COMMENT 'Instruksi pembayaran',
  `admin_fee` DECIMAL(12,2) DEFAULT 0 COMMENT 'Biaya admin jika ada',
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_config`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default config (idempotent: hanya insert jika tabel masih kosong)
INSERT INTO `pembayaran_config` (`qris_merchant_name`, `bank_name`, `bank_account`, `bank_holder`, `payment_instruction`)
SELECT 'Sekolah', 'Bank BRI', '1234567890', 'Bendahara Sekolah',
       'Silakan scan QRIS atau transfer ke rekening di atas. Setelah melakukan pembayaran, upload bukti transfer.'
WHERE NOT EXISTS (SELECT 1 FROM `pembayaran_config`);

CREATE TABLE IF NOT EXISTS `pembayaran_jenis` (
  `id_jenis` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_jenis` VARCHAR(20) NOT NULL COMMENT 'SPP, SRG, BKU, dll',
  `nama_jenis` VARCHAR(100) NOT NULL COMMENT 'Nama lengkap jenis tagihan',
  `nominal_default` DECIMAL(12,2) DEFAULT 0 COMMENT 'Nominal default',
  `is_recurring` TINYINT(1) DEFAULT 0 COMMENT '1=bulanan, 0=sekali bayar',
  `keterangan` TEXT DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_jenis`),
  UNIQUE KEY `uk_kode_jenis` (`kode_jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default jenis tagihan (idempotent)
INSERT INTO `pembayaran_jenis` (`kode_jenis`, `nama_jenis`, `nominal_default`, `is_recurring`, `keterangan`) VALUES
('SPP', 'SPP Bulanan', 500000, 1, 'Sumbangan Pembinaan Pendidikan per bulan'),
('DAFTAR', 'Biaya Daftar Ulang', 1000000, 0, 'Biaya pendaftaran ulang tahunan'),
('SERAGAM', 'Seragam Sekolah', 0, 0, 'Biaya seragam sekolah'),
('BUKU', 'Buku Pelajaran', 0, 0, 'Biaya buku pelajaran'),
('KEGIATAN', 'Biaya Kegiatan', 0, 0, 'Biaya kegiatan sekolah')
ON DUPLICATE KEY UPDATE
  `nama_jenis` = VALUES(`nama_jenis`),
  `nominal_default` = VALUES(`nominal_default`),
  `is_recurring` = VALUES(`is_recurring`),
  `keterangan` = VALUES(`keterangan`),
  `is_active` = 1;

CREATE TABLE IF NOT EXISTS `pembayaran_tagihan` (
  `id_tagihan` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_tagihan` VARCHAR(30) NOT NULL COMMENT 'Format: TG-YYYYMM-XXXXX',
  `id_siswa` INT(11) NOT NULL,
  `id_jenis` INT(11) NOT NULL,
  `id_tp` INT(11) NOT NULL COMMENT 'Tahun Pelajaran',
  `id_smt` INT(11) NOT NULL COMMENT 'Semester',
  `bulan` TINYINT(2) DEFAULT NULL COMMENT 'Untuk SPP bulanan (1-12)',
  `tahun` SMALLINT(4) DEFAULT NULL COMMENT 'Untuk SPP bulanan',
  `nominal` DECIMAL(12,2) NOT NULL,
  `diskon` DECIMAL(12,2) DEFAULT 0,
  `denda` DECIMAL(12,2) DEFAULT 0,
  `total` DECIMAL(12,2) GENERATED ALWAYS AS (nominal - diskon + denda) STORED,
  `jatuh_tempo` DATE NOT NULL,
  `keterangan` TEXT DEFAULT NULL,
  `status` ENUM('belum_bayar','menunggu_verifikasi','lunas','ditolak','expired') DEFAULT 'belum_bayar',
  `created_by` INT(11) DEFAULT NULL COMMENT 'Admin yang membuat',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_tagihan`),
  UNIQUE KEY `uk_kode_tagihan` (`kode_tagihan`),
  KEY `idx_siswa` (`id_siswa`),
  KEY `idx_jenis` (`id_jenis`),
  KEY `idx_status` (`status`),
  KEY `idx_jatuh_tempo` (`jatuh_tempo`),
  KEY `idx_periode` (`id_tp`, `id_smt`),
  KEY `idx_bulan_tahun` (`bulan`, `tahun`),
  CONSTRAINT `fk_tagihan_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `master_siswa` (`id_siswa`) ON DELETE CASCADE,
  CONSTRAINT `fk_tagihan_jenis` FOREIGN KEY (`id_jenis`) REFERENCES `pembayaran_jenis` (`id_jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pembayaran_transaksi` (
  `id_transaksi` INT(11) NOT NULL AUTO_INCREMENT,
  `kode_transaksi` VARCHAR(30) NOT NULL COMMENT 'Format: TRX-YYYYMMDD-XXXXX',
  `id_tagihan` INT(11) NOT NULL,
  `id_siswa` INT(11) NOT NULL,
  `metode_bayar` ENUM('qris','transfer') DEFAULT 'qris',
  `nominal_bayar` DECIMAL(12,2) NOT NULL,
  `bukti_bayar` VARCHAR(255) DEFAULT NULL COMMENT 'Path file bukti',
  `bukti_bayar_hash` VARCHAR(64) DEFAULT NULL COMMENT 'SHA256 untuk detect duplikat',
  `tanggal_bayar` DATE NOT NULL COMMENT 'Tanggal siswa bayar (self-reported)',
  `waktu_upload` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `catatan_siswa` TEXT DEFAULT NULL COMMENT 'Catatan dari siswa',
  `status` ENUM('pending','verified','rejected','cancelled') DEFAULT 'pending',
  `verified_by` INT(11) DEFAULT NULL COMMENT 'Admin yang verifikasi',
  `verified_at` DATETIME DEFAULT NULL,
  `catatan_admin` TEXT DEFAULT NULL COMMENT 'Alasan reject/notes',
  `reject_count` TINYINT(2) DEFAULT 0 COMMENT 'Berapa kali ditolak',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_transaksi`),
  UNIQUE KEY `uk_kode_transaksi` (`kode_transaksi`),
  KEY `idx_tagihan` (`id_tagihan`),
  KEY `idx_siswa` (`id_siswa`),
  KEY `idx_status` (`status`),
  KEY `idx_tanggal` (`tanggal_bayar`),
  KEY `idx_bukti_hash` (`bukti_bayar_hash`),
  KEY `idx_verified_at` (`verified_at`),
  CONSTRAINT `fk_transaksi_tagihan` FOREIGN KEY (`id_tagihan`) REFERENCES `pembayaran_tagihan` (`id_tagihan`) ON DELETE CASCADE,
  CONSTRAINT `fk_transaksi_siswa` FOREIGN KEY (`id_siswa`) REFERENCES `master_siswa` (`id_siswa`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `pembayaran_log` (
  `id_log` INT(11) NOT NULL AUTO_INCREMENT,
  `id_transaksi` INT(11) DEFAULT NULL,
  `id_tagihan` INT(11) DEFAULT NULL,
  `action` VARCHAR(50) NOT NULL COMMENT 'create_tagihan, upload_bukti, verify_approve, verify_reject, etc',
  `status_before` VARCHAR(30) DEFAULT NULL,
  `status_after` VARCHAR(30) DEFAULT NULL,
  `data_snapshot` JSON DEFAULT NULL COMMENT 'State data saat action',
  `actor_id` INT(11) NOT NULL COMMENT 'User ID yang melakukan',
  `actor_type` ENUM('admin','siswa','system') DEFAULT 'admin',
  `actor_name` VARCHAR(100) DEFAULT NULL COMMENT 'Nama actor untuk display',
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(500) DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `idx_transaksi` (`id_transaksi`),
  KEY `idx_tagihan` (`id_tagihan`),
  KEY `idx_action` (`action`),
  KEY `idx_actor` (`actor_id`, `actor_type`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE OR REPLACE VIEW `v_pembayaran_tagihan` AS
SELECT 
  t.id_tagihan,
  t.kode_tagihan,
  t.id_siswa,
  s.nama AS nama_siswa,
  s.nis,
  s.nisn,
  k.nama_kelas,
  k.id_kelas,
  j.kode_jenis,
  j.nama_jenis,
  t.id_tp,
  t.id_smt,
  t.bulan,
  t.tahun,
  t.nominal,
  t.diskon,
  t.denda,
  t.total,
  t.jatuh_tempo,
  t.status,
  t.keterangan,
  t.created_at,
  t.updated_at
FROM pembayaran_tagihan t
JOIN master_siswa s ON t.id_siswa = s.id_siswa
JOIN pembayaran_jenis j ON t.id_jenis = j.id_jenis
LEFT JOIN kelas_siswa ks ON s.id_siswa = ks.id_siswa AND ks.id_tp = t.id_tp AND ks.id_smt = t.id_smt
LEFT JOIN master_kelas k ON ks.id_kelas = k.id_kelas;

CREATE OR REPLACE VIEW `v_pembayaran_transaksi` AS
SELECT 
  tr.id_transaksi,
  tr.kode_transaksi,
  tr.id_tagihan,
  tg.kode_tagihan,
  tr.id_siswa,
  s.nama AS nama_siswa,
  s.nis,
  k.nama_kelas,
  j.nama_jenis,
  tg.bulan,
  tg.tahun,
  tr.metode_bayar,
  tr.nominal_bayar,
  tr.bukti_bayar,
  tr.tanggal_bayar,
  tr.waktu_upload,
  tr.catatan_siswa,
  tr.status,
  tr.verified_by,
  tr.verified_at,
  tr.catatan_admin,
  tr.reject_count,
  tr.created_at
FROM pembayaran_transaksi tr
JOIN pembayaran_tagihan tg ON tr.id_tagihan = tg.id_tagihan
JOIN master_siswa s ON tr.id_siswa = s.id_siswa
JOIN pembayaran_jenis j ON tg.id_jenis = j.id_jenis
LEFT JOIN kelas_siswa ks ON s.id_siswa = ks.id_siswa AND ks.id_tp = tg.id_tp AND ks.id_smt = tg.id_smt
LEFT JOIN master_kelas k ON ks.id_kelas = k.id_kelas;

-- Indexes + triggers (pakai DELIMITER)
DELIMITER //

DROP PROCEDURE IF EXISTS `sp_create_pembayaran_indexes`//
CREATE PROCEDURE `sp_create_pembayaran_indexes`()
BEGIN
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'pembayaran_tagihan' 
    AND INDEX_NAME = 'idx_tagihan_siswa_status'
  ) THEN
    CREATE INDEX `idx_tagihan_siswa_status` ON `pembayaran_tagihan` (`id_siswa`, `status`);
  END IF;
  
  IF NOT EXISTS (
    SELECT 1 FROM INFORMATION_SCHEMA.STATISTICS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'pembayaran_transaksi' 
    AND INDEX_NAME = 'idx_transaksi_pending'
  ) THEN
    CREATE INDEX `idx_transaksi_pending` ON `pembayaran_transaksi` (`status`, `waktu_upload`);
  END IF;
END//

CALL `sp_create_pembayaran_indexes`()//
DROP PROCEDURE IF EXISTS `sp_create_pembayaran_indexes`//

DROP TRIGGER IF EXISTS `before_insert_tagihan`//
CREATE TRIGGER `before_insert_tagihan`
BEFORE INSERT ON `pembayaran_tagihan`
FOR EACH ROW
BEGIN
  DECLARE next_num INT;
  DECLARE prefix VARCHAR(10);
  
  IF NEW.kode_tagihan IS NULL OR NEW.kode_tagihan = '' THEN
    SET prefix = CONCAT('TG-', DATE_FORMAT(NOW(), '%Y%m'), '-');
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(kode_tagihan, 11) AS UNSIGNED)), 0) + 1 
    INTO next_num
    FROM pembayaran_tagihan 
    WHERE kode_tagihan LIKE CONCAT(prefix, '%');
    
    SET NEW.kode_tagihan = CONCAT(prefix, LPAD(next_num, 5, '0'));
  END IF;
END//

DROP TRIGGER IF EXISTS `before_insert_transaksi`//
CREATE TRIGGER `before_insert_transaksi`
BEFORE INSERT ON `pembayaran_transaksi`
FOR EACH ROW
BEGIN
  DECLARE next_num INT;
  DECLARE prefix VARCHAR(15);
  
  IF NEW.kode_transaksi IS NULL OR NEW.kode_transaksi = '' THEN
    SET prefix = CONCAT('TRX-', DATE_FORMAT(NOW(), '%Y%m%d'), '-');
    
    SELECT COALESCE(MAX(CAST(SUBSTRING(kode_transaksi, 14) AS UNSIGNED)), 0) + 1 
    INTO next_num
    FROM pembayaran_transaksi 
    WHERE kode_transaksi LIKE CONCAT(prefix, '%');
    
    SET NEW.kode_transaksi = CONCAT(prefix, LPAD(next_num, 5, '0'));
  END IF;
END//

DELIMITER ;

-- ============================================================
-- 5) INDEX COMPAT (MySQL/MariaDB) - optional but safe
-- ============================================================

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND index_name = 'idx_users_username'
);
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX `idx_users_username` ON `users` (`username`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'users'
    AND index_name = 'idx_users_email'
);
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX `idx_users_email` ON `users` (`email`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @idx_exists := (
  SELECT COUNT(1)
  FROM information_schema.statistics
  WHERE table_schema = DATABASE()
    AND table_name = 'login_attempts'
    AND index_name = 'idx_login_attempts_login'
);
SET @sql := IF(@idx_exists = 0, 'CREATE INDEX `idx_login_attempts_login` ON `login_attempts` (`login`)', 'SELECT 1');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET FOREIGN_KEY_CHECKS = 1;
