CREATE TABLE IF NOT EXISTS `core_log` (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `datetime` datetime DEFAULT NULL,
  `tag` varchar(255) DEFAULT NULL,
  `content` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;