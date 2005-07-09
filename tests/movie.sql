-- MySQL dump 9.11
--
-- Host: localhost    Database: reversefold
-- ------------------------------------------------------
-- Server version	4.0.24-max-log

--
-- Table structure for table `audioFormat`
--

CREATE TABLE `audioFormat` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `audioFormat`
--

INSERT INTO `audioFormat` VALUES (1,'Mono'),(2,'Stereo'),(3,'Dolby Pro Logic'),(4,'Dolby Digital 5.1'),(5,'Dolby Digital EX'),(6,'DTS');

--
-- Table structure for table `genre`
--

CREATE TABLE `genre` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(50) NOT NULL default '',
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `genre`
--

INSERT INTO `genre` VALUES (1,'Sci-Fi'),(2,'Fantasy'),(3,'Romance'),(4,'Action'),(5,'Horror'),(13,'Alien');

--
-- Table structure for table `language`
--

CREATE TABLE `language` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `language`
--

INSERT INTO `language` VALUES (1,'English'),(2,'German'),(3,'French'),(4,'Spanish'),(5,'Japanese'),(6,'Chinese');

--
-- Table structure for table `movie`
--

CREATE TABLE `movie` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(50) NOT NULL default '',
  `genre_id` int(10) unsigned NOT NULL default '0',
  `dateAcquired` datetime default NULL,
  `enumTest` enum('option 1','option 2','option 3') default NULL,
  `enumTest2` enum('option a','option b','optionc3') default NULL,
  `anotherField` varchar(20) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `movie`
--

INSERT INTO `movie` VALUES (4,'Alien',5,'2005-05-24 22:41:51','option 2','optionc3',''),(15,'Alien Resurrection',4,'2005-06-06 23:08:37','option 2','',''),(13,'Aliens',4,'2005-06-06 23:08:23','option 2','',''),(14,'Alien3',4,'2005-06-06 23:08:32','option 3','',''),(16,'Brazil',4,'2005-06-06 23:08:44','option 1','',''),(17,'Shawn of the Dead',5,'2005-06-06 23:08:55','option 1','',''),(18,'12 Monkeys',4,'2005-06-06 23:09:01','option 1','',''),(19,'The Ring',5,'2005-06-23 02:39:53','option 3','optionc3','');

--
-- Table structure for table `movie_audioFormat_language`
--

CREATE TABLE `movie_audioFormat_language` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `movie_id` int(10) unsigned default NULL,
  `audioFormat_id` int(10) unsigned default NULL,
  `language_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `movie_audioFormat_language`
--

INSERT INTO `movie_audioFormat_language` VALUES (35,17,1,5),(34,17,6,3),(33,17,3,2),(32,17,3,1),(31,17,4,1),(25,4,1,5),(26,4,3,1),(16,4,4,1),(23,4,3,5),(36,19,5,3),(37,19,3,6);

--
-- Table structure for table `movie_song`
--

CREATE TABLE `movie_song` (
  `id` int(11) NOT NULL auto_increment,
  `movie_id` int(10) unsigned default NULL,
  `song_id` int(10) unsigned default NULL,
  `movie_song__type_id` int(10) unsigned default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `movie_song`
--

INSERT INTO `movie_song` VALUES (33,18,2,2),(32,17,4,2),(24,4,5,1),(34,16,1,1),(26,4,1,NULL),(31,17,1,1),(35,16,3,2),(36,15,5,2),(37,15,4,2),(38,15,3,2),(39,13,2,1),(40,13,5,1),(41,13,1,2),(42,14,2,2),(43,14,1,NULL),(44,14,3,1),(45,18,5,NULL),(46,18,1,NULL),(47,18,4,NULL);

--
-- Table structure for table `movie_song__type`
--

CREATE TABLE `movie_song__type` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `movie_song__type`
--

INSERT INTO `movie_song__type` VALUES (1,'Inspired By'),(2,'Soundtrack');

--
-- Table structure for table `song`
--

CREATE TABLE `song` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `song`
--

INSERT INTO `song` VALUES (1,'Forty-Six and Two'),(2,'A Change of Seasons'),(3,'Watermark'),(4,'Gnosiennes'),(5,'Absolution');

