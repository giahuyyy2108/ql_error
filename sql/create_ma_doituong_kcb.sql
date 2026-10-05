CREATE TABLE IF NOT EXISTS `doituong_kcb` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ma` VARCHAR(10) NOT NULL,
    `truong_hop` TEXT NOT NULL,
    `quy_dinh` TEXT NULL,
    `muc_huong` TEXT NULL,
    `ghi_chu` VARCHAR(255) NULL,
    `nguon` VARCHAR(1000) NOT NULL,
    `ngay_hieu_luc` DATE NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_ma_doituong_kcb_ma` (`ma`),
    KEY `idx_ma_doituong_kcb_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
