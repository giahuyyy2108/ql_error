INSERT INTO `danhmuc_thuoc` (
    `ma_hoat_chat`, `ten_hoat_chat`, `duong_dung_dang_bao_che`,
    `nong_do_ham_luong`, `ten_thuoc`, `sdk_gpnk`, `sdk_chuan_hoa`,
    `don_vi_tinh`, `source_url`, `is_active`
)
SELECT
    '40.212',
    'Metronidazole',
    '2.05 - Dung dịch tiêm truyền tĩnh mạch',
    '500mg/100ml',
    'Metronidazole 0,5g/100ml',
    'VD-34057-20',
    'VD-34057-20',
    'Túi',
    'storage/xml/failed/PASS3_XML2_Ma_hoat_chat_khong_hop_le/PASS3_XML2_Ma_hoat_chat_khong_hop_le_26952535_DN479910711296274039_2609240120_2610020900_449088_74.xml',
    1
WHERE NOT EXISTS (
    SELECT 1 FROM `danhmuc_thuoc`
    WHERE `ma_hoat_chat` = '40.212' AND `sdk_chuan_hoa` = 'VD-34057-20'
);
