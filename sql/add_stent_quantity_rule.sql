INSERT INTO xml_validation_rules
    (file_type, field_name, display_name, rule_type, rule_value, error_message, is_active)
SELECT
    'XML3',
    'MA_DICH_VU',
    'Số lượng theo mã dịch vụ',
    'CONDITIONAL_FIELD',
    '{"when_value":"N06.02.020","when_operator":"STARTS_WITH","other_field":"SO_LUONG","operator":"<=","expected":1,"data_type":"string","other_data_type":"number"}',
    'Dịch vụ N06.02.020 có SO_LUONG không được lớn hơn 1',
    1
WHERE NOT EXISTS (
    SELECT 1
    FROM xml_validation_rules
    WHERE file_type = 'XML3'
      AND field_name = 'MA_DICH_VU'
      AND rule_type = 'CONDITIONAL_FIELD'
      AND rule_value LIKE '%N06.02.020%'
);
