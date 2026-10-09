UPDATE `table_lookup_sources`
SET `allowed_columns` = '["ma","truong_hop"]',
    `is_active` = 1
WHERE `source_key` IN ('doituong_kcb', 'ma_doituong_kcb');
