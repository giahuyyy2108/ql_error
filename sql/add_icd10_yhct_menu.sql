INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT
    'ICD-10 Y học cổ truyền', 96, 'icd10_yhct', 'fa fa-leaf', '',
    'Xem', 'icd10_yhct', 98, 2
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'icd10_yhct');
