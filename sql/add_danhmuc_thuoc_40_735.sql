INSERT INTO `danhmuc_thuoc` (
    `ma_hoat_chat`, `ten_hoat_chat`, `duong_dung_dang_bao_che`,
    `nong_do_ham_luong`, `ten_thuoc`, `sdk_gpnk`, `sdk_chuan_hoa`,
    `don_vi_tinh`, `source_url`, `is_active`
)
SELECT
    '40.735',
    'Diosmin',
    '1.01 - Viên nén bao phim',
    '600mg',
    'Phlebodia',
    '300110025223',
    '300110025223',
    'Viên',
    'Hồ sơ MA_LK 20932714261002094025420248',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `danhmuc_thuoc`
    WHERE `ma_hoat_chat` = '40.735' AND `sdk_chuan_hoa` = '300110025223'
);
