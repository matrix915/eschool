CREATE TABLE `yoda_plgs` (
	`plg_id` INT NOT NULL AUTO_INCREMENT,
	`grade_level` VARCHAR(50) NOT NULL DEFAULT '0',
	`plg_name` VARCHAR(50) NOT NULL DEFAULT '0',
	`school_year_id` INT NOT NULL DEFAULT '0',
	`subject` VARCHAR(50) NOT NULL DEFAULT '0',
	`date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	INDEX `plg_id` (`plg_id`),
	PRIMARY KEY (`plg_id`),
	INDEX `school_year_id` (`school_year_id`)
);
