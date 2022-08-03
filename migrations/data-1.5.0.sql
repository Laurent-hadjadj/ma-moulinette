BEGIN TRANSACTION;
-- ############### Evolutions 1.5.0 #####################
-- ## Migration 1.4.0 vers 1.5.0

-- ## Ajoute la table ma_moulinette.
CREATE TABLE ma_moulinette
(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
version VARCHAR(8) NOT NULL,
version_date VARCHAR(8) NOT NULL,
is_default BOOLEAN DEFAULT FALSE NOT NULL,
date_enregistrement DATETIME NOT NULL);

COMMIT;

BEGIN TRANSACTION;

-- ## Initialise la table des versions.
INSERT INTO ma_moulinette (version, version_date, is_default, date_enregistrement) VALUES ('1.0.0', '10/04/2022', FALSE, date('now'));
INSERT INTO ma_moulinette (version, version_date, is_default, date_enregistrement) VALUES ('1.1.0', '24/04/2022', FALSE, date('now'));
INSERT INTO ma_moulinette (version, version_date, is_default, date_enregistrement) VALUES ('1.2.0', '05/05/2022', FALSE, date('now'));
INSERT INTO ma_moulinette (version, version_date, is_default, date_enregistrement) VALUES ('1.2.6', '02/06/2022', FALSE, date('now'));
INSERT INTO ma_moulinette (version, version_date, is_default, date_enregistrement) VALUES ('1.3.0', '03/07/2022', FALSE, date('now'));
INSERT INTO ma_moulinette (version, version_date, is_default, date_enregistrement) VALUES ('1.4.0', '06/07/2022', FALSE, date('now'));
INSERT INTO ma_moulinette (version, version_date, is_default, date_enregistrement) VALUES ('1.5.0', '01/08/2022', TRUE, date('now'));

BEGIN TRANSACTION;
