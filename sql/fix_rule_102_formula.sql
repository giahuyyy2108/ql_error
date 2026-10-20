UPDATE `xml_validation_rules`
SET `rule_value` = '{
  "condition": {
    "operation": "LTE",
    "values": [
      {
        "operation": "SUBTRACT",
        "values": [
          {
            "operation": "SUBTRACT",
            "values": [
              {"field": "THANH_TIEN_BH"},
              {"field": "T_BHTT"}
            ]
          },
          {"field": "T_BNCCT"}
        ]
      },
      {"field": "T_TRANTT"}
    ]
  },
  "tolerance": 0.01
}'
WHERE `id` = 102
  AND `file_type` = 'XML3'
  AND `field_name` = 'THANH_TIEN_BH'
  AND `rule_type` = 'FORMULA';
