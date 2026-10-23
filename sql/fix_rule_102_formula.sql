UPDATE `xml_validation_rules`
SET `rule_value` = '{
  "result_field": "THANH_TIEN_BH",
  "expression": {
    "operation": "ADD",
    "values": [
      {"field": "T_BHTT"},
      {"field": "T_BNCCT"}
    ]
  },
  "tolerance": 0.01,
  "round": 2
}',
`error_message` = 'T_BHTT + T_BNCCT phải bằng THANH_TIEN_BH'
WHERE `id` = 102
  AND `file_type` = 'XML3'
  AND `field_name` = 'THANH_TIEN_BH'
  AND `rule_type` = 'FORMULA';
