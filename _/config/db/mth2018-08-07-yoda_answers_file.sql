CREATE TABLE `yoda_answers_file` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`student_assesment_id` INT NULL,
	`mth_file_id` INT NOT NULL,
	`date_created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX `student_assesment_id` (`student_assesment_id`),
	INDEX `mth_file_id` (`mth_file_id`)
);
