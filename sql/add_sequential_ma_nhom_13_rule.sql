INSERT INTO rule (code, display_name, description, value_hint, requires_value, is_active)
SELECT 'SEQUENTIAL_FORMULA', 'Công thức theo thứ tự', 'So sánh giá trị theo nhóm và thứ tự thời gian.',
       '{"group_field":"MA_NHOM","order_field":"NGAY_TH_YL","amount_field":"THANH_TIEN_BH"}', 1, 1
WHERE NOT EXISTS (SELECT 1 FROM rule WHERE code = 'SEQUENTIAL_FORMULA');

INSERT INTO xml_validation_rules
    (file_type, field_name, display_name, rule_type, rule_value, error_message, is_active)
SELECT
    'XML3', 'THANH_TIEN_BH', 'Thanh tiền BH theo y lệnh nhóm 13', 'SEQUENTIAL_FORMULA',
    '{"group_field":"MA_NHOM","group_value":"13","order_field":"NGAY_TH_YL","amount_field":"THANH_TIEN_BH","first_value":52600,"next_percent":30,"tolerance":0.01}',
    'MA_NHOM 13: y lệnh đầu phải là 52600, các y lệnh sau phải bằng 30% (15780)', 1
WHERE NOT EXISTS (
    SELECT 1 FROM xml_validation_rules
    WHERE file_type = 'XML3' AND field_name = 'THANH_TIEN_BH'
      AND rule_type = 'SEQUENTIAL_FORMULA'
);
