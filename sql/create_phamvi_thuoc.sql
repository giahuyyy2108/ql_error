CREATE TABLE IF NOT EXISTS `phamvi_thuoc` (
    `ma` TINYINT UNSIGNED NOT NULL,
    `ten` VARCHAR(255) NOT NULL,
    `mo_ta` TEXT NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`ma`),
    KEY `idx_phamvi_thuoc_active` (`is_active`),
    CONSTRAINT `chk_phamvi_thuoc_ma` CHECK (`ma` IN (1, 2))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `phamvi_thuoc` (`ma`, `ten`, `mo_ta`, `is_active`) VALUES
    (1, 'Thuốc trong phạm vi hưởng BHYT', 'Thuốc trong danh mục thuốc do quỹ BHYT chi trả.', 1),
    (2, 'Thuốc ngoài phạm vi hưởng BHYT', 'Thuốc ngoài danh mục thuốc do quỹ BHYT chi trả.', 1)
ON DUPLICATE KEY UPDATE
    `ten` = VALUES(`ten`),
    `mo_ta` = VALUES(`mo_ta`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `table_lookup_sources`
    (`source_key`, `display_name`, `table_name`, `allowed_columns`, `condition_columns`, `is_active`)
VALUES
    ('phamvi_thuoc', 'Phạm vi thuốc BHYT', 'phamvi_thuoc', '["ma"]', '["is_active"]', 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `table_name` = VALUES(`table_name`),
    `allowed_columns` = VALUES(`allowed_columns`),
    `condition_columns` = VALUES(`condition_columns`),
    `is_active` = VALUES(`is_active`);
