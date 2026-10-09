INSERT INTO `table_lookup_sources`
    (`source_key`, `display_name`, `table_name`, `allowed_columns`, `condition_columns`, `is_active`)
VALUES
    ('quanhuyen_phuongxa', 'Quận huyện - phường xã trước/sau sáp nhập',
     'quanhuyen_phuongxa',
     '["ma_phuongxa_cu","ten_phuongxa_cu","ma_phuongxa_moi","ten_phuongxa_moi","ma_quanhuyen_cu","ten_quanhuyen_cu"]',
     '["ma_tinh_cu","ma_tinh_moi","ma_quanhuyen_cu","sap_nhap_mot_phan"]', 1)
ON DUPLICATE KEY UPDATE
    `display_name` = VALUES(`display_name`),
    `table_name` = VALUES(`table_name`),
    `allowed_columns` = VALUES(`allowed_columns`),
    `condition_columns` = VALUES(`condition_columns`),
    `is_active` = VALUES(`is_active`);

UPDATE `xml_validation_rules`
SET `rule_type` = 'TABLE_EXISTS',
    `rule_value` = '{"table":"quanhuyen_phuongxa","column":"ma_phuongxa_moi","fallback_columns":["ma_phuongxa_cu"],"return_column":"ten_phuongxa_moi"}',
    `error_message` = 'Mã phường/xã cư trú không tồn tại trong danh mục trước hoặc sau sáp nhập',
    `is_active` = 1
WHERE `file_type` = 'XML1'
  AND `field_name` = 'MAXA_CU_TRU';
