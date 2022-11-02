ALTER TABLE `mth_schedule`
	ADD INDEX `student_id` (`student_id`),
	ADD INDEX `school_year_id` (`school_year_id`);
ALTER TABLE `mth_schedule_period`
	ADD INDEX `schedule_id` (`schedule_id`),
	ADD INDEX `subject_id` (`subject_id`),
	ADD INDEX `course_id` (`course_id`),
	ADD INDEX `mth_provider_id` (`mth_provider_id`),
	ADD INDEX `provider_course_id` (`provider_course_id`);

     