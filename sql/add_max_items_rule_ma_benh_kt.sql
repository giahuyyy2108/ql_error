INSERT INTO `rule`
    (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`)
SELECT
    'MAX_ITEMS',
    'Số phần tử tối đa',
    'Số phần tử sau khi tách trường nhiều giá trị không được vượt quá cấu hình.',
    'Ví dụ: 12',
    1,
    1
WHERE NOT EXISTS (SELECT 1 FROM `rule` WHERE `code` = 'MAX_ITEMS');

INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML1',
    'MA_BENH_KT',
    'Mã bệnh kèm theo',
    'MAX_ITEMS',
    '12',
    'Mã bệnh kèm theo không được vượt quá 12 mã',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML1'
      AND `field_name` = 'MA_BENH_KT'
      AND `rule_type` = 'MAX_ITEMS'
);
