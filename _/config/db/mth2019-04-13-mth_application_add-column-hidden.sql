ALTER TABLE `mth_application`
	ADD COLUMN `hidden` INT(10) UNSIGNED NOT NULL DEFAULT 0 AFTER `accepted_by_user_id`;