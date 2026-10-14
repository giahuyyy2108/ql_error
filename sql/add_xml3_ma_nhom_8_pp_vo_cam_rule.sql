-- Dịch vụ thuộc nhóm 8 bắt buộc phải có phương pháp vô cảm trên cùng dòng XML3.
INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML3',
    'MA_NHOM',
    'Phương pháp vô cảm của dịch vụ nhóm 8',
    'CONDITIONAL_FIELD',
    '{"when_value":"8","when_operator":"=","other_field":"PP_VO_CAM","operator":"!=","expected":"","data_type":"string","other_data_type":"string"}',
    'Thiếu phương pháp vô cảm ở dịch vụ {TEN_DICH_VU}',
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM `xml_validation_rules`
    WHERE `file_type` = 'XML3'
      AND `field_name` = 'MA_NHOM'
      AND `rule_type` = 'CONDITIONAL_FIELD'
      AND `rule_value` LIKE '%"when_value":"8"%'
      AND `rule_value` LIKE '%"other_field":"PP_VO_CAM"%'
);
