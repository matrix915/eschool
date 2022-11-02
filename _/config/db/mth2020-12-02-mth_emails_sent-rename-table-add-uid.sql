ALTER TABLE `mth_email_sent`
  RENAME TO `mth_email_logs`;

ALTER TABLE `mth_email_logs` 
    ADD COLUMN `email_batch_id` VARCHAR(36) FIRST,
    ADD COLUMN `email_address` VARCHAR(30) NULL AFTER `type`,
    ADD COLUMN `error_message` LONGTEXT NULL AFTER `email_address`,
    ADD PRIMARY KEY ( `email_batch_id`, `student_id` );