CREATE INDEX IF NOT EXISTS `idx_file_update_id`
    ON `file` (`update_at`, `id`);
