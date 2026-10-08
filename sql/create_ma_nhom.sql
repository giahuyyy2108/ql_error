CREATE TABLE IF NOT EXISTS `ma_nhom` (
    `ma` TINYINT UNSIGNED NOT NULL,
    `ten_nhom` VARCHAR(255) NOT NULL,
    `ghi_chu` TEXT DEFAULT NULL,
    `nguon` VARCHAR(255) NOT NULL DEFAULT 'Quyết định 5937/QĐ-BYT ngày 30/12/2021',
    `ngay_hieu_luc` DATE NOT NULL DEFAULT '2021-12-30',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ma`),
    KEY `idx_ma_nhom_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `ma_nhom` (`ma`, `ten_nhom`, `ghi_chu`) VALUES
    (1, 'Xét nghiệm', NULL),
    (2, 'Chẩn đoán hình ảnh', NULL),
    (3, 'Thăm dò chức năng', NULL),
    (4, 'Thuốc', NULL),
    (7, 'Máu', NULL),
    (8, 'Phẫu thuật', NULL),
    (10, 'Vật tư y tế', NULL),
    (12, 'Vận chuyển', NULL),
    (13, 'Khám bệnh', NULL),
    (14, 'Ngày giường bệnh ban ngày', NULL),
    (15, 'Ngày giường bệnh điều trị nội trú', NULL),
    (16, 'Ngày giường lưu', NULL),
    (17, 'Chế phẩm máu', NULL),
    (18, 'Thủ thuật', NULL)
ON DUPLICATE KEY UPDATE
    `ten_nhom` = VALUES(`ten_nhom`),
    `ghi_chu` = VALUES(`ghi_chu`),
    `is_active` = 1;

INSERT INTO `table_lookup_sources`
    (`source_key`, `display_name`, `table_name`, `allowed_columns`, `condition_columns`, `is_active`)
VALUES
    ('ma_nhom', 'Mã nhóm theo chi phí', 'ma_nhom', '["ma"]', '["is_active"]', 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `table_name` = VALUES(`table_name`),
    `allowed_columns` = VALUES(`allowed_columns`),
    `condition_columns` = VALUES(`condition_columns`),
    `is_active` = VALUES(`is_active`);
