SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

--
-- Table structure for table `deviceRegistration`
--

CREATE TABLE IF NOT EXISTS `deviceRegistration` (
  `entryID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `registrationID` varchar(255) NOT NULL,
  `createStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`entryID`),
  UNIQUE KEY `registrationID` (`registrationID`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

