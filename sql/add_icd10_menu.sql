INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT 'ICD-10 tiếng Việt', 96, 'icd10', 'fa fa-medkit', '', 'Xem', 'icd10', 97, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'icd10');

