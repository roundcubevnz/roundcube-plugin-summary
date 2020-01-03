UPDATE `system` SET `value`='initial|20140120|20140122|20140313|20140331' WHERE `name`='myrc_summary';
ALTER TABLE `geoip` CHANGE `areacode` `area_code` VARCHAR(255);