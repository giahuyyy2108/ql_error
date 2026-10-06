INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT 'Nguồn đối chiếu', 96, 'tablelookup', 'fa fa-database', '',
       'Xem,Thêm,Sửa,Xóa,Bật/Tắt',
       'tablelookup,tablelookup.save,tablelookup.update,tablelookup.delete,tablelookup.toggleStatus', 102, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'tablelookup');

UPDATE `chucnang`
SET `tenChucNangCon` = 'Xem,Thêm,Sửa,Xóa,Bật/Tắt',
    `urlChucNangCon` = 'tablelookup,tablelookup.save,tablelookup.update,tablelookup.delete,tablelookup.toggleStatus'
WHERE `url` = 'tablelookup';
