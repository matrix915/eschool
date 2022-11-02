ALTER TABLE `mth_provider`
    ADD COLUMN `requires_multiple_periods` TINYINT(3) UNSIGNED NOT NULL DEFAULT 0,
    ADD COLUMN `multiple_periods` VARCHAR(50) NOT NULL DEFAULT '';

ALTER TABLE `mth_schedule_period`
    ADD COLUMN `provisional_provider_id` INT(10) UNSIGNED DEFAULT NULL;