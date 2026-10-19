INSERT INTO `rule`
    (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`)
VALUES
(
    'CROSS_FILE_ANY_MATCH',
    'Khớp một giá trị ở file khác',
    'Chỉ áp dụng khi trường nguồn thuộc danh sách cấu hình; ít nhất một trường đích ở XML khác phải khớp giá trị hoặc tiền tố cho phép.',
    '{"when_values":["SOURCE"],"target_file_type":"XML1","target_fields":["FIELD_A","FIELD_B"],"match_type":"PREFIX","expected_values":["VALUE"],"overrides":[{"when_values":["SPECIAL_SOURCE"],"expected_values":["SPECIAL_VALUE"]}]}',
    1,
    1
)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`),
    `is_active` = VALUES(`is_active`);

UPDATE `xml_validation_rules`
SET
    `rule_type` = 'CROSS_FILE_ANY_MATCH',
    `rule_value` = '{"when_values":["40.65","40.66","40.68","40.74","40.76"],"target_file_type":"XML1","target_fields":["MA_BENH_CHINH","MA_BENH_KT"],"match_type":"PREFIX","expected_values":["M80","M81","M82","M85"],"overrides":[{"when_values":["40.76"],"expected_values":["M80","M81","M82","M85","C79.5"]}]}',
    `display_name` = 'Mã thuốc chống hủy xương',
    `error_message` = 'Thuốc không khớp chỉ định {MA_THUOC} Thuốc điều trị loãng xương',
    `is_active` = 1
WHERE `id` = 106;

-- Use UTF-8 byte literals so this migration is safe when executed from a
-- Windows console configured with a legacy code page.
UPDATE `rule`
SET `display_name` = CONVERT(0x4B68E1BB9B70206DE1BB9974206769C3A1207472E1BB8B20E1BB9F2066696C65206B68C3A163 USING utf8mb4)
WHERE `code` = 'CROSS_FILE_ANY_MATCH';

UPDATE `xml_validation_rules`
SET
    `display_name` = CONVERT(0x4DC3A320746875E1BB9163206368E1BB916E672068E1BBA7792078C6B0C6A16E67 USING utf8mb4),
    `error_message` = CONVERT(0x546875E1BB9163206B68C3B46E67206B68E1BB9B70206368E1BB8920C491E1BB8B6E68207B4D415F5448554F437D20546875E1BB916320C49169E1BB8175207472E1BB8B206C6FC3A36E672078C6B0C6A16E67 USING utf8mb4)
WHERE `id` = 106;

DELETE FROM `rule`
WHERE `code` = 'ANTIRESORPTIVE_INDICATION'
  AND NOT EXISTS (
      SELECT 1 FROM `xml_validation_rules`
      WHERE `rule_type` = 'ANTIRESORPTIVE_INDICATION'
  );
