INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT
    'Cấu hình API', 1, 'apiValidation', 'fa fa-exchange', '',
    'Xem,Thêm,Sửa,Xóa,Test API',
    'apiValidation,apiValidation.save,apiValidation.update,apiValidation.delete,apiValidation.test',
    93, 1
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'apiValidation');

UPDATE `chucnang`
SET `tenChucNangCon` = 'Xem,Thêm,Sửa,Xóa,Test API',
    `urlChucNangCon` = 'apiValidation,apiValidation.save,apiValidation.update,apiValidation.delete,apiValidation.test'
WHERE `url` = 'apiValidation';
