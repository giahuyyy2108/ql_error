CREATE TABLE IF NOT EXISTS `file_history` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `file_id` bigint unsigned NULL,
  `ma_lk` varchar(255) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `replaced_at` datetime NOT NULL,
  `username` varchar(255) NULL,
  `khoa_name` varchar(255) NULL,
  `file_path` varchar(500) NOT NULL,
  `file_hash` char(64) NULL,
  `old_validation_result` longtext NULL,
  PRIMARY KEY (`id`), KEY `idx_file_history_ma_lk` (`ma_lk`), KEY `idx_file_history_replaced_at` (`replaced_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `xml_upload_queue` ADD COLUMN `username` varchar(255) NULL AFTER `id_khoa`;

CREATE TABLE IF NOT EXISTS `file_audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `file_id` bigint unsigned NULL,
  `ma_lk` varchar(255) NULL,
  `file_name` varchar(255) NULL,
  `action` varchar(50) NOT NULL,
  `username` varchar(255) NULL,
  `khoa_name` varchar(255) NULL,
  `file_path_old` varchar(500) NULL,
  `file_path_new` varchar(500) NULL,
  `error_detail` longtext NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`), KEY `idx_audit_ma_lk` (`ma_lk`), KEY `idx_audit_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
