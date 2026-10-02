INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT 'Khoa', 96, 'khoa', 'fa fa-hospital-o', '', 'Xem', 'khoa', 99, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'khoa');

UPDATE `chucnang`
SET `tenChucNang` = 'Khoa',
    `parent` = 96,
    `logo` = 'fa fa-hospital-o',
    `parentQuyen` = '',
    `tenChucNangCon` = 'Xem',
    `urlChucNangCon` = 'khoa',
    `order` = 99,
    `level` = 2
WHERE `url` = 'khoa';
