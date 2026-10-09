INSERT INTO `quanhuyen_phuongxa` (
    `ma_tinh_cu`, `ten_tinh_cu`, `ma_quanhuyen_cu`, `ten_quanhuyen_cu`, `loai_quanhuyen_cu`,
    `ma_phuongxa_cu`, `ten_phuongxa_cu`, `loai_phuongxa_cu`,
    `ma_tinh_moi`, `ten_tinh_moi`, `ma_phuongxa_moi`, `ten_phuongxa_moi`, `loai_phuongxa_moi`,
    `sap_nhap_mot_phan`, `ngay_hieu_luc`, `nguon`
)
SELECT
    10, 'Tỉnh Lào Cai', 80, 'Thành phố Lào Cai', 'thành phố',
    2638, 'Phường Lào Cai', 'phường',
    15, 'Tỉnh Lào Cai', 2647, 'Phường Lào Cai', 'phường',
    0, '2025-07-01',
    'Mã lịch sử 02638 theo Quyết định 124/2004/QĐ-TTg; đối chiếu Phường Lào Cai mới sau sắp xếp 2025'
WHERE NOT EXISTS (
    SELECT 1 FROM `quanhuyen_phuongxa`
    WHERE `ma_phuongxa_cu` = 2638 AND `ma_phuongxa_moi` = 2647
);
