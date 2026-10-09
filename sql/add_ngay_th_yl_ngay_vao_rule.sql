INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML2',
    'NGAY_TH_YL',
    'Ngày thực hiện y lệnh so với ngày vào viện',
    'FIELD_COMPARE',
    '{"other_file_type":"XML1","other_field":"NGAY_VAO","operator":">=","data_type":"date","format":"YmdHi"}',
    'Ngày thực hiện y lệnh không được trước ngày vào viện',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML2'
      AND `field_name` = 'NGAY_TH_YL'
      AND `rule_type` = 'FIELD_COMPARE'
      AND `rule_value` LIKE '%"other_field":"NGAY_VAO"%'
);
