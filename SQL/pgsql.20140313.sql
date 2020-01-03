CREATE TABLE IF NOT EXISTS geoip (
  id bigserial NOT NULL,
  ip bigint NOT NULL,
  ipv4 varchar(15) NOT NULL,
  country_code varchar(255) NOT NULL,
  country_name varchar(255) NOT NULL,
  region_code varchar(255) DEFAULT NULL,
  region_name varchar(255) DEFAULT NULL,
  city varchar(255) DEFAULT NULL,
  zipcode varchar(255) DEFAULT NULL,
  latitude varchar(255) NOT NULL,
  longitude varchar(255) NOT NULL,
  metro_code varchar(255) DEFAULT NULL,
  areacode varchar(255) DEFAULT NULL,
  hits integer DEFAULT NULL,
  PRIMARY KEY (id),
  UNIQUE (ip)
);

INSERT INTO `system` (name, value) VALUES ('myrc_summary_ts', '0');
INSERT INTO `system` (name, value) VALUES ('myrc_summary_requests', '0');
INSERT INTO `system` (name, value) VALUES ('myrc_summary_service', '0');
UPDATE system SET value='initial|20140120|20140122|20140313' WHERE name='myrc_summary';