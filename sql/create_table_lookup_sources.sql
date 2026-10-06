CREATE TABLE IF NOT EXISTS `table_lookup_sources` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
    `source_key` VARCHAR(64) NOT NULL,
    `display_name` VARCHAR(150) NOT NULL,
    `table_name` VARCHAR(64) NOT NULL,
    `allowed_columns` TEXT NOT NULL,
    `condition_columns` TEXT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_table_lookup_source_key` (`source_key`),
    KEY `idx_table_lookup_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `table_lookup_sources`
    (`source_key`, `display_name`, `table_name`, `allowed_columns`, `condition_columns`, `is_active`)
VALUES
    ('icd10', 'ICD-10', 'icd10', '["code"]', '["is_active","is_leaf","version","chapter_code","type_code"]', 1),
    ('nhom_bhyt', 'Nhóm BHYT', 'nhom_BHYT', '["id"]', '["dien"]', 1),
    ('dan_toc', 'Dân tộc', 'dan_toc', '["ma"]', '["is_active"]', 1),
    ('khoa', 'Khoa', 'khoa', '["ma"]', '["is_active"]', 1),
    ('ma_tinhthanh', 'Mã tỉnh thành', 'tinhthanh', '["ma_cu","ma_sau_sapnhap"]', '[]', 1),
    ('tinhthanh', 'Tỉnh thành', 'tinhthanh', '["ma_cu","ma_sau_sapnhap"]', '[]', 1),
    ('ma_doituong_kcb', 'Mã đối tượng KCB', 'doituong_kcb', '["ma"]', '["is_active"]', 1),
    ('doituong_kcb', 'Đối tượng KCB', 'doituong_kcb', '["ma"]', '["is_active"]', 1),
    ('tan_duoc', 'Tân dược', 'tan_duoc', '["ma_hoat_chat","sdk_gpnk","sdk_chuan_hoa"]', '["is_active","nhom_tieu_chi","goi_thau","tinh_thanh"]', 1);
