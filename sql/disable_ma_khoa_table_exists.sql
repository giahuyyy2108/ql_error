UPDATE `xml_validation_rules`
SET `is_active` = 0
WHERE `field_name` = 'MA_KHOA'
  AND `rule_type` = 'TABLE_EXISTS';
