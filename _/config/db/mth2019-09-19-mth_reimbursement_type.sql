CREATE TABLE `mth_reimbursement_type` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`placeholder` INT NOT NULL DEFAULT '0',
	`label` VARCHAR(50) NOT NULL DEFAULT '0',
	`is_enable` TINYINT NOT NULL DEFAULT '0',
	`date_created` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`date_updated` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`)
);


INSERT INTO mth_reimbursement_type
(placeholder, label, is_enable, date_created, date_updated)
VALUES(1, 'Custom-built', 1, '2019-09-19 07:23:17.000', NULL);

INSERT INTO mth_reimbursement_type
(placeholder, label, is_enable, date_created, date_updated)
VALUES(2, '3rd Party Provider', 1, '2019-09-19 07:23:39.000', NULL);

INSERT INTO mth_reimbursement_type
(placeholder, label, is_enable, date_created, date_updated)
VALUES(5, 'Required Software and Materials', 1, '2019-09-19 07:23:53.000', '2019-09-19 07:25:51.000');
INSERT INTO mth_reimbursement_type
(placeholder, label, is_enable, date_created, date_updated)
VALUES(4, 'Technology Allowance', 1, '2019-09-19 07:24:04.000', '2019-09-19 07:24:17.000');
