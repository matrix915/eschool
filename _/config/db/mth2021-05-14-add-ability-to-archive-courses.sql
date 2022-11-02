ALTER TABLE `mth_course`
    ADD COLUMN `alternative_min_grade_level` TINYINT(3) UNSIGNED NULL DEFAULT NULL AFTER `min_grade_level`;
ALTER TABLE `mth_course`
    ADD COLUMN `alternative_max_grade_level` TINYINT(3) UNSIGNED NULL DEFAULT NULL AFTER `max_grade_level`;
UPDATE mth_course SET alternative_min_grade_level = min_grade_level WHERE alternative_min_grade_level IS NULL;
UPDATE mth_course SET alternative_max_grade_level = max_grade_level WHERE alternative_max_grade_level IS NULL;

ALTER TABLE `mth_provider`
    ADD COLUMN `archived` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `mth_provider_course`
    ADD COLUMN `archived` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';
ALTER TABLE `mth_course`
    ADD COLUMN `archived` TINYINT(1) UNSIGNED NOT NULL DEFAULT '0';

ALTER TABLE `mth_provider`
    ADD COLUMN `alternative_min_grade_level` TINYINT(3) UNSIGNED NULL DEFAULT NULL AFTER `min_grade_level`;
ALTER TABLE `mth_provider`
    ADD COLUMN `alternative_max_grade_level` TINYINT(3) UNSIGNED NULL DEFAULT NULL AFTER `max_grade_level`;
UPDATE mth_provider SET alternative_min_grade_level = min_grade_level WHERE alternative_min_grade_level IS NULL;
UPDATE mth_provider SET alternative_max_grade_level = max_grade_level WHERE alternative_max_grade_level IS NULL;

ALTER TABLE `mth_schedule_period`
    ADD COLUMN `allow_above_max_grade_level` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `changed`;
ALTER TABLE `mth_schedule_period`
    ADD COLUMN `allow_below_min_grade_level` TINYINT(1) UNSIGNED NULL DEFAULT NULL AFTER `changed`;