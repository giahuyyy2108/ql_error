ALTER TABLE `file` ADD COLUMN `id_khoa` INT NULL AFTER `validation_result`;
ALTER TABLE `file`
    ADD CONSTRAINT `fk_file_khoa`
    FOREIGN KEY (`id_khoa`) REFERENCES `khoa` (`id`)
    ON UPDATE CASCADE ON DELETE SET NULL;
