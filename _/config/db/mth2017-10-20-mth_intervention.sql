CREATE TABLE `mth_intervention` (
	`intervention_id` INT NOT NULL AUTO_INCREMENT,
	`mth_student_id` INT NOT NULL,
	`zero_count` INT NULL DEFAULT NULL,
	`grade` FLOAT NULL DEFAULT NULL,
	`school_year_id` INT NULL DEFAULT NULL,
	`last_login` DATETIME NULL DEFAULT NULL,
    `date_updated` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`label_id` INT NULL,
	PRIMARY KEY (`intervention_id`),
	INDEX `mth_student_id` (`mth_student_id`),
	INDEX `school_year_id` (`school_year_id`),
    INDEX `label_id` (`label_id`)
);