-- MariaDB: safe to rerun. Preserve existing indexes used by other queries.
-- Equality columns precede the date range for department and queue lookups.
ALTER TABLE `file`
  ADD INDEX IF NOT EXISTS `idx_file_khoa_update` (`id_khoa`, `update_at`, `id`),
  ADD INDEX IF NOT EXISTS `idx_file_status_update` (`processing_status`, `update_at`, `id`);

ALTER TABLE `cong_hoso`
  ADD INDEX IF NOT EXISTS `idx_cong_hoso_ngay_day` (`ngay_day`, `id`);

-- Latest audit entry for a record, including deterministic ordering on ties.
ALTER TABLE `file_audit_logs`
  ADD INDEX IF NOT EXISTS `idx_audit_ma_lk_created_id` (`ma_lk`, `created_at`, `id`);
