ALTER TABLE `user` DROP FOREIGN KEY `fk_khoa`;
ALTER TABLE `file` DROP FOREIGN KEY `fk_khoaphong`;

ALTER TABLE `user` ADD COLUMN `id_khoa_new` VARCHAR(30)
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER `maNQ`;
UPDATE `user` u
LEFT JOIN `khoa` k_old ON k_old.id = u.id_khoa
LEFT JOIN `khoa_phong` kp
       ON kp.ma_khoa_goc IS NULL
      AND kp.is_active = 1
      AND (
          LOWER(TRIM(REPLACE(kp.ten, 'Khoa ', ''))) = LOWER(TRIM(k_old.ten))
          OR LOWER(kp.ten) LIKE CONCAT('%', LOWER(TRIM(k_old.ten)), '%')
      )
SET u.id_khoa_new = kp.ma
WHERE u.id_khoa IS NOT NULL;

ALTER TABLE `user` DROP COLUMN `id_khoa`;
ALTER TABLE `user` CHANGE `id_khoa_new` `id_khoa` VARCHAR(30)
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL AFTER `maNQ`;
ALTER TABLE `user` ADD INDEX `idx_user_khoa` (`id_khoa`);
ALTER TABLE `user`
  ADD CONSTRAINT `fk_user_khoa_phong`
  FOREIGN KEY (`id_khoa`) REFERENCES `khoa_phong` (`ma`)
  ON UPDATE CASCADE ON DELETE SET NULL;

ALTER TABLE `file` MODIFY `id_khoa` VARCHAR(30)
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;
ALTER TABLE `xml_upload_queue` MODIFY `id_khoa` VARCHAR(30)
  CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci NULL;

ALTER TABLE `file`
  ADD CONSTRAINT `fk_file_khoa_phong`
  FOREIGN KEY (`id_khoa`) REFERENCES `khoa_phong` (`ma`)
  ON UPDATE CASCADE ON DELETE SET NULL;
