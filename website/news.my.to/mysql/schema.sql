DROP DATABASE IF EXISTS news_my_to;
CREATE DATABASE IF NOT EXISTS news_my_to DEFAULT CHARACTER SET utf8 DEFAULT COLLATE utf8_general_ci;

DROP TABLE IF EXISTS `news_my_to`.`stories`;
CREATE TABLE  `news_my_to`.`stories` (
  `sid` integer unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` varchar(65535) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=1;

DROP TABLE IF EXISTS `news_my_to`.`comments`;
CREATE TABLE  `news_my_to`.`comments` (
  `cid` integer unsigned NOT NULL AUTO_INCREMENT,
  `nick` varchar(255) NOT NULL,
  `sid` integer unsigned NOT NULL,
  `parent_cid` integer unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` varchar(65535) NOT NULL,
  `auth_hash` varchar(65535) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`),
  KEY `nick` (`nick`),
  KEY `sid` (`sid`),
  KEY `parent_cid` (`parent_cid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB AUTO_INCREMENT=1;

DROP TABLE IF EXISTS `news_my_to`.`nicks`;
CREATE TABLE  `news_my_to`.`nicks` (
  `nick` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `auth_hash` varchar(65535) NOT NULL,
  PRIMARY KEY (`nick`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `news_my_to`.`story_mods`;
CREATE TABLE  `news_my_to`.`story_mods` (
  `sid` integer unsigned NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `mod` tinyint NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`,`ip_address`),
  KEY `mod` (`mod`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB;

DROP TABLE IF EXISTS `news_my_to`.`comment_mods`;
CREATE TABLE  `news_my_to`.`comment_mods` (
  `cid` integer unsigned NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `mod` tinyint NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`,`ip_address`),
  KEY `mod` (`mod`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB;

