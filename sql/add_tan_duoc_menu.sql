INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT 'Danh mục tân dược', 96, 'tanduoc', 'fa fa-medkit', '', 'Xem', 'tanduoc', 98, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'tanduoc');

