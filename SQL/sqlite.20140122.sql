CREATE TABLE IF NOT EXISTS 'blacklist' (
  'id' INTEGER NOT NULL PRIMARY KEY ASC,
  'ip' VARCHAR (15) NOT NULL,
  'ts' INTEGER NOT NULL
);

CREATE TABLE IF NOT EXISTS 'blacklistcandidates' (
  'id' INTEGER NOT NULL PRIMARY KEY ASC,
  'ip' VARCHAR(15) NOT NULL,
  'ts' INTEGER NOT NULL
);

UPDATE 'system' SET 'value'='initial|20140120|20140122' WHERE 'name'='myrc_summary';