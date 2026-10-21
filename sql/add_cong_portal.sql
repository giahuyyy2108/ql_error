CREATE TABLE IF NOT EXISTS `cong_benhnhan` (
  `cccd` varchar(20) NOT NULL,
  `hoten` varchar(255) NOT NULL,
  `namsinh` varchar(20) NULL,
  `ma_the_bhyt` varchar(50) NULL,
  PRIMARY KEY (`cccd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cong_hoso` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `cccd` varchar(20) NOT NULL,
  `malk` varchar(255) NOT NULL,
  `ten_file` varchar(255) NOT NULL,
  `noidung` longtext NOT NULL,
  `nguoi_day` varchar(255) NULL,
  `id_khoa` varchar(50) NULL,
  `ngay_day` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_cong_hoso_malk` (`malk`),
  KEY `idx_cong_hoso_cccd` (`cccd`),
  CONSTRAINT `fk_cong_hoso_benhnhan` FOREIGN KEY (`cccd`) REFERENCES `cong_benhnhan` (`cccd`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
