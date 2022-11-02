CREATE TABLE `mth_student_immunizations` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`student_id` INT NOT NULL,
	`immunization_id` INT NOT NULL,
	`date_administered` DATETIME NOT NULL,
	`exempt` TINYINT(1) NOT NULL,
	`nonapplicable` TINYINT(1) NOT NULL,
	`updated_by` INT NOT NULL,
	`date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`date_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);