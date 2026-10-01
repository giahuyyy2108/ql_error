ALTER TABLE `xml_validation_rules`
    ADD INDEX `idx_xml_validation_rules_rule_type` (`rule_type`),
    ADD CONSTRAINT `fk_xml_validation_rules_rule`
        FOREIGN KEY (`rule_type`) REFERENCES `rule` (`code`)
        ON UPDATE CASCADE
        ON DELETE RESTRICT;

INSERT INTO `chucnang`
    (`tenChucNang`, `parent`, `url`, `logo`, `parentQuyen`, `tenChucNangCon`, `urlChucNangCon`, `order`, `level`)
SELECT
    'Loại rule', 1, 'rule', 'fa fa-check-square-o', '',
    'Xem,Thêm,Sửa,Xóa', 'rule,rule.save,rule.update,rule.delete', 92, 1
WHERE NOT EXISTS (SELECT 1 FROM `chucnang` WHERE `url` = 'rule');

