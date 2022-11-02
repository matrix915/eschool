SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT = @@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS = @@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION = @@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;


CREATE TABLE IF NOT EXISTS `content` (
  `content_id` INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `path`       VARCHAR(256)        NOT NULL DEFAULT '',
  `type`       VARCHAR(50)         NOT NULL DEFAULT 'HTML',
  `location`   VARCHAR(50)         NOT NULL DEFAULT 'Primary',
  `content`    TEXT,
  `time`       TIMESTAMP           NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `published`  TINYINT(4)          NOT NULL DEFAULT '1',
  `priority`   TINYINT(3) UNSIGNED NOT NULL DEFAULT '50',
  PRIMARY KEY (`content_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `nav` (
  `nav`                VARCHAR(60)      NOT NULL,
  `nav_item_id`        INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `order`              INT(11)          NOT NULL DEFAULT '0',
  `path`               VARCHAR(256)              DEFAULT NULL,
  `title`              VARCHAR(60)               DEFAULT NULL,
  `parent_nav_item_id` INT(10) UNSIGNED          DEFAULT NULL,
  PRIMARY KEY (`nav_item_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `settings` (
  `name`            VARCHAR(60) NOT NULL,
  `category`        VARCHAR(60) NOT NULL DEFAULT '',
  `title`           VARCHAR(120)         DEFAULT NULL,
  `type`            VARCHAR(50) NOT NULL,
  `value`           MEDIUMTEXT  NOT NULL,
  `description`     TEXT,
  `user_changeable` TINYINT(4)  NOT NULL DEFAULT '0',
  PRIMARY KEY (`name`, `category`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

CREATE TABLE IF NOT EXISTS `users` (
  `user_id`    INT(10) UNSIGNED    NOT NULL AUTO_INCREMENT,
  `email`      VARCHAR(120)        NOT NULL,
  `first_name` VARCHAR(60)                  DEFAULT NULL,
  `last_name`  VARCHAR(60)                  DEFAULT NULL,
  `password`   VARCHAR(120)        NOT NULL,
  `level`      TINYINT(3) UNSIGNED NOT NULL DEFAULT '1',
  PRIMARY KEY (`user_id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = latin1;

/*!40101 SET CHARACTER_SET_CLIENT = @OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS = @OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION = @OLD_COLLATION_CONNECTION */;
