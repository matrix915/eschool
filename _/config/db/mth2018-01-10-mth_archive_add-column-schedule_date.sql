ALTER TABLE `mth_archive`
	ADD COLUMN `schedule_date` DATETIME NULL DEFAULT NULL AFTER `schedule_status`;
ALTER TABLE `mth_archive`
	ADD COLUMN `status_date` DATETIME NULL DEFAULT NULL AFTER `student_status`;
