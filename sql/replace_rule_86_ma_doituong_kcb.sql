INSERT INTO `rule`
    (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`)
VALUES
    ('CONDITIONAL_FIELD',
     'Điều kiện theo trường khác',
     'Chỉ kiểm tra một trường khác khi giá trị trường hiện tại thỏa điều kiện.',
     '{"when_value":"1.1","other_field":"MA_DKBD","operator":"=","expected":"79025"}',
     1, 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`),
    `is_active` = VALUES(`is_active`);

UPDATE `xml_validation_rules`
SET `field_name` = 'MA_DOITUONG_KCB',
    `display_name` = 'Mã đối tượng khám chữa bệnh theo nơi đăng ký ban đầu',
    `rule_type` = 'CONDITIONAL_FIELD',
    `rule_value` = '{"when_value":"1.1","when_operator":"=","other_field":"MA_DKBD","operator":"=","expected":"79025","data_type":"string","other_data_type":"string"}',
    `error_message` = 'Đối tượng KCB 1.1 không hợp lệ khi nơi đăng ký KCB ban đầu khác 79025',
    `is_active` = 1
WHERE `id` = 86
   OR (`file_type` = 'XML1'
       AND `field_name` = 'MA_DKBD'
       AND `rule_type` = 'SUBSTRING'
       AND `rule_value` LIKE '%"MA_DOITUONG_KCB"%'
       AND `rule_value` LIKE '%"1.1"%');

INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`, `error_message`, `is_active`)
SELECT
    'XML1',
    'MA_DOITUONG_KCB',
    'Mã đối tượng khám chữa bệnh theo nơi đăng ký ban đầu',
    'CONDITIONAL_FIELD',
    '{"when_value":"1.1","when_operator":"=","other_field":"MA_DKBD","operator":"=","expected":"79025","data_type":"string","other_data_type":"string"}',
    'Đối tượng KCB 1.1 không hợp lệ khi nơi đăng ký KCB ban đầu khác 79025',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML1'
      AND `field_name` = 'MA_DOITUONG_KCB'
      AND `rule_type` = 'CONDITIONAL_FIELD'
      AND `rule_value` LIKE '%"MA_DKBD"%'
      AND `rule_value` LIKE '%"1.1"%'
);
