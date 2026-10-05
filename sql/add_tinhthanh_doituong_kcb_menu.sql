INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT 'Tỉnh thành', 96, 'tinhthanh', 'fa fa-map-marker', '', 'Xem', 'tinhthanh', 99, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'tinhthanh');

UPDATE `chucnang`
SET `tenChucNang`='Tỉnh thành', `parent`=96, `logo`='fa fa-map-marker',
    `parentQuyen`='', `tenChucNangCon`='Xem', `urlChucNangCon`='tinhthanh',
    `order`=99, `level`=2
WHERE `url`='tinhthanh';

INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT 'Loại hình KCB', 96, 'doituong_kcb', 'fa fa-hospital-o', '', 'Xem', 'doituong_kcb', 100, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'doituong_kcb');

UPDATE `chucnang`
SET `tenChucNang`='Loại hình KCB', `parent`=96, `logo`='fa fa-hospital-o',
    `parentQuyen`='', `tenChucNangCon`='Xem', `urlChucNangCon`='doituong_kcb',
    `order`=100, `level`=2
WHERE `url`='doituong_kcb';
