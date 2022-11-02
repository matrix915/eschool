ALTER TABLE `mth_offense_notif`
	ADD COLUMN `intervention_id` INT NOT NULL AFTER `offense_id`,
	ADD COLUMN `type` INT NOT NULL COMMENT '1 if first 2 if final' AFTER `intervention_id`,
	CHANGE COLUMN `date_created` `date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `type`,
	DROP COLUMN `grade`,
	DROP COLUMN `zero_count`,
	DROP COLUMN `last_login`,
	ADD INDEX `intervention_id` (`intervention_id`);
