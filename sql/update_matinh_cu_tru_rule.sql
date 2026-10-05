UPDATE `xml_validation_rules`
SET `rule_type` = 'TABLE_EXISTS',
    `rule_value` = '{"table":"ma_tinhthanh","column":"ma_cu"}',
    `error_message` = 'Mã tỉnh cư trú không tồn tại trong danh mục tỉnh thành',
    `is_active` = 1
WHERE `file_type` = 'XML1'
  AND `field_name` = 'MATINH_CU_TRU';
