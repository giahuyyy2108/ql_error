INSERT INTO `rule`
    (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`)
VALUES
(
    'CROSS_FILE_REQUIRED_BY_KEY',
    'Bắt buộc theo khóa file khác',
    'Bắt buộc trường hiện tại có giá trị khi bản ghi cùng khóa ở file XML khác thỏa điều kiện.',
    '{"source_key":"MA_DICH_VU","when_match":{"file_type":"XML3","target_key":"MA_DICH_VU","field":"MA_NHOM","operator":"=","value":"2"},"error_when_source_missing":false}',
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
    'XML4',
    'KET_LUAN',
    'Kết luận dịch vụ kỹ thuật nhóm 2',
    'CROSS_FILE_REQUIRED_BY_KEY',
    '{"source_key":"MA_DICH_VU","when_match":{"file_type":"XML3","target_key":"MA_DICH_VU","field":"MA_NHOM","operator":"=","value":"2","data_type":"string"},"error_when_source_missing":false}',
    'KẾT LUẬN không được để trống đối với dịch vụ có MÃ NHÓM = 2.',
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM `xml_validation_rules`
    WHERE `file_type` = 'XML4'
      AND `field_name` = 'KET_LUAN'
      AND `rule_type` = 'CROSS_FILE_REQUIRED_BY_KEY'
      AND `rule_value` LIKE '%"field":"MA_NHOM"%'
      AND `rule_value` LIKE '%"value":"2"%'
);

UPDATE `xml_validation_rules`
SET
    `display_name` = 'Kết luận dịch vụ kỹ thuật nhóm 2',
    `rule_value` = '{"source_key":"MA_DICH_VU","when_match":{"file_type":"XML3","target_key":"MA_DICH_VU","field":"MA_NHOM","operator":"=","value":"2","data_type":"string"},"error_when_source_missing":false}',
    `error_message` = 'KẾT LUẬN không được để trống đối với dịch vụ có MÃ NHÓM = 2.',
    `is_active` = 1
WHERE `file_type` = 'XML4'
  AND `field_name` = 'KET_LUAN'
  AND `rule_type` = 'CROSS_FILE_REQUIRED_BY_KEY';
