CREATE TABLE `exec_irc_bot`.`bank` (
  `itemid` integer unsigned NOT NULL AUTO_INCREMENT,
  `description` varchar(100) NOT NULL,
  `uid` varchar(100) NOT NULL,
  `count` integer unsigned NOT NULL,
  PRIMARY KEY (`itemid`),
  INDEX `uid` (`uid` ASC)
) ENGINE=InnoDB AUTO_INCREMENT=1;
