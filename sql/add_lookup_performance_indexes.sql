CREATE INDEX IF NOT EXISTS `idx_danhmuc_thuoc_ma_active`
    ON `danhmuc_thuoc` (`ma_hoat_chat`, `is_active`);

CREATE INDEX IF NOT EXISTS `idx_icd10_yhct_ma_active`
    ON `icd10_yhct` (`ma_icd10`, `is_active`);
