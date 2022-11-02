CREATE TABLE IF NOT EXISTS `mth_address` (
  `address_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(20) DEFAULT NULL,
  `street` varchar(120) DEFAULT NULL,
  `street2` varchar(120) DEFAULT NULL,
  `city` varchar(60) DEFAULT NULL,
  `state` varchar(2) DEFAULT NULL,
  `zip` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`address_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_application` (
  `application_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int(10) UNSIGNED DEFAULT NULL,
  `school_year_id` int(10) UNSIGNED DEFAULT NULL,
  `status` varchar(20) DEFAULT NULL,
  `city_of_residence` varchar(60) DEFAULT NULL,
  `agrees_to_policies` tinyint(4) DEFAULT NULL,
  `referred_by` varchar(120) DEFAULT NULL,
  `date_started` datetime NOT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `date_accepted` datetime DEFAULT NULL,
  `accepted_by_user_id` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`application_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_canvas_course` (
  `canvas_course_id` int(10) UNSIGNED NOT NULL,
  `mth_course_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `workflow_state` tinyint(3) UNSIGNED NOT NULL,
  PRIMARY KEY (`canvas_course_id`),
  UNIQUE KEY `mth_course_id` (`mth_course_id`,`school_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_canvas_enrollment` (
  `canvas_enrollment_id` int(10) UNSIGNED NOT NULL,
  `canvas_user_id` int(10) UNSIGNED NOT NULL,
  `canvas_course_id` int(10) UNSIGNED NOT NULL,
  `canvas_section_id` int(10) UNSIGNED DEFAULT NULL,
  `role` tinyint(3) UNSIGNED NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL,
  `grade` float DEFAULT NULL,
  `grade_updated` datetime DEFAULT NULL,
  `zero_count` int(11) DEFAULT NULL,
  `zero_count_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`canvas_enrollment_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_canvas_error` (
  `error_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `error_message` varchar(255) NOT NULL,
  `time` datetime NOT NULL,
  `command` varchar(255) NOT NULL,
  `post_fields` text NOT NULL,
  `full_response` text NOT NULL,
  `flag` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`error_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_canvas_section` (
  `canvas_section_id` int(10) UNSIGNED NOT NULL,
  `canvas_course_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`canvas_course_id`,`name`),
  UNIQUE KEY `canvas_section_id` (`canvas_section_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_canvas_term` (
  `canvas_term_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(255) NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  UNIQUE KEY `canvas_term_id` (`canvas_term_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_canvas_user` (
  `canvas_user_id` int(10) UNSIGNED NOT NULL,
  `mth_person_id` int(10) UNSIGNED DEFAULT NULL,
  `email` varchar(255) DEFAULT NULL,
  `to_be_pushed` tinyint(3) UNSIGNED NOT NULL,
  `last_pushed` datetime DEFAULT NULL,
  `canvas_login_id` int(10) UNSIGNED DEFAULT NULL,
  PRIMARY KEY (`canvas_user_id`),
  UNIQUE KEY `mth_person_id` (`mth_person_id`),
  KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_cas_ticket` (
  `ticket_str` varchar(64) NOT NULL,
  `user_id` int(10) UNSIGNED NOT NULL,
  `service_url` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  PRIMARY KEY (`ticket_str`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_city_zip` (
  `zip` varchar(20) NOT NULL,
  `city` varchar(120) NOT NULL,
  PRIMARY KEY (`zip`,`city`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_course` (
  `course_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `subject_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `allow_other_mth` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `allow_custom` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `allow_tp` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `min_grade_level` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `max_grade_level` tinyint(3) UNSIGNED NOT NULL DEFAULT '12',
  `diploma_valid` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `available` tinyint(4) NOT NULL DEFAULT '1',
  `allow_2nd_sem_change` varchar(28) NOT NULL DEFAULT '',
  PRIMARY KEY (`course_id`),
  KEY `subject_id` (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_file` (
  `file_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(60) NOT NULL,
  `type` varchar(60) NOT NULL,
  `item1` varchar(60) NOT NULL,
  `item2` varchar(60) NOT NULL,
  `item3` varchar(250) NOT NULL,
  `year` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`file_id`),
  KEY `item1` (`item1`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_log` (
  `log_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id` int(10) UNSIGNED NOT NULL,
  `field` varchar(255) NOT NULL,
  `new_value` text NOT NULL,
  `old_value` text NOT NULL,
  `field_id` int(10) UNSIGNED NOT NULL,
  `changed_by_user_id` int(10) UNSIGNED NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notified` tinyint(3) UNSIGNED NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `person_id` (`person_id`),
  KEY `field` (`field`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_packet` (
  `packet_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int(10) UNSIGNED NOT NULL,
  `status` varchar(20) NOT NULL,
  `deadline` date DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `date_accepted` datetime DEFAULT NULL,
  `school_district` varchar(255) DEFAULT NULL,
  `special_ed` varchar(120) DEFAULT NULL,
  `understands_special_ed` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `special_ed_desc` text,
  `last_school` varchar(120) DEFAULT NULL,
  `last_school_address` varchar(255) DEFAULT NULL,
  `last_school_type` tinyint(3) UNSIGNED DEFAULT NULL,
  `permission_to_request_records` tinyint(3) UNSIGNED DEFAULT NULL,
  `hispanic` tinyint(3) UNSIGNED DEFAULT NULL,
  `race` varchar(120) DEFAULT NULL,
  `language` varchar(120) DEFAULT NULL,
  `language_home` varchar(120) DEFAULT NULL,
  `secondary_contact_first` varchar(60) DEFAULT NULL,
  `secondary_contact_last` varchar(60) DEFAULT NULL,
  `secondary_phone` varchar(15) DEFAULT NULL,
  `secondary_email` varchar(120) DEFAULT NULL,
  `birth_place` varchar(120) DEFAULT NULL,
  `birth_country` varchar(50) DEFAULT NULL,
  `worked_in_agriculture` tinyint(4) DEFAULT NULL,
  `military` tinyint(4) DEFAULT NULL,
  `household_size` tinyint(3) UNSIGNED DEFAULT NULL,
  `household_income` int(10) UNSIGNED DEFAULT NULL,
  `agrees_to_policy` tinyint(3) UNSIGNED DEFAULT NULL,
  `approves_enrollment` tinyint(3) UNSIGNED DEFAULT NULL,
  `ferpa_agreement` tinyint(3) UNSIGNED DEFAULT NULL,
  `photo_permission` tinyint(3) UNSIGNED DEFAULT NULL,
  `dir_permission` tinyint(3) UNSIGNED DEFAULT NULL,
  `signature_name` varchar(120) DEFAULT NULL,
  `signature_file_id` int(10) UNSIGNED DEFAULT NULL,
  `reupload_files` text,
  PRIMARY KEY (`packet_id`),
  KEY `student_id` (`student_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_packet_file` (
  `file_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `packet_id` int(10) UNSIGNED NOT NULL,
  `kind` varchar(60) NOT NULL,
  `name` varchar(60) NOT NULL,
  `type` varchar(60) NOT NULL,
  `item1` varchar(60) NOT NULL,
  `item2` varchar(60) NOT NULL,
  `year` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`file_id`),
  KEY `packet_id` (`packet_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_parent` (
  `parent_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`parent_id`),
  KEY `person_id` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_person` (
  `person_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `first_name` varchar(60) DEFAULT NULL,
  `last_name` varchar(60) DEFAULT NULL,
  `middle_name` varchar(60) DEFAULT NULL,
  `preferred_first_name` varchar(60) DEFAULT NULL,
  `preferred_last_name` varchar(60) DEFAULT NULL,
  `gender` varchar(10) DEFAULT NULL,
  `email` varchar(120) DEFAULT NULL,
  `date_of_birth` date DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_person_address` (
  `person_id` int(10) UNSIGNED NOT NULL,
  `address_id` int(10) UNSIGNED NOT NULL,
  UNIQUE KEY `person_id` (`person_id`,`address_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_phone` (
  `phone_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(20) DEFAULT NULL,
  `number` varchar(15) DEFAULT NULL,
  `ext` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`phone_id`),
  KEY `person_id` (`person_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_provider` (
  `provider_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `led_by` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `min_grade_level` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `max_grade_level` tinyint(3) UNSIGNED NOT NULL DEFAULT '12',
  `diploma_valid` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `available` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `allow_2nd_sem_change` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_provider_course` (
  `provider_course_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `provider_id` int(10) UNSIGNED NOT NULL,
  `title` varchar(255) NOT NULL,
  `available` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`provider_course_id`),
  KEY `provider_id` (`provider_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_provider_course_mapping` (
  `provider_course_id` int(10) UNSIGNED NOT NULL,
  `course_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`provider_course_id`,`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_purchasedcourse` (
  `purchasedCourse_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `mth_parent_id` int(10) UNSIGNED NOT NULL,
  `wooCommerce_customer_id` int(10) UNSIGNED NOT NULL,
  `wooCommerce_order_id` int(10) UNSIGNED NOT NULL,
  `wooCommerce_order_line_item_id` int(10) UNSIGNED NOT NULL,
  `quantity_item` tinyint(3) UNSIGNED NOT NULL,
  `date_purchased` datetime DEFAULT NULL,
  `canvas_course_id` int(10) UNSIGNED NOT NULL,
  `mth_school_year_id` int(10) UNSIGNED NOT NULL,
  `mth_student_id` int(10) UNSIGNED DEFAULT NULL,
  `student_canvas_enrollment_id` int(10) UNSIGNED DEFAULT NULL,
  `parent_canvas_enrollment_id` int(10) UNSIGNED DEFAULT NULL,
  `date_registered` datetime DEFAULT NULL,
  PRIMARY KEY (`purchasedCourse_id`),
  UNIQUE KEY `wooCommerce_order_id` (`wooCommerce_order_id`,`wooCommerce_order_line_item_id`,`quantity_item`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_reimbursement` (
  `reimbursement_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `student_id` int(10) UNSIGNED DEFAULT NULL,
  `school_year_id` int(10) UNSIGNED DEFAULT NULL,
  `at_least_80` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `schedule_period_id` int(10) UNSIGNED DEFAULT NULL,
  `type` tinyint(3) UNSIGNED DEFAULT NULL,
  `amount` float DEFAULT NULL,
  `invalid_amount` float DEFAULT NULL,
  `description` text,
  `status` tinyint(3) UNSIGNED DEFAULT NULL,
  `date_submitted` datetime DEFAULT NULL,
  `date_resubmitted` datetime DEFAULT NULL,
  `date_paid` datetime DEFAULT NULL,
  `fields_last_changed` text,
  `require_new_receipt` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`reimbursement_id`),
  KEY `parent_id` (`parent_id`,`student_id`,`school_year_id`,`schedule_period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_reimbursement_reciept` (
  `reimbursement_id` int(10) UNSIGNED NOT NULL,
  `file_id` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`reimbursement_id`,`file_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_schedule` (
  `schedule_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `date_accepted` datetime DEFAULT NULL,
  `last_modified` datetime DEFAULT NULL,
  PRIMARY KEY (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_schedule_period` (
  `schedule_period_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `schedule_id` int(10) UNSIGNED NOT NULL,
  `period` tinyint(3) UNSIGNED NOT NULL,
  `second_semester` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `subject_id` int(10) UNSIGNED DEFAULT NULL,
  `course_id` int(10) UNSIGNED DEFAULT NULL,
  `course_type` tinyint(3) UNSIGNED DEFAULT NULL,
  `mth_provider_id` int(10) UNSIGNED DEFAULT NULL,
  `provider_course_id` int(10) UNSIGNED DEFAULT NULL,
  `tp_name` varchar(255) DEFAULT NULL,
  `tp_course` varchar(255) DEFAULT NULL,
  `tp_phone` varchar(255) DEFAULT NULL,
  `tp_website` varchar(255) DEFAULT NULL,
  `tp_desc` varchar(255) DEFAULT NULL,
  `tp_district` varchar(255) DEFAULT NULL,
  `custom_desc` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `reimbursed` float DEFAULT NULL,
  `require_change` datetime DEFAULT NULL,
  `changed` datetime DEFAULT NULL,
  PRIMARY KEY (`schedule_period_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_schoolyear` (
  `school_year_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `date_begin` date DEFAULT NULL,
  `date_end` date DEFAULT NULL,
  `date_reg_open` date DEFAULT NULL,
  `date_reg_close` date DEFAULT NULL,
  `reimburse_open` date DEFAULT NULL,
  `reimburse_tech_open` date DEFAULT NULL,
  `reimburse_close` date DEFAULT NULL,
  `second_sem_start` date DEFAULT NULL,
  `second_sem_open` date DEFAULT NULL,
  `second_sem_close` date DEFAULT NULL,
  `re_enroll_open` date DEFAULT NULL,
  `re_enroll_deadline` date DEFAULT NULL,
  PRIMARY KEY (`school_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_student` (
  `student_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `person_id` int(10) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED DEFAULT NULL,
  `parent2_id` int(10) UNSIGNED DEFAULT NULL,
  `grade_level` tinyint(3) UNSIGNED DEFAULT NULL,
  `special_ed` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `diploma_seeking` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `hidden` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `school_of_enrollment` varchar(120) DEFAULT NULL COMMENT 'No longer used',
  PRIMARY KEY (`student_id`),
  KEY `person_id` (`person_id`),
  KEY `school_of_enrollment` (`school_of_enrollment`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_student_grade_level` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `grade_level` varchar(3) NOT NULL,
  PRIMARY KEY (`student_id`,`school_year_id`),
  KEY `grade_level` (`grade_level`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_student_school` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `school_of_enrollment` tinyint(3) UNSIGNED NOT NULL,
  PRIMARY KEY (`student_id`,`school_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_student_section` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `period_num` tinyint(3) UNSIGNED NOT NULL,
  `schoolYear_id` int(10) UNSIGNED NOT NULL,
  `name` varchar(120) NOT NULL,
  PRIMARY KEY (`student_id`,`period_num`,`schoolYear_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_student_status` (
  `student_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL,
  `date_updated` datetime DEFAULT NULL,
  PRIMARY KEY (`student_id`,`school_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_subject` (
  `subject_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `desc` varchar(255) DEFAULT NULL,
  `show_providers` tinyint(3) UNSIGNED NOT NULL DEFAULT '1',
  `require_tp_desc` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  `allow_2nd_sem_change` varchar(28) NOT NULL DEFAULT '',
  PRIMARY KEY (`subject_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_subject_period` (
  `subject_id` int(10) UNSIGNED NOT NULL,
  `period` tinyint(3) UNSIGNED NOT NULL,
  PRIMARY KEY (`subject_id`,`period`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_testoptout` (
  `testOptOut_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `in_attendance` tinyint(3) UNSIGNED NOT NULL,
  `parent_id` int(10) UNSIGNED NOT NULL,
  `sig_file_id` int(10) UNSIGNED NOT NULL,
  `school_year_id` int(10) UNSIGNED NOT NULL,
  `date_submitted` datetime NOT NULL,
  PRIMARY KEY (`testOptOut_id`),
  KEY `parent_id` (`parent_id`),
  KEY `school_year_id` (`school_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_testoptout_student` (
  `testOptOut_id` int(10) UNSIGNED NOT NULL,
  `student_id` int(10) UNSIGNED NOT NULL,
  `sent_to_dropbox` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`testOptOut_id`,`student_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE IF NOT EXISTS `mth_withdrawal` (
  `withdrawal_id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `student_id` int(10) UNSIGNED DEFAULT NULL,
  `school_year_id` int(10) UNSIGNED DEFAULT NULL,
  `reason` tinyint(3) UNSIGNED DEFAULT NULL,
  `new_school_name` varchar(255) DEFAULT NULL,
  `new_school_address` varchar(255) DEFAULT NULL,
  `sig_file_id` int(10) UNSIGNED DEFAULT NULL,
  `datetime` datetime DEFAULT NULL,
  `status` tinyint(3) UNSIGNED NOT NULL DEFAULT '0',
  PRIMARY KEY (`withdrawal_id`),
  KEY `student_id` (`student_id`),
  KEY `school_year_id` (`school_year_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;