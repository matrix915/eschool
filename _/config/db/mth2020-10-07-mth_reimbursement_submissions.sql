CREATE TABLE `mth_reimbursement_submission` (
	`submission_id` INT NOT NULL AUTO_INCREMENT,
	`reimbursement_id` INT NOT NULL DEFAULT '0',
	`date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	PRIMARY KEY (`submission_id`)
);
