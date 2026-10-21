-- Bảng khoa hiện dùng các cột id, ten, id_khoi.
UPDATE `xml_validation_rules`
SET `rule_value` = REPLACE(
    `rule_value`,
    '"column": "ma"',
    '"column": "id"'
)
WHERE `rule_type` = 'TABLE_EXISTS'
  AND `rule_value` LIKE '%"table": "khoa"%';

UPDATE `table_lookup_sources`
SET `allowed_columns` = '["id"]',
    `condition_columns` = '[]'
WHERE `source_key` = 'khoa';

UPDATE `xml_validation_rules`
SET `rule_value` = REPLACE(
    `rule_value`,
    ',\n  "conditions": {\n    "is_active": 1\n  }',
    ''
)
WHERE `rule_type` = 'TABLE_EXISTS'
  AND `rule_value` LIKE '%"table": "khoa"%';
