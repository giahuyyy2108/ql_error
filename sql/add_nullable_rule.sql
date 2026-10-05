INSERT INTO `rule`
    (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`)
VALUES
    (
        'NULLABLE',
        'Cho phép rỗng/null',
        'Cho phép trường không tồn tại, có giá trị null hoặc chuỗi rỗng. Nếu trường có giá trị, các rule khác vẫn được kiểm tra.',
        '',
        0,
        1
    )
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`);
