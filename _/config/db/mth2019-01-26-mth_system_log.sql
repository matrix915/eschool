CREATE TABLE `mth_system_log` (
	`log_id` INT NOT NULL AUTO_INCREMENT,
	`user_id` INT NOT NULL DEFAULT '0',
	`new_value` LONGTEXT NULL DEFAULT NULL,
	`old_value` LONGTEXT NULL DEFAULT NULL,
	`type`	VARCHAR(50) NULL DEFAULT NULL,
	`archive` INT NULL DEFAULT '0',
	`tag` VARCHAR(50) NULL DEFAULT '0',
	`date_created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`date_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`log_id`),
	INDEX `user_id` (`user_id`)
);