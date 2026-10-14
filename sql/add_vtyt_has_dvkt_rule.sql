INSERT INTO `rule`
    (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`)
VALUES
(
    'VTYT_HAS_DVKT',
    'VTYT phải có DVKT tương ứng',
    'Khi dòng vật tư XML3 có GOI_VTYT, MA_DICH_VU phải trùng với một dòng DVKT trong cùng XML3.',
    'Không cần cấu hình',
    0,
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
    'XML3',
    'GOI_VTYT',
    'Gói vật tư y tế',
    'VTYT_HAS_DVKT',
    '',
    'Mã dịch vụ của VTYT có GOI_VTYT không trùng với DVKT nào trong XML3.',
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM `xml_validation_rules`
    WHERE `file_type` = 'XML3'
      AND `field_name` = 'GOI_VTYT'
      AND `rule_type` = 'VTYT_HAS_DVKT'
);

UPDATE `xml_validation_rules`
SET
    `display_name` = 'Mã dịch vụ VTYT',
    `error_message` = 'Mã dịch vụ của VTYT có GOI_VTYT không trùng với DVKT nào trong XML3.',
    `is_active` = 1
WHERE `file_type` = 'XML3'
  AND `field_name` = 'GOI_VTYT'
  AND `rule_type` = 'VTYT_HAS_DVKT';
