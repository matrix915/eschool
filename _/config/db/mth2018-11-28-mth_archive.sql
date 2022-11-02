CREATE TABLE `mth_archive` (
	`archive_id` INT NOT NULL AUTO_INCREMENT,
	`student_id` INT NOT NULL DEFAULT '0',
	`student_status` INT NOT NULL DEFAULT '0',
	`schedule_status` INT NOT NULL DEFAULT '0',
	`homeroom_id` INT NOT NULL DEFAULT '0',
	`school_year_id` INT NOT NULL DEFAULT '0',
	`status` INT NOT NULL DEFAULT '0',
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`archive_id`),
	INDEX `student_id` (`student_id`, `homeroom_id`),
	INDEX `school_year_id` (`school_year_id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;
