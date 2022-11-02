ALTER TABLE `mth_reimbursement` ADD `last_modified` DATETIME NULL DEFAULT NULL AFTER `require_new_receipt`;
UPDATE `mth_reimbursement` SET `last_modified`='2014-01-01' WHERE 1;