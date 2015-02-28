DROP DATABASE IF EXISTS exec_log;
CREATE DATABASE IF NOT EXISTS exec_log DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `exec_log`.`irc.sylnt.us` (
  `id` integer unsigned NOT NULL AUTO_INCREMENT,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `data` varchar(1000) NOT NULL,
  `prefix` varchar(255) NOT NULL,
  `params` varchar(255) NOT NULL,
  `trailing` varchar(500) NOT NULL,
  `user` varchar(255) NOT NULL,
  `nick` varchar(100) NOT NULL,
  `hostname` varchar(255) NOT NULL,
  `destination` varchar(100) NOT NULL,
  `cmd` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  INDEX `timestamp` (`timestamp` ASC),
  INDEX `params` (`params` ASC),
  INDEX `trailing` (`trailing` ASC),
  INDEX `nick` (`nick` ASC),
  INDEX `cmd` (`cmd` ASC),
  INDEX `hostname` (`hostname` ASC),
  INDEX `destination` (`destination` ASC)
) ENGINE=InnoDB AUTO_INCREMENT=1;
