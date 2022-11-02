ALTER TABLE `mth_reimbursement`
	ADD COLUMN `confirm_receipt` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `last_status`,
	ADD COLUMN `confirm_related` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `confirm_receipt`,
	ADD COLUMN `confirm_dated` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `confirm_related`,
	ADD COLUMN `confirm_provided` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `confirm_dated`,
	ADD COLUMN `confirm_allocation` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `confirm_provided`,
	ADD COLUMN `confirm_update` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0 AFTER `confirm_allocation`;