INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT 'Danh mục thuốc', 96, 'danhmuc_thuoc', 'fa fa-medkit', '', 'Xem', 'danhmuc_thuoc', 98, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'danhmuc_thuoc');
