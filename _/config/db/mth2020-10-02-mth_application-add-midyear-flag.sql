ALTER TABLE `mth_application` ADD COLUMN `midyear_application` TINYINT NULL DEFAULT NULL;

UPDATE mth_application
SET midyear_application = 1
WHERE dayofyear(date_accepted) < 16 OR dayofyear(date_accepted) > 304;