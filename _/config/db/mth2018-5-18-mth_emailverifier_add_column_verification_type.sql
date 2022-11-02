ALTER TABLE `mth_emailverifier`
	ADD COLUMN `verification_type` TINYINT NOT NULL AFTER `verified`;
