CREATE TABLE IF NOT EXISTS `geoip` (
  `id` int(12) unsigned NOT NULL AUTO_INCREMENT,
  `ip` int(10) unsigned NOT NULL,
  `ipv4` varchar(15) NOT NULL,
  `country_code` varchar(255) NOT NULL,
  `country_name` varchar(255) NOT NULL,
  `region_code` varchar(255) DEFAULT NULL,
  `region_name` varchar(255) DEFAULT NULL,
  `city` varchar(255) DEFAULT NULL,
  `zipcode` varchar(255) DEFAULT NULL,
  `latitude` varchar(255) NOT NULL,
  `longitude` varchar(255) NOT NULL,
  `metro_code` varchar(255) DEFAULT NULL,
  `areacode` varchar(255) DEFAULT NULL,
  `hits` int(11) unsigned DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

INSERT INTO `system` (name, value) VALUES ('myrc_summary_ts', '0');
INSERT INTO `system` (name, value) VALUES ('myrc_summary_requests', '0');
INSERT INTO `system` (name, value) VALUES ('myrc_summary_service', '0');
UPDATE `system` SET `value`='initial|20140120|20140122|20140313' WHERE `name`='myrc_summary';