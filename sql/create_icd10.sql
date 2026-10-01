CREATE TABLE IF NOT EXISTS `icd10` (
    `code` VARCHAR(20) NOT NULL,
    `display_vi` VARCHAR(500) NOT NULL,
    `display_en` VARCHAR(500) DEFAULT NULL,
    `definition_en` TEXT DEFAULT NULL,
    `level` VARCHAR(30) DEFAULT NULL,
    `parent_code` VARCHAR(20) DEFAULT NULL,
    `chapter_id` VARCHAR(30) DEFAULT NULL,
    `chapter_code` VARCHAR(10) DEFAULT NULL,
    `section_id` VARCHAR(30) DEFAULT NULL,
    `type_code` VARCHAR(20) DEFAULT NULL,
    `is_leaf` TINYINT(1) NOT NULL DEFAULT 0,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `coding_guidance` TEXT DEFAULT NULL,
    `coding_guidance_en` TEXT DEFAULT NULL,
    `properties_json` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (`properties_json` IS NULL OR JSON_VALID(`properties_json`)),
    `version` VARCHAR(30) NOT NULL,
    `source` VARCHAR(255) NOT NULL,
    PRIMARY KEY (`code`),
    KEY `idx_icd10_display_vi` (`display_vi`(100)),
    KEY `idx_icd10_chapter` (`chapter_code`),
    KEY `idx_icd10_section` (`section_id`),
    KEY `idx_icd10_active` (`is_active`),
    CONSTRAINT `fk_icd10_parent` FOREIGN KEY (`parent_code`) REFERENCES `icd10` (`code`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `icd10_import_meta` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `version` VARCHAR(30) NOT NULL,
    `source_url` VARCHAR(500) NOT NULL,
    `artifact_sha256` CHAR(64) NOT NULL,
    `total_codes` INT(11) NOT NULL,
    `active_codes` INT(11) NOT NULL,
    `imported_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_icd10_import_version` (`version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
