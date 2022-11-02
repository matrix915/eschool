ALTER TABLE `mth_packet`
  ADD `language_home_child` VARCHAR(120) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL AFTER `language_home`,
  ADD `language_friends` VARCHAR(120) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL AFTER `language_home_child`,
  ADD `language_home_preferred` VARCHAR(120) CHARACTER SET utf8 COLLATE utf8_bin NULL DEFAULT NULL AFTER `language_friends`,
  ADD `work_move` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `language_home_preferred`,
  ADD `living_location` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `work_move`,
  ADD `lives_with` TINYINT UNSIGNED NULL DEFAULT NULL AFTER `living_location`;