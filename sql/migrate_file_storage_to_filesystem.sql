ALTER TABLE `file`
    ADD COLUMN `file_path` VARCHAR(1000) NULL AFTER `kichthuoc`,
    ADD COLUMN `processing_status` VARCHAR(20) NOT NULL DEFAULT 'processed' AFTER `file_path`,
    ADD COLUMN `validation_result` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL
        CHECK (`validation_result` IS NULL OR JSON_VALID(`validation_result`)) AFTER `processing_status`;

UPDATE `file`
SET `file_path` = CONCAT('storage/xml/processed/', `ten`),
    `processing_status` = 'processed'
WHERE `file_path` IS NULL OR `file_path` = '';

ALTER TABLE `file`
    MODIFY COLUMN `file_path` VARCHAR(1000) NOT NULL,
    DROP COLUMN `noidung`,
    DROP COLUMN `decode`;
