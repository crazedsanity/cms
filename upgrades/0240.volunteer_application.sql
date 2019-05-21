/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Author:  danf
 * Created: Mar 23, 2018
 */

CREATE TABLE `volunteer_application` (
  `volunteer_application_id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `first_name` text NOT NULL,
  `last_name` text NOT NULL,
  `first_name2` text NOT NULL,
  `last_name2` text NOT NULL,
  `first_name3` text NOT NULL,
  `first_name4` text NOT NULL,
  `last_name3` text NOT NULL,
  `last_name4` text NOT NULL,
  `address` text NOT NULL,
  `city` text NOT NULL,
  `state` text NOT NULL,
  `zip` text NOT NULL,
  `phone` text NOT NULL,
  `address2` text NOT NULL,
  `city2` text NOT NULL,
  `state2` text NOT NULL,
  `zip2` text NOT NULL,
  `phone2` text NOT NULL,
  `email` text NOT NULL,
  `totalapplying` text NOT NULL,
  `experience` text NOT NULL,
  `add_experience` text NOT NULL,
  `preference1` text NOT NULL,
  `preference2` text NOT NULL,
  `preference3` text NOT NULL,
  `nopreference` int(11) NOT NULL DEFAULT '0',
  `requestcopy` int(11) NOT NULL DEFAULT '0',
  `archive` int(11) NOT NULL DEFAULT '0',
  `status` varchar(50) DEFAULT 'notreviewed',
  `reviewby` varchar(50) DEFAULT NULL,
  `depart` date DEFAULT NULL,
  `arrive` date DEFAULT NULL,
  `work_type` text,
  `rfirst_name` text,
  `rlast_name` text,
  `relationship` text,
  `raddress` text,
  `rcity` text,
  `rstate` text,
  `rzip` text,
  `rphone` text,
  `rcell` text,
  `crime` text,
  `crimexp` text,
  `applytype` text,
  `applytype_partner` text,
  `sro` text,
  `volunteer_status` text,
  `available_extra` text,
  `initial` text,
  `sig` text,
  PRIMARY KEY (`volunteer_application_id`)
) ENGINE=MyISAM AUTO_INCREMENT=1364 DEFAULT CHARSET=latin1;

INSERT INTO `acl` (`user_id`, `group_id`, `asset`, `asset_id`, `permission`) VALUES 
	('0', '1', 'volunteer', '0', '4'),
	('0', '1', 'volunteer', '0', '6'),
	('0', '1', 'volunteer', '0', '7');
INSERT INTO `admin_menus` (`title`, `code`, `link`, `sort`, `is_active`, `asset_id`) VALUES ('Volunteer Applications', 'volunteer', 'volunteer', '10', '1', '85');
