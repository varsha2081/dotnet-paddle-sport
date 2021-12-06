SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

CREATE TABLE IF NOT EXISTS `FieldClubBookings` (
  `id` int(11) NOT NULL auto_increment,
  `sportId` varchar(255) NOT NULL,
  `userId` varchar(255) NOT NULL,
  `courtId` varchar(255) NOT NULL,
  `startDateTime` datetime NOT NULL,
  `endDateTime` datetime NOT NULL,
  `teamId` varchar(255) NOT NULL COMMENT 'team that will be using this booking (not necessary) ',
  `notes` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=6354 ;

CREATE TABLE IF NOT EXISTS `FieldClubCourts` (
  `id` int(11) NOT NULL auto_increment,
  `usedBySports` varchar(255) NOT NULL,
  `openFrom` time NOT NULL,
  `openUntil` time NOT NULL,
  `name` varchar(255) NOT NULL,
  `slotDuration` int(11) NOT NULL COMMENT 'Duration of slots in minutes',
  `statusOverride` int(11) NOT NULL default '-1' COMMENT '0 = Free (no override), 1 = Taken, 2 = TakenByOverlap, 3 = ClosedForMaintenance',
  `coordinates` text NOT NULL COMMENT 'The coordinates that mark the span of the court (used for the map display)',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Courts in Field Club' AUTO_INCREMENT=15 ;

CREATE TABLE IF NOT EXISTS `FieldClubCourtsOverlap` (
  `id` int(11) NOT NULL auto_increment,
  `courtA` int(11) NOT NULL,
  `courtB` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=27 ;

CREATE TABLE IF NOT EXISTS `FieldClubKitOrderItems` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `sizes` varchar(255) NOT NULL,
  `price` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=10 ;

CREATE TABLE IF NOT EXISTS `FieldClubKitOrders` (
  `id` int(11) NOT NULL auto_increment,
  `closingDate` datetime NOT NULL,
  `name` varchar(255) NOT NULL,
  `items` varchar(255) NOT NULL,
  `paymentTo` varchar(255) NOT NULL,
  `adminUserId` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

CREATE TABLE IF NOT EXISTS `FieldClubKitOrderSizes` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=34 ;

CREATE TABLE IF NOT EXISTS `FieldClubLog` (
  `id` int(11) NOT NULL auto_increment,
  `hostname` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `dateTime` datetime NOT NULL,
  `message` text NOT NULL,
  `userId` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=15646 ;

CREATE TABLE IF NOT EXISTS `FieldClubPlacedKitOrders` (
  `id` int(11) NOT NULL auto_increment,
  `userId` int(11) NOT NULL,
  `kitOrderId` int(11) NOT NULL,
  `orderedItems` varchar(255) NOT NULL,
  `status` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=51 ;

CREATE TABLE IF NOT EXISTS `FieldClubSports` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `maxFutureSlots` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Sports at the Field Club' AUTO_INCREMENT=14 ;

CREATE TABLE IF NOT EXISTS `FieldClubTeams` (
  `id` int(11) NOT NULL auto_increment,
  `name` varchar(255) NOT NULL,
  `sportId` int(11) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Teams of the Field Club' AUTO_INCREMENT=23 ;

CREATE TABLE IF NOT EXISTS `FieldClubUserRequests` (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `dateTime` datetime NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Requests for new users' AUTO_INCREMENT=120 ;

CREATE TABLE IF NOT EXISTS `FieldClubUsers` (
  `id` int(11) NOT NULL auto_increment,
  `accessLevel` varchar(4) NOT NULL COMMENT '0 = admin, 1 = users',
  `captainOfTeams` varchar(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `userType` varchar(10) NOT NULL COMMENT '0 = student, 1 = fellow',
  `userStatus` varchar(10) NOT NULL COMMENT '0 = green, 1 = yellow, 2 = red',
  `userNotes` text,
  `loginType` varchar(2) NOT NULL COMMENT '0 = alternative login, 1 = raven',
  `email` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COMMENT='Users of Field Club system' AUTO_INCREMENT=402 ;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
