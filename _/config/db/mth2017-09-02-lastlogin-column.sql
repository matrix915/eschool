ALTER TABLE `mth_canvas_user`
	ADD COLUMN `last_login` TIMESTAMP NULL DEFAULT NULL AFTER `canvas_login_id`;