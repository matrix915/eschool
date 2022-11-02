CREATE TABLE `mth_offense_notif` (
  `offense_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `mth_student_id` int(10) unsigned DEFAULT NULL,
  `grade` float DEFAULT NULL,
  `zero_count` int(11) DEFAULT null,
  `last_login` datetime DEFAULT NULL,
  `email_sent` datetime DEFAULT NULL,
  `school_year_id` int(11) DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`offense_id`),
  KEY `mth_student_id` (`mth_student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;