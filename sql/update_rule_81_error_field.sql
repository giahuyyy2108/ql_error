UPDATE `xml_validation_rules`
SET `rule_value` = JSON_SET(`rule_value`, '$.error_field', 'T_BNTT')
WHERE `id` = 81
  AND `rule_type` = 'FORMULA';
