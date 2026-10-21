ALTER TABLE `file`
ADD COLUMN `da_day_cong` BOOLEAN NOT NULL DEFAULT FALSE
AFTER `processing_status`;
