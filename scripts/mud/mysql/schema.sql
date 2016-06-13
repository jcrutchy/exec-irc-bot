DROP DATABASE IF EXISTS exec_mud;
CREATE DATABASE IF NOT EXISTS exec_mud DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci;

CREATE TABLE `exec_mud`.`mud_players` (
  `hostname` varchar(100) NOT NULL,
  `x_coord` integer unsigned NOT NULL,
  `y_coord` integer unsigned NOT NULL,
  `deaths` integer unsigned NOT NULL,
  `kills` integer unsigned NOT NULL,
  PRIMARY KEY (`hostname`)
) ENGINE=InnoDB AUTO_INCREMENT=1;
