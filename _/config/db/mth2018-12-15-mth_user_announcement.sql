CREATE TABLE `mth_user_announcement` (
	`id` INT NOT NULL AUTO_INCREMENT,
	`announcement_id` INT NOT NULL DEFAULT '0',
	`user_id` INT NOT NULL DEFAULT '0',
	`status` INT NOT NULL DEFAULT '0',
	`created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
	PRIMARY KEY (`id`),
	INDEX `announcement_id` (`announcement_id`),
	INDEX `user_id` (`user_id`)
)
COLLATE='utf8_general_ci';
