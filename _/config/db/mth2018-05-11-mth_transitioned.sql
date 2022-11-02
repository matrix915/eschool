CREATE TABLE IF NOT EXISTS `mth_transitioned` (
  `transition_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `student_id` int(10) unsigned DEFAULT NULL,
  `school_year_id` int(10) unsigned DEFAULT NULL,
  `reason` tinyint(3) unsigned DEFAULT NULL,
  `new_school_name` varchar(255) DEFAULT NULL,
  `new_school_address` varchar(255) DEFAULT NULL,
  `sig_file_id` int(10) unsigned DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `status` tinyint(3) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`transition_id`),
  KEY `student_id` (`student_id`),
  KEY `school_year_id` (`school_year_id`)
);