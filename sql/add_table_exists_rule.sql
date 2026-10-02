INSERT INTO `rule` (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`) VALUES
(
    'TABLE_EXISTS',
    'Tồn tại trong bảng',
    'Kiểm tra giá trị trường XML có tồn tại trong bảng danh mục được cho phép.',
    '{"table":"icd10","column":"code","conditions":{"is_active":1}}',
    1,
    1
)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`),
    `is_active` = VALUES(`is_active`);
