SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for master_shift
-- ----------------------------
DROP TABLE IF EXISTS `master_shift`;
CREATE TABLE `master_shift` (
  `id_shift` int(11) NOT NULL AUTO_INCREMENT,
  `nama_shift` varchar(50) NOT NULL,
  `kode_shift` varchar(20) NOT NULL,
  `jam_masuk` time NOT NULL,
  `jam_pulang` time NOT NULL,
  `durasi_menit` int(11) GENERATED ALWAYS AS (TIMESTAMPDIFF(MINUTE, STR_TO_DATE(CONCAT('2000-01-01 ', jam_masuk)), STR_TO_DATE(CONCAT(IF(jam_pulang < jam_masuk, '2000-01-02 ', '2000-01-01 '), jam_pulang)))) STORED,
  `lintas_hari` tinyint(1) DEFAULT 0 COMMENT '1=Ya, 0=Tidak',
  `jam_awal_checkin` time DEFAULT NULL COMMENT 'Batas awal boleh absen',
  `jam_akhir_checkin` time DEFAULT NULL COMMENT 'Batas akhir terlambat',
  `jam_awal_checkout` time DEFAULT NULL COMMENT 'Batas awal boleh pulang',
  `jam_akhir_checkout` time DEFAULT NULL COMMENT 'Batas akhir absen pulang (overtime auto-cutoff)',
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_shift`),
  UNIQUE KEY `kode_shift` (`kode_shift`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sample Data
INSERT INTO `master_shift` (`nama_shift`, `kode_shift`, `jam_masuk`, `jam_pulang`, `lintas_hari`) VALUES
('Regular Pagi', 'REG_PAGI', '07:00:00', '15:00:00', 0),
('Shift Satpam Pagi', 'SEC_PAGI', '06:00:00', '14:00:00', 0),
('Shift Satpam Siang', 'SEC_SIANG', '14:00:00', '22:00:00', 0),
('Shift Satpam Malam', 'SEC_MALAM', '22:00:00', '06:00:00', 1);

-- ----------------------------
-- Table structure for master_karyawan
-- ----------------------------
DROP TABLE IF EXISTS `master_karyawan`;
CREATE TABLE `master_karyawan` (
  `id_karyawan` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED DEFAULT NULL,
  `nama_karyawan` varchar(100) NOT NULL,
  `nip` varchar(30) DEFAULT NULL,
  `jabatan` varchar(50) DEFAULT NULL COMMENT 'Satpam, Kebersihan, TU, dll',
  `no_hp` varchar(20) DEFAULT NULL,
  `alamat` text,
  `foto` varchar(255) DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id_karyawan`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `fk_karyawan_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for pegawai_shift
-- ----------------------------
DROP TABLE IF EXISTS `pegawai_shift`;
CREATE TABLE `pegawai_shift` (
  `id_pegawai_shift` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `tipe_shift` enum('fixed','rotating') NOT NULL DEFAULT 'fixed',
  `id_shift_fixed` int(11) DEFAULT NULL COMMENT 'Jika tipe fixed',
  `tgl_efektif` date NOT NULL,
  PRIMARY KEY (`id_pegawai_shift`),
  KEY `id_user` (`id_user`),
  KEY `id_shift_fixed` (`id_shift_fixed`),
  CONSTRAINT `fk_ps_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ps_shift` FOREIGN KEY (`id_shift_fixed`) REFERENCES `master_shift` (`id_shift`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for shift_jadwal
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
-- Table structure for absensi_logs
-- ----------------------------
DROP TABLE IF EXISTS `absensi_logs`;
CREATE TABLE `absensi_logs` (
  `id_log` bigint(20) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `id_shift` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL COMMENT 'Tanggal Shift dimulai',
  `jam_masuk` datetime DEFAULT NULL,
  `jam_pulang` datetime DEFAULT NULL,
  `status_kehadiran` enum('Hadir','Terlambat','Pulang Awal','Alpha','Izin','Sakit','Cuti') DEFAULT 'Alpha',
  `metode_masuk` enum('GPS','QR','Token','Manual') DEFAULT NULL,
  `metode_pulang` enum('GPS','QR','Token','Manual') DEFAULT NULL,
  `lat_masuk` decimal(10,8) DEFAULT NULL,
  `long_masuk` decimal(11,8) DEFAULT NULL,
  `lat_pulang` decimal(10,8) DEFAULT NULL,
  `long_pulang` decimal(11,8) DEFAULT NULL,
  `foto_masuk` varchar(255) DEFAULT NULL,
  `foto_pulang` varchar(255) DEFAULT NULL,
  `keterangan` text,
  `terlambat_menit` int(11) DEFAULT 0,
  `pulang_awal_menit` int(11) DEFAULT 0,
  `is_overnight` tinyint(1) DEFAULT 0,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_log`),
  KEY `id_user` (`id_user`),
  KEY `tanggal` (`tanggal`),
  CONSTRAINT `fk_al_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for master_jenis_izin
-- ----------------------------
DROP TABLE IF EXISTS `master_jenis_izin`;
CREATE TABLE `master_jenis_izin` (
  `id_jenis` int(11) NOT NULL AUTO_INCREMENT,
  `nama_izin` varchar(50) NOT NULL,
  `kode_izin` varchar(20) NOT NULL,
  `kurangi_cuti` tinyint(1) DEFAULT 0,
  `butuh_file` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_jenis`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `master_jenis_izin` (`nama_izin`, `kode_izin`, `kurangi_cuti`, `butuh_file`) VALUES
('Sakit', 'SAKIT', 0, 1),
('Izin', 'IZIN', 0, 0),
('Cuti Tahunan', 'CUTI', 1, 0),
('Dinas Luar', 'DINAS', 0, 1);

-- ----------------------------
-- Table structure for absensi_pengajuan
-- ----------------------------
DROP TABLE IF EXISTS `absensi_pengajuan`;
CREATE TABLE `absensi_pengajuan` (
  `id_pengajuan` int(11) NOT NULL AUTO_INCREMENT,
  `id_user` int(11) UNSIGNED NOT NULL,
  `tipe_pengajuan` enum('Izin','Lembur') NOT NULL,
  `id_jenis_izin` int(11) DEFAULT NULL,
  `tgl_mulai` date NOT NULL,
  `tgl_selesai` date NOT NULL,
  `jam_mulai` time DEFAULT NULL COMMENT 'Untuk lembur/izin jam',
  `jam_selesai` time DEFAULT NULL,
  `keterangan` text NOT NULL,
  `file_bukti` varchar(255) DEFAULT NULL,
  `status` enum('Pending','Disetujui','Ditolak') DEFAULT 'Pending',
  `approved_by` int(11) UNSIGNED DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `alasan_tolak` text,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id_pengajuan`),
  KEY `id_user` (`id_user`),
  CONSTRAINT `fk_ap_user` FOREIGN KEY (`id_user`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ----------------------------
-- Table structure for absensi_setting
-- ----------------------------
DROP TABLE IF EXISTS `absensi_setting`;
CREATE TABLE `absensi_setting` (
  `id_setting` int(11) NOT NULL AUTO_INCREMENT,
  `id_group` mediumint(8) UNSIGNED NOT NULL COMMENT 'Group ID from groups table',
  `metode_absensi` varchar(50) DEFAULT 'GPS,QR' COMMENT 'Comma separated: GPS,QR,Token',
  `radius_meter` int(11) DEFAULT 100,
  `allow_wfh` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id_setting`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
