UPDATE `xml_validation_rules`
SET `rule_value` = '{"table":"ma_tinhthanh","column":"ma_cu","value_substring":{"start":0,"length":3},"value_transform":"integer"}'
WHERE `id` = 12 AND `rule_type` = 'TABLE_EXISTS';

UPDATE `xml_validation_rules`
SET `rule_value` = JSON_SET(`rule_value`, '$.remove_suffix', JSON_ARRAY('*', '†'))
WHERE `id` IN (15, 29, 58, 67)
  AND `rule_type` = 'TABLE_EXISTS'
  AND JSON_VALID(`rule_value`);
