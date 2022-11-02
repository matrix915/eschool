ALTER TABLE `mth_schoolyear`
    ADD COLUMN `direct_order_tech_enabled` TINYINT DEFAULT 0 AFTER `direct_order_open`;