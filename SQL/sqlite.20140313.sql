CREATE TABLE IF NOT EXISTS 'geoip' (
  'id' INTEGER NOT NULL PRIMARY KEY ASC,
  'ip' INTEGER NOT NULL,
  'ipv4' VARCHAR (15) NOT NULL,
  'country_code' VARCHAR(255) NOT NULL,
  'country_name' VARCHAR(255) NOT NULL,
  'region_code' VARCHAR(255) DEFAULT NULL,
  'region_name' VARCHAR(255) DEFAULT NULL,
  'city' VARCHAR(255) DEFAULT NULL,
  'zipcode' VARCHAR(255) DEFAULT NULL,
  'latitude' VARCHAR(255) NOT NULL,
  'longitude' VARCHAR(255) NOT NULL,
  'metro_code' VARCHAR(255) DEFAULT NULL,
  'areacode' VARCHAR(255) DEFAULT NULL,
  hits INTEGER DEFAULT NULL,
);

CREATE UNIQUE INDEX IF NOT EXISTS uniquegeoip ON geoip (ip);

INSERT INTO `system` (name, value) VALUES ('myrc_summary_ts', '0');
INSERT INTO `system` (name, value) VALUES ('myrc_summary_requests', '0');
INSERT INTO `system` (name, value) VALUES ('myrc_summary_service', '0');
UPDATE 'system' SET 'value'='initial|20140120|20140122|20140313' WHERE 'name'='myrc_summary';