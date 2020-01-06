CREATE TABLE IF NOT EXISTS summary (
    id serial NOT NULL,
    user_id integer NOT NULL
      REFERENCES users (user_id) ON DELETE CASCADE ON UPDATE CASCADE,
    ts timestamp NOT NULL,
    ip varchar (15) DEFAULT NULL,
    PRIMARY KEY (id)
);
CREATE INDEX summary_user_idx ON summary (user_id);

CREATE TABLE IF NOT EXISTS "system" (
  name varchar(64) NOT NULL PRIMARY KEY,
  value text
);

INSERT INTO "system" (name, value) VALUES ('myrc_summary', 'initial');