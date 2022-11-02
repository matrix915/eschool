CREATE TABLE IF NOT EXISTS `mth_email_sent` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL,
  `type` varchar(30) NULL,
  `date_created` datetime DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;