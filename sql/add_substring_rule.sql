INSERT INTO `rule` (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`) VALUES
(
    'SUBSTRING',
    'Cắt chuỗi và kiểm tra',
    'Cắt một phần giá trị theo vị trí bắt đầu và độ dài, sau đó so sánh với giá trị cấu hình.',
    '{"start":0,"length":2,"operator":"=","expected":"79"}',
    1,
    1
)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`),
    `is_active` = VALUES(`is_active`);

