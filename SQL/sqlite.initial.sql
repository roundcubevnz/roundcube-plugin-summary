CREATE TABLE IF NOT EXISTS 'summary' (
  'id' INTEGER NOT NULL PRIMARY KEY ASC,
  'user_id' INTEGER NOT NULL,
  'ts' DATETIME NOT NULL,
  'ip' VARCHAR (15) DEFAULT NULL,
  CONSTRAINT 'summary_ibfk_1' FOREIGN KEY ('user_id') REFERENCES 'users'
    ('user_id') ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE IF NOT EXISTS 'system' (
  name varchar(64) NOT NULL PRIMARY KEY,
  value text NOT NULL
);

INSERT INTO system (name, value) VALUES ('myrc_summary', 'initial');