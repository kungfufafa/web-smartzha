-- ============================================
-- SISTEM PEMBAYARAN SEKOLAH - QRIS STATIS
-- Database Migration Script
-- Created: 2024-12-31
-- ============================================

-- ============================================
-- TABEL 1: KONFIGURASI PEMBAYARAN
-- Menyimpan setting QRIS statis & rekening bank
-- ============================================

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

-- If you already have the table, run this once:
-- ALTER TABLE pembayaran_config ADD COLUMN qris_string TEXT NULL COMMENT 'String QRIS (EMV) untuk generate QRIS dinamis' AFTER qris_image;

-- Insert default config
INSERT INTO `pembayaran_config` (`qris_merchant_name`, `bank_name`, `bank_account`, `bank_holder`, `payment_instruction`) 
VALUES ('Sekolah', 'Bank BRI', '1234567890', 'Bendahara Sekolah', 'Silakan scan QRIS atau transfer ke rekening di atas. Setelah melakukan pembayaran, upload bukti transfer.');

-- ============================================
-- TABEL 2: JENIS TAGIHAN
-- Master data jenis pembayaran (SPP, Seragam, dll)
-- ============================================

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

-- Insert default jenis tagihan
INSERT INTO `pembayaran_jenis` (`kode_jenis`, `nama_jenis`, `nominal_default`, `is_recurring`, `keterangan`) VALUES
('SPP', 'SPP Bulanan', 500000, 1, 'Sumbangan Pembinaan Pendidikan per bulan'),
('DAFTAR', 'Biaya Daftar Ulang', 1000000, 0, 'Biaya pendaftaran ulang tahunan'),
('SERAGAM', 'Seragam Sekolah', 0, 0, 'Biaya seragam sekolah'),
('BUKU', 'Buku Pelajaran', 0, 0, 'Biaya buku pelajaran'),
('KEGIATAN', 'Biaya Kegiatan', 0, 0, 'Biaya kegiatan sekolah');

-- ============================================
-- TABEL 3: TAGIHAN (INVOICE)
-- Tagihan per siswa
-- ============================================

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

-- ============================================
-- TABEL 4: TRANSAKSI PEMBAYARAN
-- Bukti pembayaran & status verifikasi
-- ============================================

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
  
  -- Status Verifikasi
  `status` ENUM('pending','verified','rejected','cancelled') DEFAULT 'pending',
  `verified_by` INT(11) DEFAULT NULL COMMENT 'Admin yang verifikasi',
  `verified_at` DATETIME DEFAULT NULL,
  `catatan_admin` TEXT DEFAULT NULL COMMENT 'Alasan reject/notes',
  `reject_count` TINYINT(2) DEFAULT 0 COMMENT 'Berapa kali ditolak',
  
  -- Audit Fields
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

-- ============================================
-- TABEL 5: AUDIT LOG PEMBAYARAN
-- Log semua aktivitas untuk accountability
-- ============================================

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

-- ============================================
-- VIEWS UNTUK REPORTING
-- ============================================

-- View: Tagihan dengan info siswa dan kelas
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

-- View: Transaksi dengan info lengkap
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

-- ============================================
-- INDEXES UNTUK PERFORMA
-- ============================================

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

-- ============================================
-- TRIGGER: Auto-generate kode tagihan & transaksi
-- ============================================

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
