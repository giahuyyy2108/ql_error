CREATE TABLE IF NOT EXISTS `phuongthuc_thanhtoan` (
    `ma` TINYINT UNSIGNED NOT NULL,
    `ten` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ma`),
    KEY `idx_phuongthuc_thanhtoan_active` (`is_active`),
    CONSTRAINT `chk_phuongthuc_thanhtoan_ma` CHECK (`ma` BETWEEN 0 AND 3)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `phuongthuc_thanhtoan` (`ma`, `ten`, `is_active`) VALUES
    (0, 'Phí dịch vụ', 1),
    (1, 'Định suất', 1),
    (2, 'Ngoài định suất', 1),
    (3, 'DRG', 1)
ON DUPLICATE KEY UPDATE
    `ten` = VALUES(`ten`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `table_lookup_sources`
    (`source_key`, `display_name`, `table_name`, `allowed_columns`, `condition_columns`, `is_active`)
VALUES
    ('phuongthuc_thanhtoan', 'Phương thức thanh toán', 'phuongthuc_thanhtoan', '["ma"]', '["is_active"]', 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `table_name` = VALUES(`table_name`),
    `allowed_columns` = VALUES(`allowed_columns`),
    `condition_columns` = VALUES(`condition_columns`),
    `is_active` = VALUES(`is_active`);
