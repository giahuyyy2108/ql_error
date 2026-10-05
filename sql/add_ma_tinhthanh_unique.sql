ALTER TABLE `tinhthanh`
    ADD UNIQUE KEY `uq_ma_tinhthanh_ma_cu` (`ma_cu`),
    ADD KEY `idx_ma_tinhthanh_ma_sau_sapnhap` (`ma_sau_sapnhap`);
