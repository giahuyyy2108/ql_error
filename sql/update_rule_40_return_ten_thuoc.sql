UPDATE `xml_validation_rules`
SET `rule_value` = '{"table":"danhmuc_thuoc","column":"ma_hoat_chat","return_column":"ten_thuoc","conditions":{"is_active":1}}',
    `is_active` = 1
WHERE `id` = 40
  AND `file_type` = 'XML2'
  AND `field_name` = 'MA_THUOC';
