CREATE TABLE `mth_events` (
	`event_id` INT NOT NULL AUTO_INCREMENT,
	`name` VARCHAR(90) NULL DEFAULT '0',
	`color` VARCHAR(50) NULL DEFAULT '#1e88e5',
	`content` TEXT NULL,
	`start_date` DATETIME NULL,
	`end_date` DATETIME NULL,
	`created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` DATETIME NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`event_id`)
);
