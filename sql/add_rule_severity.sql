ALTER TABLE `xml_validation_rules`
  ADD COLUMN `severity` ENUM('error','warning') NOT NULL DEFAULT 'error' AFTER `huongxuly`;
