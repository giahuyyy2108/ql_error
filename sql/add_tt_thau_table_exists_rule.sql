INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML2',
    'TT_THAU',
    'Thông tin thầu thuốc',
    'TABLE_EXISTS',
    '{"table":"danhmuc_thuoc","column":"so_quyet_dinh","return_column":"so_quyet_dinh","conditions":{"is_active":1},"allow_empty":true}',
    'Thông tin thầu thuốc không tồn tại trong danh mục thuốc',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML2'
      AND `field_name` = 'TT_THAU'
      AND `rule_type` = 'TABLE_EXISTS'
);

UPDATE `xml_validation_rules`
SET `rule_value` = '{"table":"danhmuc_thuoc","column":"so_quyet_dinh","return_column":"so_quyet_dinh","conditions":{"is_active":1},"allow_empty":true}',
    `error_message` = 'Thông tin thầu thuốc không tồn tại trong danh mục thuốc',
    `is_active` = 1
WHERE `file_type` = 'XML2'
  AND `field_name` = 'TT_THAU'
  AND `rule_type` = 'TABLE_EXISTS';
