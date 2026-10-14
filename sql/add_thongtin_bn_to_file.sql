ALTER TABLE `file`
    ADD COLUMN IF NOT EXISTS `thongtin_bn` JSON NULL AFTER `ho_ten`;
