SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";

-- --------------------------------------------------------

--
-- Table structure for table `twanslationparty`
--

CREATE TABLE IF NOT EXISTS `twanslationparty` (
  `original_id` bigint(20) unsigned NOT NULL,
  `translated_id` bigint(20) unsigned DEFAULT NULL,
  PRIMARY KEY (`original_id`),
  KEY `translated_id` (`translated_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
