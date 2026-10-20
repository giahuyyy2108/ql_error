INSERT INTO `chucnang` (`tenChucNang`, `parent`, `url`, `icon`, `urlChucNangCon`, `quyen`, `stt`, `level`)
SELECT 'Khối', 96, 'khoi', 'fa fa-sitemap', '', 'Xem', 100, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'khoi');
