CREATE TABLE `sessions` (
  `id` varchar(32) NOT NULL,
  `data` mediumtext NOT NULL,
  `accessed` int unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `accessed` (`accessed`)
) ENGINE=InnoDB;