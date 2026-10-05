UPDATE `xml_validation_rules`
SET `rule_type` = 'TABLE_EXISTS',
    `rule_value` = '{"table":"ma_tinhthanh","column":"ma_cu","value_substring":{"start":0,"length":3}}',
    `error_message` = 'Ba ký tự đầu của số CCCD không phải mã tỉnh hợp lệ',
    `is_active` = 1
WHERE `file_type` = 'XML1'
  AND `field_name` = 'SO_CCCD'
  AND `rule_type` = 'API';
