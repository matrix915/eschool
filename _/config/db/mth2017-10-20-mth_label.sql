CREATE TABLE `mth_label` (
	`label_id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(90) NULL DEFAULT NULL,
	`user_id` INT NOT NULL,
	`date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`label_id`),
	INDEX `user_id` (`user_id`)
);
