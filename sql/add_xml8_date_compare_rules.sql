INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML8',
    'NGAY_VAO',
    'Ngày vào viện XML8 so với XML1',
    'FIELD_COMPARE',
    '{"other_file_type":"XML1","other_field":"NGAY_VAO","operator":"=","data_type":"date","format":"YmdHi"}',
    'Ngày vào viện trong XML8 không khớp với XML1',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML8'
      AND `field_name` = 'NGAY_VAO'
      AND `rule_type` = 'FIELD_COMPARE'
      AND `rule_value` LIKE '%"other_file_type":"XML1"%'
      AND `rule_value` LIKE '%"other_field":"NGAY_VAO"%'
);

INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML8',
    'NGAY_RA',
    'Ngày ra viện XML8 so với XML1',
    'FIELD_COMPARE',
    '{"other_file_type":"XML1","other_field":"NGAY_RA","operator":"=","data_type":"date","format":"YmdHi"}',
    'Ngày ra viện trong XML8 không khớp với XML1',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML8'
      AND `field_name` = 'NGAY_RA'
      AND `rule_type` = 'FIELD_COMPARE'
      AND `rule_value` LIKE '%"other_file_type":"XML1"%'
      AND `rule_value` LIKE '%"other_field":"NGAY_RA"%'
);
