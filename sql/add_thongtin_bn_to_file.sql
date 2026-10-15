ALTER TABLE `file`
    ADD COLUMN IF NOT EXISTS `thongtin_bn` JSON NULL AFTER `ho_ten`;

ALTER TABLE `file`
    MODIFY COLUMN `thongtin_bn` JSON NULL;
