-- Kiểm tra trên từng dòng chi tiết:
-- T_BNTT + T_BHTT + T_BNCCT = THANH_TIEN_BV.
SET @payment_formula = '{
  "result_field": "THANH_TIEN_BV",
  "error_field": "THANH_TIEN_BV",
  "expression": {
    "operation": "ADD",
    "values": [
      { "field": "T_BNTT" },
      { "field": "T_BHTT" },
      { "field": "T_BNCCT" }
    ]
  },
  "tolerance": 0.01,
  "round": 2
}';

UPDATE `xml_validation_rules`
SET `field_name` = 'THANH_TIEN_BV',
    `display_name` = 'Tổng các nguồn thanh toán',
    `rule_value` = @payment_formula,
    `error_message` = 'T_BNTT + T_BHTT + T_BNCCT phải bằng THANH_TIEN_BV',
    `is_active` = 1
WHERE `id` = 81 AND `file_type` = 'XML2' AND `rule_type` = 'FORMULA';

SET @insurance_formula = '{
  "result_field": "THANH_TIEN_BH",
  "error_field": "THANH_TIEN_BH",
  "expression": {
    "operation": "ADD",
    "values": [
      { "field": "T_BHTT" },
      { "field": "T_BNCCT" }
    ]
  },
  "tolerance": 0.01,
  "round": 2
}';

UPDATE `xml_validation_rules`
SET `field_name` = 'THANH_TIEN_BH',
    `display_name` = 'Tổng thanh toán bảo hiểm',
    `rule_value` = @insurance_formula,
    `error_message` = 'T_BHTT + T_BNCCT phải bằng THANH_TIEN_BH',
    `is_active` = 1
WHERE `id` = 82 AND `file_type` = 'XML2' AND `rule_type` = 'FORMULA';

INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML3', 'THANH_TIEN_BV', 'Tổng các nguồn thanh toán', 'FORMULA',
    @payment_formula,
    'Đề nghị kiểm tra đơn giá của vật tư y tế {TEN_VAT_TU} ({MA_VAT_TU})', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML3'
      AND `field_name` = 'THANH_TIEN_BV'
      AND `rule_type` = 'FORMULA'
);

UPDATE `xml_validation_rules`
SET `display_name` = 'Tổng các nguồn thanh toán',
    `rule_value` = @payment_formula,
    `error_message` = 'Đề nghị kiểm tra đơn giá của vật tư y tế {TEN_VAT_TU} ({MA_VAT_TU})',
    `is_active` = 1
WHERE `file_type` = 'XML3'
  AND `field_name` = 'THANH_TIEN_BV'
  AND `rule_type` = 'FORMULA';

INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML3', 'THANH_TIEN_BH', 'Tổng thanh toán bảo hiểm', 'FORMULA',
    @insurance_formula,
    'T_BHTT + T_BNCCT phải bằng THANH_TIEN_BH', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML3'
      AND `field_name` = 'THANH_TIEN_BH'
      AND `rule_type` = 'FORMULA'
);

UPDATE `xml_validation_rules`
SET `display_name` = 'Tổng thanh toán bảo hiểm',
    `rule_value` = @insurance_formula,
    `error_message` = 'T_BHTT + T_BNCCT phải bằng THANH_TIEN_BH',
    `is_active` = 1
WHERE `file_type` = 'XML3'
  AND `field_name` = 'THANH_TIEN_BH'
  AND `rule_type` = 'FORMULA';
