-- ############### Reprise          #####################
-- ###  1.2.4 --> 1.3.0
-- ###  1.3.0 --> 1.4.0

-- ############### Evolutions 1.3.0 #####################
-- ## Migration 1.2.4 vers 1.3.0

BEGIN TRANSACTION;

-- ## Ajout de la table repartition
CREATE TABLE IF NOT EXISTS repartition (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  maven_key VARCHAR(128) NOT NULL, name VARCHAR(128) NOT NULL,
  component CLOB NOT NULL, type VARCHAR(16) NOT NULL,
  severity VARCHAR(8) NOT NULL, setup UNSIGNED BIG INT NOT NULL,
  date_enregistrement DATETIME NOT NULL);

COMMIT;

-- ############### Evolutions 1.4.0 #####################
-- ## Migration 1.3.0 vers 1.4.0

BEGIN TRANSACTION;

-- ## Ajout de l'attribut liste dans la table anomalie avec comme valeur par défaut 1.
ALTER TABLE anomalie ADD COLUMN liste BOOLEAN DEFAULT 1 NOT NULL;

COMMIT;

-- ############### Evolutions 1.5.0 #####################
-- ## Migration 1.4.0 vers 1.5.0

BEGIN TRANSACTION;
--  ## correction des boolean ---
--  ## TRUE = 1 et FALSE = 0
UPDATE favori SET favori=1 WHERE favori='TRUE';
UPDATE favori SET favori=0 WHERE favori='FALSE'

COMMIT;

BEGIN TRANSACTION;

-- ## Ajoute la table ma_moulinette.
CREATE TABLE IF NOT EXISTS ma_moulinette
(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
version VARCHAR(8) NOT NULL,
date_version DATETIME NOT NULL,
date_enregistrement DATETIME NOT NULL);

COMMIT;

BEGIN TRANSACTION;

-- ## Initialise la table des versions. 0 (false) and 1 (true).
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.0.0', '2022-01-04', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.1.0', '2022-04-24', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.2.0', '2022-05-05', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.2.6', '2022-06-02', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.3.0', '2022-07-03', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.4.0', '2022-07-06', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.5.0', '2022-09-18', date('now'));

COMMIT;

BEGIN TRANSACTION;

-- ## Initialise la table des utilisateurs.

CREATE TABLE IF NOT EXISTS utilisateur
(id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  avatar VARCHAR(128) DEFAULT NULL),
  prenom VARCHAR(32) NOT NULL,
  nom VARCHAR(64) NOT NULL,
  courriel VARCHAR(180) NOT NULL,
  password VARCHAR(255) NOT NULL,
  actif BOOLEAN DEFAULT 0 NOT NULL,
  date_modification DATETIME DEFAULT NULL,
  date_enregistrement DATETIME NOT NULL,
  roles CLOB NOT NULL --(DC2Type:json)
);

CREATE UNIQUE INDEX UNIQ_1D1C63B344FB41C9 ON utilisateur (courriel);

COMMIT;

BEGIN TRANSACTION;

-- ## Ajoute le compte admin

INSERT INTO utilisateur
(courriel, roles,  password, nom, prenom, date_enregistrement, actif, avatar )
VALUES
('admin@ma-moulinette.fr',
'["ROLE_GESTIONNAIRE"]',
'$2y$10$g1KdFM/ARBc7DG0UClLOl./4Cv.urhltS8zWPtOVzq78qkcSjliGa',
'admin',
'@ma-moulinette',
'1980-01-01 00:00:00',
1,
'chiffre/01.png');

COMMIT;
