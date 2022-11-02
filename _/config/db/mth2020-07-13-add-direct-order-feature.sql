ALTER TABLE `mth_schoolYear`
    ADD COLUMN `direct_order_open` DATE NULL DEFAULT NULL AFTER `reimburse_close`;
ALTER TABLE `mth_schoolYear`
    ADD COLUMN `direct_order_close` DATE NULL DEFAULT NULL AFTER `direct_order_open`;
ALTER TABLE `mth_schoolYear`
    ADD COLUMN `direct_order_tech_open` DATE NULL DEFAULT NULL AFTER `direct_order_open`;

ALTER TABLE `mth_reimbursement`
    ADD COLUMN `is_direct_order` TINYINT(1) NULL DEFAULT 0;
ALTER TABLE `mth_reimbursement`
    ADD COLUMN `direct_order_list_link` TEXT NULL DEFAULT NULL;
ALTER TABLE `mth_reimbursement`
    ADD COLUMN `direct_order_list_provider` VARCHAR(255) NULL DEFAULT NULL;
ALTER TABLE `mth_reimbursement`
    ADD COLUMN `signature_name` VARCHAR(120) NULL DEFAULT NULL;

ALTER TABLE `mth_reimbursement_type`
    ADD COLUMN `enabled_for_direct_order` TINYINT(1) NULL DEFAULT 0;

INSERT INTO `core_settings` (`name`, `category`, `title`, `type`, `value`, `description`, `user_changeable`, `date_changed`) VALUES ('directOrderApprovalEmailContent', 'DirectOrders', 'Approval Email Content', 'HTML', '<p>Hi [PARENT_FIRST] [PARENT_LAST]<strong>,</strong></p><p><strong>Your Request for a Direct Order has been approved:</strong></p><p><strong>Submitted: </strong>[DIRECT_ORDER_SUBMITTED_DATE]</p><p><strong>Amount: </strong>$[DIRECT_ORDER_AMOUNT]</p><p><strong>Student: </strong>[STUDENT_FIRST] [STUDENT_LAST]</p><p><strong>Class: </strong>[CLASS_PERIOD_DESCRIPTION]</p><p><strong>Schedule 1-1 Zoom Meeting here: <a href="http://comingsoon" target="_blank">LINK TO CALENDLY</a></strong></p><p><strong>My Tech High</strong></p>', '<p>Direct Orders Approval Email Content</p>', 1, '2020-08-08 14:58:55');
INSERT INTO `core_settings` (`name`, `category`, `title`, `type`, `value`, `description`, `user_changeable`, `date_changed`) VALUES ('directOrderApprovedEmailSubject', 'DirectOrders', 'Approval Email Subject', 'Text', 'Direct Order Approved Action Required', '<p>Email subject for Direct Order Approved email</p>', 1, '2020-08-08 14:42:40');
