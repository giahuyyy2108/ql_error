INSERT INTO `rule` (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`) VALUES
(
    'CCCD_GENDER_CENTURY',
    'Kiểm tra giới tính và thế kỷ CCCD',
    'Đối chiếu ký tự thứ 4 của CCCD với năm sinh và giới tính.',
    '{"birth_field":"NGAY_SINH","gender_field":"GIOI_TINH","male_values":["1"],"female_values":["2"]}',
    1,
    1
)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML1',
    'SO_CCCD',
    'Số căn cước công dân',
    'CCCD_GENDER_CENTURY',
    '{"birth_field":"NGAY_SINH","gender_field":"GIOI_TINH","male_values":["1"],"female_values":["2"]}',
    'Mã giới tính/thế kỷ trên CCCD không phù hợp với ngày sinh và giới tính.',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML1'
      AND `field_name` = 'SO_CCCD'
      AND `rule_type` = 'CCCD_GENDER_CENTURY'
);
