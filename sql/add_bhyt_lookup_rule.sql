INSERT INTO `rule`
    (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`)
VALUES
    ('BHYT_LOOKUP', 'Tra cứu hành chính BHYT',
     'Đối chiếu MA_THE_BHYT, HO_TEN và NGAY_SINH của XML1 với API BHXH.',
     '{}', 1, 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML1', 'MA_THE_BHYT', 'Thông tin hành chính BHYT', 'BHYT_LOOKUP', '{}',
    'Thông tin hành chính bệnh nhân không khớp dữ liệu BHXH.', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML1' AND `rule_type` = 'BHYT_LOOKUP'
);
