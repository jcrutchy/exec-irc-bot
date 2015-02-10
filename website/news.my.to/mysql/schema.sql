CREATE DATABASE IF NOT EXISTS news_my_to;

DROP TABLE IF EXISTS `news_my_to`.`stories`;
CREATE TABLE  `news_my_to`.`stories` (
  `sid` integer unsigned NOT NULL AUTO_INCREMENT,
  `title` tinytext NOT NULL,
  `content` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=ascii;

DROP TABLE IF EXISTS `news_my_to`.`comments`;
CREATE TABLE  `news_my_to`.`comments` (
  `cid` integer unsigned NOT NULL AUTO_INCREMENT,
  `nick` tinytext NOT NULL,
  `sid` integer unsigned NOT NULL,
  `parent_cid` integer unsigned NOT NULL,
  `subject` tinytext NOT NULL,
  `content` text NOT NULL,
  `auth_hash` text NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`),
  KEY `nick` (`nick`),
  KEY `sid` (`sid`),
  KEY `parent_cid` (`parent_cid`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=ascii;

DROP TABLE IF EXISTS `news_my_to`.`nicks`;
CREATE TABLE  `news_my_to`.`nicks` (
  `nick` tinytext NOT NULL,
  `email` tinytext NOT NULL,
  `ip_address` tinytext NOT NULL,
  `auth_hash` text NOT NULL,
  PRIMARY KEY (`nick`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii;

DROP TABLE IF EXISTS `news_my_to`.`story_scores`;
CREATE TABLE  `news_my_to`.`story_scores` (
  `sid` integer unsigned NOT NULL,
  `ip_address` tinytext NOT NULL,
  `mod` tinyint(1) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`,`ip_address`),
  KEY `mod` (`mod`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii;

DROP TABLE IF EXISTS `news_my_to`.`comment_scores`;
CREATE TABLE  `news_my_to`.`comment_scores` (
  `cid` integer unsigned NOT NULL,
  `ip_address` tinytext NOT NULL,
  `mod` tinyint(1) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`,`ip_address`),
  KEY `mod` (`mod`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=ascii;
