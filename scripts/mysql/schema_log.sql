DROP DATABASE IF EXISTS exec_irc_bot;
CREATE DATABASE IF NOT EXISTS exec_irc_bot DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `exec_irc_bot`.`irc_log` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `server` varchar(255) NOT NULL,
  `microtime` varchar(255) NOT NULL,
  `time` varchar(255) NOT NULL,
  `data` varchar(2000) NOT NULL,
  `prefix` varchar(255) NOT NULL,
  `params` varchar(255) NOT NULL,
  `trailing` varchar(500) NOT NULL,
  `nick` varchar(255) NOT NULL,
  `user` varchar(255) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `destination` varchar(255) NOT NULL,
  `cmd` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `timestamp` (`timestamp` ASC),
  INDEX `microtime` (`microtime` ASC),
  INDEX `time` (`time` ASC),
  INDEX `server` (`server` ASC),
  INDEX `params` (`params` ASC),
  INDEX `trailing` (`trailing` ASC),
  INDEX `nick` (`nick` ASC),
  INDEX `cmd` (`cmd` ASC),
  INDEX `hostname` (`hostname` ASC),
  INDEX `destination` (`destination` ASC)
) ENGINE=InnoDB AUTO_INCREMENT=1;
