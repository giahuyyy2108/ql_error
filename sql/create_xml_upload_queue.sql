CREATE TABLE IF NOT EXISTS `xml_upload_queue` (
    `file_name` VARCHAR(255) NOT NULL,
    `id_khoa` INT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`file_name`),
    KEY `idx_xml_upload_queue_khoa` (`id_khoa`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
