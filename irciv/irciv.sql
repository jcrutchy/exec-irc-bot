-- MySQL Administrator dump 1.4
--
-- ------------------------------------------------------
-- Server version	5.1.73-1-log


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;


--
-- Create schema IRCiv
--

CREATE DATABASE IF NOT EXISTS IRCiv;
USE IRCiv;

--
-- Definition of table `IRCiv`.`cities`
--

DROP TABLE IF EXISTS `IRCiv`.`cities`;
CREATE TABLE  `IRCiv`.`cities` (
  `city_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
  `size` int(11) NOT NULL,
  `tile_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`city_id`),
  UNIQUE KEY `tile_id` (`tile_id`) USING BTREE,
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;

--
-- Dumping data for table `IRCiv`.`cities`
--

/*!40000 ALTER TABLE `cities` DISABLE KEYS */;
LOCK TABLES `cities` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `cities` ENABLE KEYS */;


--
-- Definition of table `IRCiv`.`games`
--

DROP TABLE IF EXISTS `IRCiv`.`games`;
CREATE TABLE  `IRCiv`.`games` (
  `game_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  PRIMARY KEY (`game_id`),
  UNIQUE KEY `name` (`name`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=ascii;

--
-- Dumping data for table `IRCiv`.`games`
--

/*!40000 ALTER TABLE `games` DISABLE KEYS */;
LOCK TABLES `games` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `games` ENABLE KEYS */;


--
-- Definition of table `IRCiv`.`players`
--

DROP TABLE IF EXISTS `IRCiv`.`players`;
CREATE TABLE  `IRCiv`.`players` (
  `player_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `password` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `start` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nick` varchar(50) NOT NULL,
  PRIMARY KEY (`player_id`),
  UNIQUE KEY `game_nick` (`nick`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=ascii;

--
-- Dumping data for table `IRCiv`.`players`
--

/*!40000 ALTER TABLE `players` DISABLE KEYS */;
LOCK TABLES `players` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `players` ENABLE KEYS */;


--
-- Definition of table `IRCiv`.`resources`
--

DROP TABLE IF EXISTS `IRCiv`.`resources`;
CREATE TABLE  `IRCiv`.`resources` (
  `resource_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tile_id` int(10) unsigned NOT NULL,
  `resource_type` int(11) NOT NULL,
  `quantity` int(11) NOT NULL,
  PRIMARY KEY (`resource_id`),
  UNIQUE KEY `tile_type` (`tile_id`,`resource_type`) USING BTREE,
  KEY `tile_id` (`tile_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;

--
-- Dumping data for table `IRCiv`.`resources`
--

/*!40000 ALTER TABLE `resources` DISABLE KEYS */;
LOCK TABLES `resources` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `resources` ENABLE KEYS */;


--
-- Definition of table `IRCiv`.`tiles`
--

DROP TABLE IF EXISTS `IRCiv`.`tiles`;
CREATE TABLE  `IRCiv`.`tiles` (
  `tile_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tile_type` int(11) NOT NULL,
  `game_id` int(10) unsigned NOT NULL,
  `coord_x` int(10) unsigned NOT NULL,
  `coord_y` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tile_id`),
  UNIQUE KEY `game_coord` (`game_id`,`coord_x`,`coord_y`) USING BTREE,
  KEY `game_id` (`game_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;

--
-- Dumping data for table `IRCiv`.`tiles`
--

/*!40000 ALTER TABLE `tiles` DISABLE KEYS */;
LOCK TABLES `tiles` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `tiles` ENABLE KEYS */;


--
-- Definition of table `IRCiv`.`units`
--

DROP TABLE IF EXISTS `IRCiv`.`units`;
CREATE TABLE  `IRCiv`.`units` (
  `unit_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tile_id` int(10) unsigned NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
  `unit_type` int(11) NOT NULL,
  `health` int(11) NOT NULL,
  `experience` int(11) NOT NULL,
  PRIMARY KEY (`unit_id`),
  KEY `tile_id` (`tile_id`),
  KEY `player_id` (`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;

--
-- Dumping data for table `IRCiv`.`units`
--

/*!40000 ALTER TABLE `units` DISABLE KEYS */;
LOCK TABLES `units` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `units` ENABLE KEYS */;


--
-- Definition of table `IRCiv`.`visibility`
--

DROP TABLE IF EXISTS `IRCiv`.`visibility`;
CREATE TABLE  `IRCiv`.`visibility` (
  `tile_id` int(10) unsigned NOT NULL,
  `player_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`tile_id`,`player_id`)
) ENGINE=InnoDB DEFAULT CHARSET=ascii;

--
-- Dumping data for table `IRCiv`.`visibility`
--

/*!40000 ALTER TABLE `visibility` DISABLE KEYS */;
LOCK TABLES `visibility` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `visibility` ENABLE KEYS */;




/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
