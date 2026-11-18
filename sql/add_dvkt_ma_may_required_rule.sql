INSERT INTO `rule`
    (`code`, `display_name`, `description`, `value_hint`, `requires_value`, `is_active`)
VALUES
    ('TABLE_MATCH_REQUIRED',
     'Bắt buộc khi mã thuộc danh mục',
     'Bắt buộc nhập trường đích khi một trường khác cùng dòng tồn tại trong bảng danh mục.',
     '{"source_field":"MA_DICH_VU","table":"danh_muc_dvkt_ma_may","column":"ma_dvkt","conditions":{"hieu_luc":1}}',
     1, 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `description` = VALUES(`description`),
    `value_hint` = VALUES(`value_hint`),
    `requires_value` = VALUES(`requires_value`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `table_lookup_sources`
    (`source_key`, `display_name`, `table_name`, `allowed_columns`, `condition_columns`, `is_active`)
VALUES
    ('danh_muc_dvkt_ma_may', 'Danh mục DVKT bắt buộc có mã máy',
     'danh_muc_dvkt_ma_may', '["ma_dvkt"]', '["hieu_luc"]', 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `table_name` = VALUES(`table_name`),
    `allowed_columns` = VALUES(`allowed_columns`),
    `condition_columns` = VALUES(`condition_columns`),
    `is_active` = VALUES(`is_active`);

INSERT INTO `xml_validation_rules`
    (`file_type`, `field_name`, `display_name`, `rule_type`, `rule_value`,
     `error_message`, `huongxuly`, `severity`, `is_active`)
SELECT
    'XML3', 'MA_MAY', 'Mã máy của dịch vụ kỹ thuật', 'TABLE_MATCH_REQUIRED',
    '{"source_field":"MA_DICH_VU","table":"danh_muc_dvkt_ma_may","column":"ma_dvkt","conditions":{"hieu_luc":1}}',
    'Dịch vụ {MA_DICH_VU} thuộc danh mục bắt buộc có mã máy nhưng MA_MAY đang để trống',
    'Bổ sung MA_MAY cho dịch vụ kỹ thuật.', 'error', 1
WHERE NOT EXISTS (
    SELECT 1 FROM `xml_validation_rules`
    WHERE `file_type` = 'XML3'
      AND `field_name` = 'MA_MAY'
      AND `rule_type` = 'TABLE_MATCH_REQUIRED'
);

UPDATE `xml_validation_rules`
SET `display_name` = 'Mã máy của dịch vụ kỹ thuật',
    `rule_value` = '{"source_field":"MA_DICH_VU","table":"danh_muc_dvkt_ma_may","column":"ma_dvkt","conditions":{"hieu_luc":1}}',
    `error_message` = 'Dịch vụ {MA_DICH_VU} thuộc danh mục bắt buộc có mã máy nhưng MA_MAY đang để trống',
    `huongxuly` = 'Bổ sung MA_MAY cho dịch vụ kỹ thuật.',
    `severity` = 'error',
    `is_active` = 1
WHERE `file_type` = 'XML3'
  AND `field_name` = 'MA_MAY'
  AND `rule_type` = 'TABLE_MATCH_REQUIRED';
