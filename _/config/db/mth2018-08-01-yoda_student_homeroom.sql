CREATE TABLE `yoda_student_homeroom` (
	`student_id` INT NOT NULL,
	`school_year_id` INT NOT NULL,
	`yoda_course_id` INT NOT NULL,
	INDEX `yoda_course_id` (`yoda_course_id`),
	INDEX `student_id` (`student_id`)
);
