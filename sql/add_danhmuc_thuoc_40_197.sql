INSERT INTO `danhmuc_thuoc` (
    `ma_hoat_chat`, `ten_hoat_chat`, `duong_dung_dang_bao_che`,
    `nong_do_ham_luong`, `ten_thuoc`, `sdk_gpnk`, `sdk_chuan_hoa`,
    `don_vi_tinh`, `source_url`, `is_active`
)
SELECT
    '40.197',
    'Amikacin',
    '2.10 - Dung dịch tiêm',
    'Amikacin sulfate 500mg/2ml',
    'Aju Amikacin Injection 500mg/2mL',
    '880110007325',
    '880110007325',
    'Ống',
    'Hồ sơ MA_LK 26951977260921080645267031',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `danhmuc_thuoc`
    WHERE `ma_hoat_chat` = '40.197' AND `sdk_chuan_hoa` = '880110007325'
);
