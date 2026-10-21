-- Bổ sung hướng xử lý cho từng XML validation rule.
ALTER TABLE `xml_validation_rules`
    ADD COLUMN `huongxuly` TEXT NULL AFTER `error_message`;
