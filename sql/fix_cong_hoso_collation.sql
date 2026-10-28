-- Dong bo cac ma dinh danh cua cong_hoso voi bang file/khoa_phong.
-- Khac collation o malk va file.MA_LK gay MySQL error 1267 khi so sanh '='.

ALTER TABLE `cong_hoso`
  MODIFY `malk` VARCHAR(255)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NOT NULL,
  MODIFY `id_khoa` VARCHAR(50)
    CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
