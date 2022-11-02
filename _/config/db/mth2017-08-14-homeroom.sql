CREATE TABLE IF NOT EXISTS `mth_homeroom` (
  `canvas_course_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(50) NOT NULL,
  `deleted` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`canvas_course_id`),
  KEY `school_year_id` (`school_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_student_homeroom` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `homeroom_canvas_course_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`student_id`,`school_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;