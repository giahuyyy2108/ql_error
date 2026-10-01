CREATE TABLE IF NOT EXISTS `api_validation_configs` (
    `id` INT(11) NOT NULL AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `endpoint` VARCHAR(500) NOT NULL,
    `method` VARCHAR(10) NOT NULL DEFAULT 'POST',
    `headers` LONGTEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (`headers` IS NULL OR JSON_VALID(`headers`)),
    `timeout_seconds` INT(11) NOT NULL DEFAULT 10,
    `response_field` VARCHAR(255) DEFAULT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `rule` (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`) VALUES
(
    'FIELD_COMPARE',
    'So sánh hai trường',
    'So sánh trường hiện tại với một trường trong cùng file hoặc file XML khác.',
    '{"other_file_type":"XML1","other_field":"NGAY_RA","operator":"<=","data_type":"date","format":"YmdHi"}',
    1,
    1
),
(
    'API',
    'Kiểm tra qua API',
    'Gửi các trường cấu hình đến API và kiểm tra giá trị phản hồi.',
    '{"api_config_id":1,"request_mapping":{"ma":"MA_LK"},"operator":"=","expected":true,"on_error":"FAIL"}',
    1,
    1
)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`),
    `is_active` = VALUES(`is_active`);

