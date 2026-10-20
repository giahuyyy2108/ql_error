INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `icon`, `urlChucNangCon`, `quyen`, `stt`, `level`)
SELECT 'Khoa phòng', 96, 'khoaphong', 'fa fa-hospital-o', '', 'Xem', 101, 2
WHERE NOT EXISTS (
    SELECT 1 FROM `chucnang` WHERE `url` = 'khoaphong'
);
