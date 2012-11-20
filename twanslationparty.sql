-- phpMyAdmin SQL Dump
-- version 3.3.5.1
-- http://www.phpmyadmin.net
--
-- Host: localhost
-- Generation Time: Nov 20, 2012 at 01:14 AM
-- Server version: 5.1.51
-- PHP Version: 5.2.17

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `twitstash`
--

-- --------------------------------------------------------

--
-- Table structure for table `twanslationparty`
--

CREATE TABLE IF NOT EXISTS `twanslationparty` (
  `original_id` bigint(20) unsigned NOT NULL,
  `translated_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`original_id`),
  KEY `translated_id` (`translated_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
