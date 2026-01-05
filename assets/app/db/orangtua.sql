SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Insert orangtua group (tendik group is created in tendik.sql)
INSERT INTO `groups` (`name`, `description`)
VALUES ('orangtua', 'Orang Tua Siswa')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

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

ALTER TABLE `master_siswa`
  ADD COLUMN `id_user_orangtua` int(11) UNSIGNED DEFAULT NULL AFTER `username`,
  ADD KEY `idx_id_user_orangtua` (`id_user_orangtua`),
  ADD CONSTRAINT `fk_siswa_orangtua_user` FOREIGN KEY (`id_user_orangtua`) REFERENCES `users` (`id`) ON DELETE SET NULL;

-- MySQL/MariaDB compatibility: CREATE INDEX does not reliably support IF NOT EXISTS.
-- Use information_schema.statistics checks + dynamic SQL.

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
