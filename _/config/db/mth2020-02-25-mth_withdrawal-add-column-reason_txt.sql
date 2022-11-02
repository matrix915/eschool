ALTER TABLE `mth_withdrawal` ADD COLUMN `reason_txt` TEXT NULL DEFAULT NULL;
ALTER TABLE `mth_withdrawal` ADD COLUMN `intent_reenroll_action` DATETIME NULL DEFAULT NULL;