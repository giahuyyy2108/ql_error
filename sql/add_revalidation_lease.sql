ALTER TABLE `file`
    ADD COLUMN `revalidation_claimed_at` DATETIME NULL AFTER `processing_status`,
    ADD INDEX `idx_file_revalidation_lease` (`processing_status`, `revalidation_claimed_at`);

UPDATE `file`
SET `processing_status` = 'pending_revalidation',
    `revalidation_claimed_at` = NULL,
    `update_at` = `update_at`
WHERE `processing_status` = 'revalidating';
