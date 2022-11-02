CREATE TABLE `mth_course_state_code` (
    `course_state_code_id` INT NOT NULL AUTO_INCREMENT,
    `school_year_id` INT NULL,
    `grade` INT NULL ,
    `course_id` INT NULL,
    `subject_id` INT NULL ,
    `state_code` VARCHAR(50) NULL DEFAULT NULL,
    `teacher_name` VARCHAR(50) NULL DEFAULT NULL,
    PRIMARY KEY (`course_state_code_id`)
)
