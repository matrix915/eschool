CREATE TABLE IF NOT EXISTS `mth_student_reenrollment_status` (
 `student_id` INT NOT NULL,
 `school_year_id` INT NOT NULL,
 `reenrolled` TINYINT(3) DEFAULT 0,
 PRIMARY KEY (`student_id`, `school_year_id`)
);