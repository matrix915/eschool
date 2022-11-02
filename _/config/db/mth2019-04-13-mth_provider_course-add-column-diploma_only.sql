ALTER TABLE `mth_provider_course`
	ADD COLUMN `diploma_only` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `available`;