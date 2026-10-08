UPDATE `xml_validation_rules`
SET `rule_value` = '{
  "table": "icd10",
  "column": "code",
  "allow_empty": true,
  "remove_suffix": ["*", "†"],
  "conditions": {
    "parent_code": {
      "operator": "NOT_IN",
      "values": ["Z00", "Z01", "Z02", "Z10"]
    }
  }
}'
WHERE `id` = 58
  AND `file_type` = 'XML1'
  AND `field_name` = 'MA_BENH_KT';
