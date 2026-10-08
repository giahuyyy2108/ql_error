UPDATE `chucnang`
SET `tenChucNang` = 'Loại khám chữa bệnh',
    `url` = 'loai_kcb',
    `logo` = 'fa fa-hospital-o',
    `tenChucNangCon` = 'Xem',
    `urlChucNangCon` = 'loai_kcb'
WHERE `maChucNang` = 105;

UPDATE `nhomquyen`
SET `quyen` = CONCAT_WS(',', NULLIF(TRIM(BOTH ',' FROM `quyen`), ''), 'loai_kcb')
WHERE `maNQ` IN (5, 6)
  AND FIND_IN_SET('loai_kcb', `quyen`) = 0;
