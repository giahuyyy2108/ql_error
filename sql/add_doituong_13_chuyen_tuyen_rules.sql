INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML1',
    'MA_DOITUONG_KCB',
    'Đối tượng KCB có giấy chuyển tuyến',
    'CONDITIONAL_FIELD',
    '{"when_value":"1.3","when_operator":"=","other_field":"MA_NOI_DI","operator":"!=","expected":"","data_type":"string","other_data_type":"string"}',
    'Đối tượng KCB 1.3 có giấy chuyển tuyến nhưng thiếu MA_NOI_DI',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML1'
      AND `field_name` = 'MA_DOITUONG_KCB'
      AND `rule_type` = 'CONDITIONAL_FIELD'
      AND `rule_value` LIKE '%"when_value":"1.3"%'
      AND `rule_value` LIKE '%"other_field":"MA_NOI_DI"%'
);

INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML1',
    'MA_DOITUONG_KCB',
    'Đối tượng KCB có giấy chuyển tuyến',
    'CONDITIONAL_FIELD',
    '{"when_value":"1.3","when_operator":"=","other_field":"SO_GIAYCHUYENTUYEN","operator":"!=","expected":"","data_type":"string","other_data_type":"string"}',
    'Đối tượng KCB 1.3 có giấy chuyển tuyến nhưng thiếu SO_GIAYCHUYENTUYEN',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML1'
      AND `field_name` = 'MA_DOITUONG_KCB'
      AND `rule_type` = 'CONDITIONAL_FIELD'
      AND `rule_value` LIKE '%"when_value":"1.3"%'
      AND `rule_value` LIKE '%"other_field":"SO_GIAYCHUYENTUYEN"%'
);
