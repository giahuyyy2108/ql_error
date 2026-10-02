INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT 'Dân tộc', 96, 'dantoc', 'fa fa-users', '', 'Xem', 'dantoc', 98, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'dantoc');

UPDATE `chucnang`
SET `tenChucNang` = 'Dân tộc',
    `parent` = 96,
    `logo` = 'fa fa-users',
    `parentQuyen` = '',
    `tenChucNangCon` = 'Xem',
    `urlChucNangCon` = 'dantoc',
    `order` = 98,
    `level` = 2
WHERE `url` = 'dantoc';
