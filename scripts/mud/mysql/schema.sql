DROP DATABASE IF EXISTS exec_mud;
CREATE DATABASE IF NOT EXISTS exec_mud DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `exec_mud`.`players` (
  `player_id` integer unsigned NOT NULL AUTO_INCREMENT,
  `hostname` varchar(255) NOT NULL,
  `nick` varchar(255) NOT NULL,
  `create_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` integer unsigned NOT NULL,
  `game_id` integer unsigned NOT NULL,
  PRIMARY KEY (`player_id`),
  INDEX `create_timestamp` (`create_timestamp` ASC),
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE `exec_mud`.`maps` (
  `map_id` integer unsigned NOT NULL AUTO_INCREMENT,
  `map_name` varchar(255) NOT NULL,
  `create_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `width` integer unsigned NOT NULL,
  `height` integer unsigned NOT NULL,
  `coords` mediumtext NOT NULL,
  PRIMARY KEY (`map_id`),
  INDEX `create_timestamp` (`create_timestamp` ASC),
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE `exec_mud`.`moves` (
  `move_id` integer unsigned NOT NULL AUTO_INCREMENT,
  `create_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `player_id` integer unsigned NOT NULL,
  `game_id` integer unsigned NOT NULL,
  `coord` integer unsigned NOT NULL,
  `previous_move_id` integer unsigned NOT NULL,
  PRIMARY KEY (`move_id`),
  INDEX `create_timestamp` (`create_timestamp` ASC),
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE `exec_mud`.`games` (
  `game_id` integer unsigned NOT NULL AUTO_INCREMENT,
  `game_name` varchar(255) NOT NULL,
  `create_timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `map_id` integer unsigned NOT NULL,
  `status` integer unsigned NOT NULL,
  PRIMARY KEY (`game_id`),
  INDEX `create_timestamp` (`create_timestamp` ASC),
) ENGINE=InnoDB AUTO_INCREMENT=1;






CREATE TABLE `exec_mud`.`admins` (
  `sid` integer unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `content` varchar(65535) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`),
  INDEX `timestamp` (`timestamp` ASC)
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE `news_my_to`.`comments` (
  `cid` integer unsigned NOT NULL AUTO_INCREMENT,
  `nick` varchar(255) NOT NULL,
  `sid` integer unsigned NOT NULL,
  `parent_cid` integer unsigned NOT NULL,
  `subject` varchar(255) NOT NULL,
  `content` varchar(65535) NOT NULL,
  `auth_hash` varchar(65535) NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`),
  INDEX `nick` (`nick` ASC),
  INDEX `sid` (`sid` ASC),
  INDEX `parent_cid` (`parent_cid` ASC),
  INDEX `timestamp` (`timestamp` ASC)
) ENGINE=InnoDB AUTO_INCREMENT=1;

CREATE TABLE `news_my_to`.`nicks` (
  `nick` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `auth_hash` varchar(65535) NOT NULL,
  PRIMARY KEY (`nick`)
) ENGINE=InnoDB;

CREATE TABLE `news_my_to`.`story_mods` (
  `sid` integer unsigned NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `mod` tinyint NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`sid`,`ip_address`),
  INDEX `mod` (`mod` ASC),
  INDEX `timestamp` (`timestamp` ASC)
) ENGINE=InnoDB;

CREATE TABLE `news_my_to`.`comment_mods` (
  `cid` integer unsigned NOT NULL,
  `ip_address` varchar(15) NOT NULL,
  `mod` tinyint NOT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`,`ip_address`),
  INDEX `mod` (`mod` ASC),
  INDEX `timestamp` (`timestamp` ASC)
) ENGINE=InnoDB;
