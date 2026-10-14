ALTER TABLE `file`
    ADD COLUMN `ma_bn` VARCHAR(255) NULL AFTER `ma_lk`,
    ADD COLUMN `ho_ten` VARCHAR(255) NULL AFTER `ma_bn`;
