CREATE TABLE IF NOT EXISTS blacklist (
    id serial NOT NULL,
    ip VARCHAR (15) NOT NULL,
    ts INTEGER NOT NULL,
    PRIMARY KEY (id)
);

CREATE TABLE IF NOT EXISTS blacklistcandidates (
    id serial NOT NULL,
    ip VARCHAR (15) NOT NULL,
    ts INTEGER NOT NULL,
    PRIMARY KEY (id)
);

UPDATE 'system' SET 'value'='initial|20140120|20140122' WHERE 'name'='myrc_summary';