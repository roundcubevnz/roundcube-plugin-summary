CREATE TABLE IF NOT EXISTS `summary` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(10) NOT NULL,
  `ts` datetime NOT NULL,
  `ip` varchar(15) CHARACTER SET latin1 DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `system` (
  `name` varchar(64) NOT NULL,
  `value` mediumtext,
  PRIMARY KEY(`name`)
);

INSERT INTO `system` (name, value) VALUES ('myrc_summary', 'initial');

ALTER TABLE `summary`
  ADD CONSTRAINT `summary_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE ON UPDATE CASCADE;