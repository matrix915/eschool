ALTER TABLE `mth_packet_file`
  CHANGE `name` `name` VARCHAR(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  CHANGE `type` `type` VARCHAR(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  CHANGE `item1` `item1` VARCHAR(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  CHANGE `item2` `item2` VARCHAR(60) CHARACTER SET latin1 COLLATE latin1_swedish_ci NULL DEFAULT NULL,
  CHANGE `year` `year` INT(10) UNSIGNED NULL DEFAULT NULL,
  ADD `mth_file_id` INT UNSIGNED NULL DEFAULT NULL AFTER `packet_id`,
  ADD INDEX (`mth_file_id`);