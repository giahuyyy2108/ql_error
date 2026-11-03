-- Chạy một lần để hiển thị trang Hồ sơ theo khoa trong menu phân quyền.
-- Sau đó cấp quyền `hoso` cho nhóm người dùng cần truy cập.
INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`,
     `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT 'Hồ sơ theo khoa', 1, 'hoso', 'fa fa-folder-open', '',
       'Xem và quản lý hồ sơ', 'hoso', 132, 1
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'hoso');
