-- phpMyAdmin SQL Dump
-- version 3.4.4
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Feb 14, 2012 at 07:21 PM
-- Server version: 5.0.92
-- PHP Version: 5.2.3

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `icemelon_melon`
--

-- --------------------------------------------------------

--
-- Table structure for table `im_account`
--

CREATE TABLE IF NOT EXISTS `im_account` (
  `accountID` int(255) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `site` varchar(255) NOT NULL,
  `description` text NOT NULL,
  `email` varchar(255) NOT NULL,
  `color0` varchar(8) NOT NULL,
  `color1` varchar(8) NOT NULL,
  `color2` varchar(8) NOT NULL,
  `color3` varchar(8) NOT NULL,
  `premium` int(1) NOT NULL,
  UNIQUE KEY `accountID` (`accountID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=192 ;

-- --------------------------------------------------------

--
-- Table structure for table `im_chatbox`
--

CREATE TABLE IF NOT EXISTS `im_chatbox` (
  `chatBoxID` int(255) NOT NULL auto_increment,
  `accountID` int(255) NOT NULL,
  `username1` varchar(255) NOT NULL,
  `username2` varchar(255) NOT NULL,
  `msgID1` int(255) NOT NULL,
  `msgID2` int(255) NOT NULL,
  `IMclosed1` int(1) NOT NULL,
  `IMclosed2` int(1) NOT NULL,
  UNIQUE KEY `chatBoxID` (`chatBoxID`),
  KEY `accountID` (`accountID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1098 ;

-- --------------------------------------------------------

--
-- Table structure for table `im_msg`
--

CREATE TABLE IF NOT EXISTS `im_msg` (
  `msgID` int(255) NOT NULL auto_increment,
  `chatBoxID` int(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `msg` varchar(255) NOT NULL,
  `timestamp` datetime NOT NULL,
  PRIMARY KEY  (`msgID`),
  KEY `chatBoxID` (`chatBoxID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=25767 ;

-- --------------------------------------------------------

--
-- Table structure for table `im_user`
--

CREATE TABLE IF NOT EXISTS `im_user` (
  `userID` int(255) NOT NULL auto_increment,
  `accountID` int(255) NOT NULL,
  `username` varchar(255) NOT NULL,
  `IP` varchar(255) NOT NULL,
  `last_activity` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `loggedOff` int(1) NOT NULL,
  `filterGroup` varchar(255) NOT NULL,
  UNIQUE KEY `userID` (`userID`),
  KEY `accountID` (`accountID`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=7349 ;
