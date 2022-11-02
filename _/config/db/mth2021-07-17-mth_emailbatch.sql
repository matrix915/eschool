CREATE TABLE IF NOT EXISTS `mth_emailbatch` (
  `batch_id` int(10) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) NULL,
  `title` varchar(120) NULL,
  `category` varchar(60) NULL,
  `template` LONGTEXT,
  `school_year_id` INT(10) NULL,
  `sent_by_id` INT(10) NULL,
  `batch_date` datetime DEFAULT NOW(),
  PRIMARY KEY (`batch_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;