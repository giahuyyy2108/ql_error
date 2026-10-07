CREATE TABLE IF NOT EXISTS `nhom_BHYT_dien` (
    `ma_bhyt` VARCHAR(10) NOT NULL,
    `dien` TINYINT UNSIGNED NOT NULL,
    PRIMARY KEY (`ma_bhyt`, `dien`),
    KEY `idx_nhom_bhyt_dien_dien` (`dien`),
    CONSTRAINT `fk_nhom_bhyt_dien_bhyt`
        FOREIGN KEY (`ma_bhyt`) REFERENCES `nhom_BHYT` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `chk_nhom_bhyt_dien_value` CHECK (`dien` BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT IGNORE INTO `nhom_BHYT_dien` (`ma_bhyt`, `dien`)
SELECT `id`, `dien` FROM `nhom_BHYT`;
