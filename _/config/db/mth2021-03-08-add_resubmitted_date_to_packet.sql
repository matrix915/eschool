ALTER TABLE `mth_packet`
    ADD COLUMN `date_last_submitted` datetime DEFAULT NULL AFTER `date_submitted`;