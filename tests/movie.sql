-- MySQL dump 9.11
--
-- Host: localhost    Database: reversefold
-- ------------------------------------------------------
-- Server version	4.0.24-max-log

--
-- Table structure for table `audioFormat`
--

DROP TABLE IF EXISTS `audioFormat`;
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

DROP TABLE IF EXISTS `genre`;
CREATE TABLE `genre` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `genre`
--

INSERT INTO `genre` VALUES (1,'Sci-Fi'),(2,'Fantasy'),(3,'Romance'),(4,'Action'),(5,'Horror');

--
-- Table structure for table `language`
--

DROP TABLE IF EXISTS `language`;
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

DROP TABLE IF EXISTS `movie`;
CREATE TABLE `movie` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(50) default NULL,
  `genre_id` int(10) unsigned default NULL,
  `dateAcquired` datetime default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `movie`
--

INSERT INTO `movie` VALUES (3,'Testing',5,'2005-04-22 19:36:00');

--
-- Table structure for table `movie_audioFormat_language`
--

DROP TABLE IF EXISTS `movie_audioFormat_language`;
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

INSERT INTO `movie_audioFormat_language` VALUES (7,3,5,5),(6,3,4,1),(8,3,3,2);

--
-- Table structure for table `movie_song`
--

DROP TABLE IF EXISTS `movie_song`;
CREATE TABLE `movie_song` (
  `id` int(10) unsigned NOT NULL default '0',
  `movie_id` int(10) unsigned default NULL,
  `song_id` int(10) unsigned default NULL,
  `movie_song__type_id` int(10) unsigned default NULL
) TYPE=MyISAM;

--
-- Dumping data for table `movie_song`
--

INSERT INTO `movie_song` VALUES (18,3,2,2),(14,3,3,2),(16,3,4,1);

--
-- Table structure for table `movie_song__type`
--

DROP TABLE IF EXISTS `movie_song__type`;
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

DROP TABLE IF EXISTS `song`;
CREATE TABLE `song` (
  `id` int(10) unsigned NOT NULL auto_increment,
  `title` varchar(50) default NULL,
  PRIMARY KEY  (`id`)
) TYPE=MyISAM;

--
-- Dumping data for table `song`
--

INSERT INTO `song` VALUES (1,'Forty-Six and Two'),(2,'A Change of Seasons'),(3,'Watermark'),(4,'Gnosiennes'),(5,'Absolution');

