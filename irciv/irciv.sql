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
-- Definition of table `IRCiv`.`events`
--

DROP TABLE IF EXISTS `IRCiv`.`events`;
CREATE TABLE  `IRCiv`.`events` (
  `event_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `nick` varchar(50) NOT NULL,
  `state` longtext NOT NULL,
  PRIMARY KEY (`event_id`),
  KEY `nick` (`nick`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

--
-- Dumping data for table `IRCiv`.`events`
--

/*!40000 ALTER TABLE `events` DISABLE KEYS */;
LOCK TABLES `events` WRITE;
UNLOCK TABLES;
/*!40000 ALTER TABLE `events` ENABLE KEYS */;




/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
