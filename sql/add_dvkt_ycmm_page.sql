-- Đăng ký trang CRUD danh mục DVKT yêu cầu có mã máy vào menu Danh mục.
INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`,
     `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT
    'Danh mục DVKT yêu cầu có mã máy', parent_menu.maChucNang, 'DVKT_YCMM',
    'fa fa-cogs', '', '', '', 110, 2
FROM `chucnang` AS parent_menu
WHERE parent_menu.url = ''
  AND parent_menu.tenChucNang = 'Danh mục'
  AND NOT EXISTS (SELECT 1 FROM `chucnang` WHERE url = 'DVKT_YCMM')
LIMIT 1;
