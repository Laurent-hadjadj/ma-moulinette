-- ############### Evolutions 1.6.0 #####################
-- ## Migration 1.5.0 vers 1.6.0


BEGIN TRANSACTION;

-- ## Ajout de la version 1.6.0 dans la table ma_moulinette
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.6.0', '2022-11-29', date('now'));

COMMIT;

BEGIN TRANSACTION;

-- ## Ajout de la table tags
CREATE TABLE IF NOT EXISTS tags (
	"id"	INTEGER NOT NULL,
	"maven_key"	VARCHAR(128) NOT NULL,
	"name"	VARCHAR(64) NOT NULL,
	"tags"	CLOB NOT NULL,
	"visibility"	VARCHAR(8) NOT NULL,
	"date_enregistrement"	DATETIME NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;

BEGIN TRANSACTION;

-- ## Ajout de la table equipe
CREATE TABLE IF NOT EXISTS equipe (
	"id"	INTEGER NOT NULL,
	"nom"	VARCHAR(32) NOT NULL,
	"description"	VARCHAR(128) NOT NULL,
	"date_modification"	DATETIME DEFAULT NULL,
	"date_enregistrement"	DATETIME NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;

BEGIN TRANSACTION;

-- ## Ajout de la table portefeuilles
CREATE TABLE IF NOT EXISTS portefeuille (
	"id"	INTEGER NOT NULL,
	"equipe"	VARCHAR(32) NOT NULL,
	"liste"	CLOB NOT NULL,
	"nom"	VARCHAR(32) NOT NULL,
	"date_modification"	DATETIME DEFAULT NULL,
	"date_enregistrement"	DATETIME NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;


BEGIN TRANSACTION;

-- ## Ajout de l'attribut equipe dans la table utiliasteur
ALTER TABLE utilisateur ADD COLUMN equipe CLOB DEFAULT NULL;

COMMIT;
