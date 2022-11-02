ALTER TABLE `mth_reimbursement`
ADD `product_name` varchar(255) DEFAULT NULL AFTER `description`,
ADD `product_sn` varchar(255) DEFAULT NULL AFTER `product_name`,
ADD `product_amount` DECIMAL(7,2) NULL DEFAULT NULL AFTER `product_sn`;