ALTER TABLE `mth_resource_settings`
	ADD COLUMN `show_parent` TINYINT(4) NULL DEFAULT 1,
	ADD COLUMN `image` VARCHAR(160) NULL DEFAULT NULL,
	ADD COLUMN `content` TEXT NULL DEFAULT NULL;