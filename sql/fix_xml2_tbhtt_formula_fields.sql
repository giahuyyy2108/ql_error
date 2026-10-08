UPDATE `xml_validation_rules`
SET `rule_value` = REPLACE(
    REPLACE(`rule_value`, '"field": "THANH_TIEN"', '"field": "THANH_TIEN_BH"'),
    '"field": "TYLE_TT"', '"field": "TYLE_TT_BH"'
)
WHERE `file_type` = 'XML2'
  AND `field_name` = 'T_BHTT'
  AND `rule_type` = 'FORMULA';
