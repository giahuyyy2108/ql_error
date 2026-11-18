CREATE TABLE IF NOT EXISTS `danh_muc_dvkt_ma_may` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `stt` INT UNSIGNED NOT NULL,
    `ma_dvkt` VARCHAR(50) NOT NULL,
    `ten_dvkt` TEXT NOT NULL,
    `hieu_luc` TINYINT(1) NOT NULL DEFAULT 1,
    `ngay_hieu_luc` DATE NULL,
    `ngay_het_hieu_luc` DATE NULL,
    `tra_ket_qua_sau` TINYINT(1) NOT NULL DEFAULT 0,
    `source_file` VARCHAR(255) NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_dvkt_ma_may_stt` (`stt`),
    KEY `idx_dvkt_ma_may_ma` (`ma_dvkt`),
    KEY `idx_dvkt_ma_may_hieu_luc` (`hieu_luc`, `ngay_hieu_luc`, `ngay_het_hieu_luc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
