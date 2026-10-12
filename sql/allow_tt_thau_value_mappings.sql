UPDATE `table_lookup_sources`
SET `allowed_columns` = '["ma_hoat_chat","sdk_gpnk","sdk_chuan_hoa","ten_thuoc","so_quyet_dinh","goi_thau","nhom_tieu_chi","ngay_cong_bo","tinh_thanh"]',
    `condition_columns` = '["is_active","ma_hoat_chat","nhom_tieu_chi","goi_thau","tinh_thanh"]',
    `is_active` = 1
WHERE `source_key` = 'danhmuc_thuoc';
