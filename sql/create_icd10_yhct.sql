CREATE TABLE IF NOT EXISTS `icd10_yhct` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    `ma_dung_chung` VARCHAR(20) NOT NULL,
    `ma_dung_chung_cha` VARCHAR(20) NULL,
    `ten_benh_huong_dan` TEXT NULL,
    `ten_benh_y_hoc_hien_dai` TEXT NULL,
    `ma_icd10` VARCHAR(30) NULL,
    `benh_danh_yhct` VARCHAR(500) NULL,
    `ma_u` VARCHAR(100) NULL,
    `the_lam_sang` TEXT NULL,
    `ma_hoa` VARCHAR(100) NULL,
    `so_quyet_dinh` VARCHAR(50) NOT NULL DEFAULT '2552/QĐ-BYT',
    `ngay_ban_hanh` DATE NOT NULL DEFAULT '2025-08-12',
    `dot` TINYINT UNSIGNED NOT NULL DEFAULT 1,
    `source_url` VARCHAR(1000) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_icd10_yhct_ma_dung_chung` (`ma_dung_chung`),
    KEY `idx_icd10_yhct_ma_cha` (`ma_dung_chung_cha`),
    KEY `idx_icd10_yhct_ma_icd10` (`ma_icd10`),
    KEY `idx_icd10_yhct_ma_u` (`ma_u`),
    KEY `idx_icd10_yhct_ma_hoa` (`ma_hoa`),
    KEY `idx_icd10_yhct_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `table_lookup_sources`
    (`source_key`, `display_name`, `table_name`, `allowed_columns`, `condition_columns`, `is_active`)
VALUES
    ('icd10_yhct', 'ICD-10 Y học cổ truyền', 'icd10_yhct',
     '["ma_dung_chung","ma_icd10","ma_u","ma_hoa","benh_danh_yhct","the_lam_sang"]',
     '["is_active","dot","ma_dung_chung_cha"]', 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `table_name` = VALUES(`table_name`),
    `allowed_columns` = VALUES(`allowed_columns`),
    `condition_columns` = VALUES(`condition_columns`),
    `is_active` = VALUES(`is_active`);
