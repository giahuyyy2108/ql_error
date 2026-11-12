-- Add individual Create / Update / Delete permissions to existing catalog menus.
-- Safe to run repeatedly; duplicate menu URLs are updated consistently.
-- Reassign operation permissions in Nhom quyen and sign in again to take effect.

UPDATE `chucnang`
SET `tenChucNangCon` = 'Xem,Thêm,Sửa,Xóa',
    `urlChucNangCon` = CONCAT(
        `url`, ',',
        `url`, '.save,',
        `url`, '.update,',
        `url`, '.delete'
    )
WHERE `url` IN (
    'dantoc',
    'khoi',
    'khoa',
    'khoaphong',
    'tinhthanh',
    'danhmuc_thuoc',
    'doituong_kcb',
    'loai_kcb',
    'icd10',
    'icd10_yhct',
    'nhanvienyte',
    'dichvu_bv',
    'vattu_yte',
    'trang_thiet_bi'
);
