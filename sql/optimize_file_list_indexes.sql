ALTER TABLE `file`
  ADD INDEX `idx_file_list_date_khoa` (`update_at`, `da_day_cong`, `id_khoa`, `id`);
