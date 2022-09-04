BEGIN TRANSACTION;
-- ############### Evolutions 1.5.0 #####################
-- ## Migration 1.4.0 vers 1.5.0

-- ## Ajoute la table ma_moulinette.
CREATE TABLE ma_moulinette
(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
version VARCHAR(8) NOT NULL,
date_version DATETIME NOT NULL,
is_default BOOLEAN DEFAULT FALSE NOT NULL,
date_enregistrement DATETIME NOT NULL);

COMMIT;

BEGIN TRANSACTION;

-- ## Initialise la table des versions.
INSERT INTO ma_moulinette (version, date_version, is_default, date_enregistrement) VALUES ('1.0.0', '2022-01-04', FALSE, date('now'));
INSERT INTO ma_moulinette (version, date_version, is_default, date_enregistrement) VALUES ('1.1.0', '2022-04-24', FALSE, date('now'));
INSERT INTO ma_moulinette (version, date_version, is_default, date_enregistrement) VALUES ('1.2.0', '2022-05-05', FALSE, date('now'));
INSERT INTO ma_moulinette (version, date_version, is_default, date_enregistrement) VALUES ('1.2.6', '2022-06-02', FALSE, date('now'));
INSERT INTO ma_moulinette (version, date_version, is_default, date_enregistrement) VALUES ('1.3.0', '2022-07-03', FALSE, date('now'));
INSERT INTO ma_moulinette (version, date_version, is_default, date_enregistrement) VALUES ('1.4.0', '2022-07-06', FALSE, date('now'));
INSERT INTO ma_moulinette (version, date_version, is_default, date_enregistrement) VALUES ('1.5.0', '2022-08-01', TRUE, date('now'));

COMMIT;

BEGIN TRANSACTION;

-- ## Initialise la table des utiliasteurs.
-- roles : (DC2Type:json)

CREATE TABLE utilisateur
(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    courriel VARCHAR(180) NOT NULL,
    roles CLOB NOT NULL,
    password VARCHAR(255) NOT NULL)
);

CREATE UNIQUE INDEX UNIQ_1D1C63B344FB41C9 ON utilisateur (courriel);

COMMIT;
