-- MySQL dump 10.14  Distrib 5.5.56-MariaDB, for Linux (x86_64)
--
-- Host: localhost    Database: medora
-- ------------------------------------------------------
-- Server version	5.5.56-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `acl`
--

DROP TABLE IF EXISTS `acl`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `acl` (
  `acl_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT '0',
  `group_id` int(11) DEFAULT '0',
  `asset` text COLLATE utf8_unicode_ci,
  `asset_id` int(11) DEFAULT '0',
  `permission` int(11) DEFAULT '0',
  PRIMARY KEY (`acl_id`),
  KEY `group_id` (`group_id`),
  KEY `asset_id` (`asset_id`),
  KEY `permission` (`permission`),
  KEY `user_id` (`user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=10587 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `admin_menus`
--

DROP TABLE IF EXISTS `admin_menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `admin_menus` (
  `admin_menu_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(45) NOT NULL,
  `code` varchar(45) NOT NULL,
  `link` varchar(90) NOT NULL,
  `class` text,
  `sort` int(3) NOT NULL DEFAULT '10',
  `is_active` int(1) NOT NULL DEFAULT '1',
  `show_beneath` varchar(45) DEFAULT NULL,
  `asset_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`admin_menu_id`),
  UNIQUE KEY `admin_menu_id_UNIQUE` (`admin_menu_id`),
  UNIQUE KEY `menu_title_UNIQUE` (`title`),
  UNIQUE KEY `code_UNIQUE` (`code`),
  UNIQUE KEY `link_UNIQUE` (`link`)
) ENGINE=InnoDB AUTO_INCREMENT=68 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `assets`
--

DROP TABLE IF EXISTS `assets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `assets` (
  `asset_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `location` text CHARACTER SET utf8,
  `clean_name` text CHARACTER SET utf8,
  `sort` int(10) DEFAULT '0',
  `visible` int(11) DEFAULT '1',
  PRIMARY KEY (`asset_id`),
  UNIQUE KEY `name_UNIQUE` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calendar_events`
--

DROP TABLE IF EXISTS `calendar_events`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendar_events` (
  `calendar_event_id` int(10) NOT NULL AUTO_INCREMENT,
  `calendar_id` int(10) NOT NULL,
  `title` text NOT NULL,
  `start_timestamp` datetime NOT NULL,
  `duration` time NOT NULL DEFAULT '01:00:00',
  `allday` tinyint(1) NOT NULL DEFAULT '1',
  `location` text,
  `description` longtext,
  `short_description` longtext,
  `recurrence_type_id` int(11) NOT NULL DEFAULT '1',
  `recurrence_last_timestamp` datetime NOT NULL,
  PRIMARY KEY (`calendar_event_id`),
  KEY `calendar_id` (`calendar_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `calendars`
--

DROP TABLE IF EXISTS `calendars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `calendars` (
  `calendar_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `title` text,
  `color` text NOT NULL,
  PRIMARY KEY (`calendar_id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8 ROW_FORMAT=COMPACT;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cswal_auth_token_table`
--

DROP TABLE IF EXISTS `cswal_auth_token_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cswal_auth_token_table` (
  `auth_token_id` varchar(100) NOT NULL,
  `token_type_id` int(11) NOT NULL DEFAULT '1',
  `uid` int(11) DEFAULT '0',
  `passwd` text NOT NULL,
  `max_uses` int(11) NOT NULL DEFAULT '1',
  `total_uses` int(11) NOT NULL DEFAULT '0',
  `creation` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expiration` timestamp NULL DEFAULT NULL,
  `stored_value` text,
  PRIMARY KEY (`auth_token_id`),
  KEY `fk_cswal_auth_token_table_1_idx` (`token_type_id`),
  CONSTRAINT `fk_cswal_auth_token_table_1` FOREIGN KEY (`token_type_id`) REFERENCES `cswal_token_type_table` (`token_type_id`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `cswal_token_type_table`
--

DROP TABLE IF EXISTS `cswal_token_type_table`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cswal_token_type_table` (
  `token_type_id` int(11) NOT NULL AUTO_INCREMENT,
  `token_type` varchar(30) NOT NULL,
  `token_desc` text,
  PRIMARY KEY (`token_type_id`),
  UNIQUE KEY `token_type_UNIQUE` (`token_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `galleries`
--

DROP TABLE IF EXISTS `galleries`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `galleries` (
  `gallery_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8,
  `description` longtext CHARACTER SET utf8,
  `sort` int(11) DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`gallery_id`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `gallery_photos`
--

DROP TABLE IF EXISTS `gallery_photos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `gallery_photos` (
  `gallery_photo_id` int(10) NOT NULL AUTO_INCREMENT,
  `gallery_id` int(10) NOT NULL DEFAULT '0',
  `media_id` int(10) NOT NULL DEFAULT '0',
  `name` varchar(256) CHARACTER SET utf8 NOT NULL DEFAULT '0',
  `description` blob NOT NULL,
  `sort` int(11) NOT NULL DEFAULT '0',
  PRIMARY KEY (`gallery_photo_id`)
) ENGINE=InnoDB AUTO_INCREMENT=248 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `groups`
--

DROP TABLE IF EXISTS `groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `groups` (
  `group_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(45) COLLATE utf8_unicode_ci NOT NULL,
  `description` text COLLATE utf8_unicode_ci,
  `created` datetime DEFAULT NULL,
  `modified` datetime DEFAULT NULL,
  PRIMARY KEY (`group_id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=27 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `log`
--

DROP TABLE IF EXISTS `log`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` text NOT NULL,
  `admin_id` int(11) NOT NULL DEFAULT '0',
  `module` text NOT NULL,
  `message` longtext NOT NULL,
  `stacktrace` longtext,
  `ipaddress` varchar(128) NOT NULL DEFAULT '',
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=35 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media` (
  `media_id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` text CHARACTER SET utf8,
  `display_filename` text CHARACTER SET utf8 NOT NULL,
  `filetype` text CHARACTER SET utf8,
  `filesize` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `is_folder` int(11) DEFAULT '0',
  `parent_id` int(11) DEFAULT '0',
  `deleteable` int(11) DEFAULT '1',
  `media_folder_id` int(11) NOT NULL DEFAULT '1',
  `admin_id` int(11) DEFAULT NULL,
  `user` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `date_modified` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`media_id`),
  KEY `fk_media_1_idx` (`media_folder_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3704 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `media_folder_categories`
--

DROP TABLE IF EXISTS `media_folder_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_folder_categories` (
  `media_folder_category_id` int(11) NOT NULL AUTO_INCREMENT,
  `media_folder_id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  PRIMARY KEY (`media_folder_category_id`),
  KEY `fk_media_folder_categories_1_idx` (`media_folder_id`),
  KEY `fk_media_folder_categories_2_idx` (`category_id`),
  CONSTRAINT `fk_media_folder_categories_1` FOREIGN KEY (`media_folder_id`) REFERENCES `media_folders` (`media_folder_id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  CONSTRAINT `fk_media_folder_categories_2` FOREIGN KEY (`category_id`) REFERENCES `categories` (`category_id`) ON DELETE CASCADE ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `media_folders`
--

DROP TABLE IF EXISTS `media_folders`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media_folders` (
  `media_folder_id` int(11) NOT NULL AUTO_INCREMENT,
  `path` varchar(60) NOT NULL DEFAULT '/data/upfiles/media/',
  `display_name` varchar(50) NOT NULL,
  PRIMARY KEY (`media_folder_id`),
  UNIQUE KEY `media_folder_id_UNIQUE` (`media_folder_id`),
  UNIQUE KEY `display_name_UNIQUE` (`display_name`)
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menu_items`
--

DROP TABLE IF EXISTS `menu_items`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menu_items` (
  `menu_item_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `menu_id` int(10) DEFAULT '0',
  `page_id` int(10) unsigned DEFAULT NULL,
  `parent_id` int(10) unsigned DEFAULT '0',
  `link` text CHARACTER SET utf8,
  `title` text CHARACTER SET utf8,
  `class` text CHARACTER SET utf8,
  `sort` int(10) DEFAULT NULL,
  `sub_menu_id` int(10) DEFAULT NULL,
  PRIMARY KEY (`menu_item_id`),
  KEY `menu_id` (`menu_id`),
  KEY `sub_menu_id` (`sub_menu_id`),
  KEY `sort` (`sort`),
  KEY `page_id` (`page_id`),
  KEY `parent_id` (`parent_id`),
  CONSTRAINT `menu_id` FOREIGN KEY (`menu_id`) REFERENCES `menus` (`menu_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `sub_menu_id` FOREIGN KEY (`sub_menu_id`) REFERENCES `menus` (`menu_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=318 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `menus`
--

DROP TABLE IF EXISTS `menus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `menus` (
  `menu_id` int(10) NOT NULL AUTO_INCREMENT,
  `name` text CHARACTER SET utf8,
  `class` varchar(45) COLLATE utf8_unicode_ci DEFAULT NULL,
  `sort` int(5) DEFAULT NULL,
  PRIMARY KEY (`menu_id`),
  KEY `sort` (`menu_id`,`sort`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `mimes`
--

DROP TABLE IF EXISTS `mimes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `mimes` (
  `mime_id` int(10) NOT NULL AUTO_INCREMENT,
  `mime` varchar(128) CHARACTER SET utf8 DEFAULT NULL,
  `ext` varchar(16) CHARACTER SET utf8 DEFAULT NULL,
  `allowed` int(11) DEFAULT '0',
  `image` int(10) DEFAULT '0',
  PRIMARY KEY (`mime_id`),
  KEY `mime` (`mime`),
  KEY `ext` (`ext`),
  KEY `allowed` (`allowed`),
  KEY `image` (`image`)
) ENGINE=InnoDB AUTO_INCREMENT=276 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `news`
--

DROP TABLE IF EXISTS `news`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `news` (
  `news_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` text CHARACTER SET utf8,
  `byline` text CHARACTER SET utf8,
  `start_date` datetime DEFAULT NULL,
  `end_date` datetime DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `short_description` text CHARACTER SET utf8,
  `description` text CHARACTER SET utf8,
  `front_page` text CHARACTER SET utf8,
  `category` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `approved` text CHARACTER SET utf8 COLLATE utf8_unicode_ci,
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`news_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notification_types`
--

DROP TABLE IF EXISTS `notification_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notification_types` (
  `notification_type_id` int(11) NOT NULL,
  `notification_type` varchar(10) NOT NULL,
  `description` text,
  `color` varchar(6) NOT NULL,
  `icon` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`notification_type_id`),
  UNIQUE KEY `notification_type_id_UNIQUE` (`notification_type_id`),
  UNIQUE KEY `notification_type_UNIQUE` (`notification_type`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `notifications`
--

DROP TABLE IF EXISTS `notifications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `notifications` (
  `notification_id` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(45) NOT NULL,
  `body` text NOT NULL,
  `notification_type_id` int(11) NOT NULL DEFAULT '1',
  `date_created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_active` int(11) NOT NULL DEFAULT '1',
  PRIMARY KEY (`notification_id`),
  UNIQUE KEY `notification_id_UNIQUE` (`notification_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `pages`
--

DROP TABLE IF EXISTS `pages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `pages` (
  `page_id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) DEFAULT '0',
  `is_landing_page` int(11) DEFAULT '0',
  `include_inside_nav` int(11) DEFAULT '1',
  `title` text CHARACTER SET utf8,
  `keywords` text CHARACTER SET utf8,
  `description` text CHARACTER SET utf8,
  `body` text CHARACTER SET utf8,
  `body_extra1` text COLLATE utf8_unicode_ci,
  `body_extra2` text COLLATE utf8_unicode_ci,
  `body_extra3` text COLLATE utf8_unicode_ci,
  `sort` int(11) DEFAULT NULL,
  `url` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `status` varchar(256) COLLATE utf8_unicode_ci DEFAULT 'active',
  `redirect` text CHARACTER SET utf8,
  `asset` varchar(256) CHARACTER SET utf8 DEFAULT NULL,
  `template` varchar(100) COLLATE utf8_unicode_ci DEFAULT NULL,
  `created` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `modified` datetime DEFAULT NULL,
  `media_id` int(11) DEFAULT NULL,
  `og_title` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `og_image_media_id` int(11) DEFAULT '0',
  `og_description` text COLLATE utf8_unicode_ci,
  `required_group_id` int(11) DEFAULT NULL,
  `asset_argument` varchar(45) COLLATE utf8_unicode_ci DEFAULT '',
  `para1` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  `para2` varchar(256) COLLATE utf8_unicode_ci DEFAULT NULL,
  PRIMARY KEY (`page_id`),
  UNIQUE KEY `unique_url` (`url`),
  KEY `parent_id` (`parent_id`),
  KEY `url` (`url`),
  KEY `asset` (`asset`),
  KEY `is_landing_page` (`is_landing_page`),
  KEY `og_image_media_id` (`og_image_media_id`),
  KEY `media_id` (`media_id`),
  KEY `status` (`status`)
) ENGINE=MyISAM AUTO_INCREMENT=1005 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;


--
-- Table structure for table `recurrence_types`
--

DROP TABLE IF EXISTS `recurrence_types`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recurrence_types` (
  `recurrence_type_id` int(11) NOT NULL,
  `recurrence_type` varchar(45) NOT NULL,
  `interval` varchar(45) DEFAULT NULL,
  `default_recurrence_end` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`recurrence_type_id`),
  UNIQUE KEY `recurrence_type_UNIQUE` (`recurrence_type`),
  UNIQUE KEY `recurrence_type_id_UNIQUE` (`recurrence_type_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `setting_categories`
--

DROP TABLE IF EXISTS `setting_categories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `setting_categories` (
  `setting_category_id` int(11) NOT NULL,
  `setting_category_name` varchar(45) DEFAULT NULL,
  `setting_code` varchar(45) NOT NULL,
  PRIMARY KEY (`setting_category_id`),
  UNIQUE KEY `setting_category_id_UNIQUE` (`setting_category_id`),
  UNIQUE KEY `setting_code_UNIQUE` (`setting_code`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `settings`
--

DROP TABLE IF EXISTS `settings`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `settings` (
  `setting_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` text CHARACTER SET utf8,
  `description` text CHARACTER SET utf8,
  `type` varchar(45) CHARACTER SET utf8 DEFAULT NULL,
  `name` varchar(45) CHARACTER SET utf8 NOT NULL DEFAULT '',
  `value` text CHARACTER SET utf8,
  `setting_category_id` int(11) NOT NULL DEFAULT '1',
  `admin` tinyint(1) unsigned DEFAULT '0',
  `modified` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`setting_id`),
  UNIQUE KEY `unique` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `snippets`
--

DROP TABLE IF EXISTS `snippets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `snippets` (
  `snippet_id` int(11) NOT NULL AUTO_INCREMENT,
  `code` varchar(45) NOT NULL,
  `description` text,
  `body` blob,
  PRIMARY KEY (`snippet_id`),
  UNIQUE KEY `code_UNIQUE` (`code`)
) ENGINE=InnoDB AUTO_INCREMENT=19 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `states`
--

DROP TABLE IF EXISTS `states`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `states` (
  `state_id` int(10) NOT NULL AUTO_INCREMENT,
  `state` varchar(128) DEFAULT NULL,
  `abbreviation` varchar(16) DEFAULT NULL,
  `country` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`state_id`)
) ENGINE=InnoDB AUTO_INCREMENT=61 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `user_groups`
--

DROP TABLE IF EXISTS `user_groups`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `user_groups` (
  `user_group_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`user_group_id`)
) ENGINE=InnoDB AUTO_INCREMENT=205 DEFAULT CHARSET=latin1;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
  `company` varchar(255) CHARACTER SET utf8 DEFAULT NULL,
  `username` varchar(100) CHARACTER SET utf8 NOT NULL,
  `password` varchar(100) CHARACTER SET utf8 DEFAULT NULL,
  `email` text COLLATE utf8_unicode_ci,
  `created` timestamp NULL DEFAULT NULL,
  `modified` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `is_active` int(1) NOT NULL DEFAULT '0',
  PRIMARY KEY (`user_id`),
  UNIQUE KEY `username_UNIQUE` (`username`),
  KEY `username_password` (`username`,`password`)
) ENGINE=InnoDB AUTO_INCREMENT=65 DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci ROW_FORMAT=DYNAMIC;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2018-02-19  9:04:31


-- NOTE: this is an old function that is in bad need of retirement.
DELIMITER $$
CREATE FUNCTION `url`(`thestring` TEXT) RETURNS text CHARSET latin1
BEGIN

declare returnstring text;

set returnstring = TRIM(thestring); #remove extra spaces
set returnstring = REPLACE(REPLACE(returnstring,'/',''),' ','-'); #remove slashes and spaces
set returnstring = REPLACE(REPLACE(returnstring,'"',''),"'",""); # replace quotes
set returnstring = REPLACE(returnstring,',',''); # replace commas
set returnstring = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(returnstring,"?",""),"!",""),"!",""),"!",""),"@",""),"#",""),"$",""),"%",""),"^",""),"&",""),"*",""),"(",""),")",""),'.','-'),'&','-'); #remove special characters
set returnstring = REPLACE(returnstring,"--","-"); #remove douibledashes
set returnstring = REPLACE(returnstring,"Ã¸","o"); #remove slashed o
set returnstring = REPLACE(returnstring,"ø","o"); #remove slashed o
set returnstring = REPLACE(returnstring,"Ã¥","a"); #remove slashed o
set returnstring = LOWER(returnstring); # make lowercase
return returnstring;

END$$
DELIMITER ;
