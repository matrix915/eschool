CREATE TABLE `mth_assistant_user` (
	`assistant_id` INT NOT NULL AUTO_INCREMENT,
	`user_id` INT NOT NULL DEFAULT '0',
	`value` INT NULL DEFAULT '0',
	`type` INT NULL DEFAULT '0',
	`datecreated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`assistant_id`),
	INDEX `user_id` (`user_id`)
);
