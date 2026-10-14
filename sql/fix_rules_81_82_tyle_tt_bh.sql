UPDATE `xml_validation_rules`
SET `rule_value` = '{
  "result_field": "T_BHTT",
  "error_field": "T_BNTT",
  "expression": {
    "operation": "MULTIPLY",
    "values": [
      {
        "operation": "SUBTRACT",
        "values": [
          { "field": "THANH_TIEN_BH" },
          { "field": "T_NGUONKHAC" }
        ]
      },
      {
        "operation": "DIVIDE",
        "values": [
          { "field": "MUC_HUONG" },
          100
        ]
      },
      {
        "operation": "DIVIDE",
        "values": [
          { "field": "TYLE_TT_BH" },
          100
        ]
      }
    ]
  },
  "tolerance": 0.01,
  "round": 2
}'
WHERE `id` = 81
  AND `file_type` = 'XML2'
  AND `rule_type` = 'FORMULA';

UPDATE `xml_validation_rules`
SET `rule_value` = '{
  "result_field": "T_BNCCT",
  "expression": {
    "operation": "SUBTRACT",
    "values": [
      {
        "operation": "MULTIPLY",
        "values": [
          {
            "operation": "SUBTRACT",
            "values": [
              { "field": "THANH_TIEN_BH" },
              { "field": "T_NGUONKHAC" }
            ]
          },
          {
            "operation": "DIVIDE",
            "values": [
              { "field": "TYLE_TT_BH" },
              100
            ]
          }
        ]
      },
      { "field": "T_BHTT" }
    ]
  },
  "tolerance": 0.01,
  "round": 2
}'
WHERE `id` = 82
  AND `file_type` = 'XML2'
  AND `rule_type` = 'FORMULA';
