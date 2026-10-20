UPDATE xml_validation_rules
SET field_name = 'MA_VAT_TU',
    display_name = 'Số lượng theo mã vật tư',
    rule_value = '{"when_value":"N06.02.020","when_operator":"STARTS_WITH","other_field":"SO_LUONG","operator":"<=","expected":1,"data_type":"string","other_data_type":"number"}',
    error_message = 'Mã vật tư bắt đầu bằng N06.02.020 có SO_LUONG không được lớn hơn 1'
WHERE id = 107;
