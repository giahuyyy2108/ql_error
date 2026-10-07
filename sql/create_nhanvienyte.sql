CREATE TABLE IF NOT EXISTS `nhanvienyte` (
    `id` BIGINT UNSIGNED NOT NULL,
    `stt` INT UNSIGNED DEFAULT NULL,
    `den_ngay` DATE DEFAULT NULL,
    `ma_loai_kcb` VARCHAR(10) DEFAULT NULL,
    `ma_bhxh` VARCHAR(20) DEFAULT NULL,
    `ho_ten` VARCHAR(255) NOT NULL,
    `gioi_tinh` TINYINT UNSIGNED DEFAULT NULL,
    `ngay_sinh` DATE DEFAULT NULL,
    `so_cccd` VARCHAR(20) DEFAULT NULL,
    `chucdanh_nn` VARCHAR(20) DEFAULT NULL,
    `vi_tri` VARCHAR(20) DEFAULT NULL,
    `macchn` VARCHAR(50) DEFAULT NULL,
    `ngaycap_cchn` DATE DEFAULT NULL,
    `noicap_cchn` VARCHAR(255) DEFAULT NULL,
    `phamvi_cm` TEXT DEFAULT NULL,
    `phamvi_cmbs` TEXT DEFAULT NULL,
    `dvkt_khac` TEXT DEFAULT NULL,
    `vb_phancong` TEXT DEFAULT NULL,
    `thoigian_dk` VARCHAR(20) DEFAULT NULL,
    `thoigian_ngay` VARCHAR(255) DEFAULT NULL,
    `thoigian_tuan` VARCHAR(100) DEFAULT NULL,
    `cskcb_khac` VARCHAR(255) DEFAULT NULL,
    `cskcb_cgkt` VARCHAR(255) DEFAULT NULL,
    `qd_cgkt` TEXT DEFAULT NULL,
    `tu_ngay` DATE DEFAULT NULL,
    `ma_dantoc` VARCHAR(20) DEFAULT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_nhanvienyte_ma_bhxh` (`ma_bhxh`),
    KEY `idx_nhanvienyte_ho_ten` (`ho_ten`),
    KEY `idx_nhanvienyte_so_cccd` (`so_cccd`),
    KEY `idx_nhanvienyte_macchn` (`macchn`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `nhanvienyte_khoa` (
    `nhanvienyte_id` BIGINT UNSIGNED NOT NULL,
    `ma_khoa` VARCHAR(30) NOT NULL,
    PRIMARY KEY (`nhanvienyte_id`, `ma_khoa`),
    KEY `idx_nhanvienyte_khoa_ma_khoa` (`ma_khoa`),
    CONSTRAINT `fk_nhanvienyte_khoa_nhanvien`
        FOREIGN KEY (`nhanvienyte_id`) REFERENCES `nhanvienyte` (`id`)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT `fk_nhanvienyte_khoa_khoa`
        FOREIGN KEY (`ma_khoa`) REFERENCES `khoa` (`ma`)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
