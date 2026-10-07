-- Chỉ xóa cột cũ sau khi tất cả mã BHYT đã có ít nhất một diện ở bảng liên kết.
SET @missing_bhyt_dien := (
    SELECT COUNT(*)
    FROM `nhom_BHYT` b
    LEFT JOIN `nhom_BHYT_dien` d ON d.`ma_bhyt` = b.`id`
    WHERE d.`ma_bhyt` IS NULL
);

SET @drop_dien_sql := IF(
    @missing_bhyt_dien = 0
    AND EXISTS(
        SELECT 1 FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = 'nhom_bhyt'
          AND COLUMN_NAME = 'dien'
    ),
    'ALTER TABLE `nhom_BHYT` DROP COLUMN `dien`',
    'SELECT 1'
);

PREPARE drop_dien_statement FROM @drop_dien_sql;
EXECUTE drop_dien_statement;
DEALLOCATE PREPARE drop_dien_statement;
