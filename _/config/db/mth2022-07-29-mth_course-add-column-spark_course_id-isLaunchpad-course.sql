ALTER TABLE `mth_course` 
    ADD `spark_course_id` VARCHAR (50) DEFAULT NULL,
    ADD `is_launchpad_course` TINYINT(1) NULL DEFAULT 0;