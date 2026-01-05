SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Insert tendik group (karyawan is legacy, no longer used)
INSERT INTO `groups` (`name`, `description`)
VALUES ('tendik', 'Tenaga Kependidikan')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

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

SET FOREIGN_KEY_CHECKS = 1;
