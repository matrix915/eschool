CREATE TABLE `mth_familynote` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`parent_id` INT NOT NULL DEFAULT '0',
	`note` TEXT NULL DEFAULT NULL,
	`date_created` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
	`date_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX `parent_id` (`parent_id`)
);