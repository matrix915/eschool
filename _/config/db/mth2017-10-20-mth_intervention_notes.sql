CREATE TABLE `mth_intervention_notes` (
	`notes_id` INT NOT NULL AUTO_INCREMENT,
	`intervention_id` INT NULL DEFAULT NULL,
	`user_id` INT NOT NULL,
	`notes` TEXT NULL DEFAULT NULL,
	`date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`notes_id`),
	INDEX `intervention_id` (`intervention_id`),
	INDEX `user_id` (`user_id`)
);
