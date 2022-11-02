ALTER TABLE `mth_resource_settings`
	ADD COLUMN `cost` FLOAT NULL AFTER `content`,
	ADD COLUMN `show_cost` TINYINT(3) NULL DEFAULT 0,
	ADD COLUMN `resource_type` TINYINT(3) NULL DEFAULT 1;
