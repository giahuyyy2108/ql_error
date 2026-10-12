UPDATE `xml_validation_rules`
SET
    `rule_value` = '{"other_file_type":"XML1","other_field":"NGAY_RA","operator":"=","data_type":"date","format":"YmdHi"}',
    `display_name` = 'Ngày ra viện XML8 so với XML1',
    `error_message` = 'Ngày ra viện trong XML8 không khớp với XML1'
WHERE `id` = 92
  AND `file_type` = 'XML8'
  AND `field_name` = 'NGAY_RA'
  AND `rule_type` = 'FIELD_COMPARE';
