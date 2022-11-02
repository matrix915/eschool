CREATE TABLE `mth_resource_request` (
	`request_id` INT NOT NULL AUTO_INCREMENT,
	`parent_id` INT NOT NULL DEFAULT '0',
	`student_id` INT NOT NULL DEFAULT '0',
	`resource_id` INT NOT NULL,
	`school_year_id` INT NOT NULL DEFAULT '0',
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`request_id`),
	INDEX `parent_id` (`parent_id`),
	INDEX `student_id` (`student_id`),
	INDEX `resource_id` (`resource_id`),
	INDEX `school_year_id` (`school_year_id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB
;
