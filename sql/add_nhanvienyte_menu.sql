UPDATE `chucnang`
SET `tenChucNang` = 'Nhân viên y tế',
    `url` = 'nhanvienyte',
    `logo` = 'fa fa-user-md',
    `tenChucNangCon` = 'Xem',
    `urlChucNangCon` = 'nhanvienyte'
WHERE `maChucNang` = 109;

UPDATE `nhomquyen`
SET `quyen` = CONCAT_WS(',', NULLIF(TRIM(BOTH ',' FROM `quyen`), ''), 'nhanvienyte')
WHERE `maNQ` IN (5, 6)
  AND FIND_IN_SET('nhanvienyte', `quyen`) = 0;
