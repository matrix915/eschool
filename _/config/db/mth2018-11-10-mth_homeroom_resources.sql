CREATE TABLE `mth_resource_settings` (
	`resource_id` INT NOT NULL AUTO_INCREMENT,
	`resource_name` VARCHAR(90) NULL,
	`min_grade_level` TINYINT NULL,
	`max_grade_level` TINYINT NULL,
	`hidden` TINYINT NULL DEFAULT NULL,
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`resource_id`)
)
COLLATE='utf8_general_ci'
ENGINE=InnoDB;
