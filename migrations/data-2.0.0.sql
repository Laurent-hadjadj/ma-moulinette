-- ############### Evolutions 2.0.0 #####################
-- ## Migration 1.6.0 vers 2.0.0


BEGIN TRANSACTION;

-- ## Ajout de la version 2.0.0 dans la table ma_moulinette
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('2.0.0', '2023-05-30', date('now'));

COMMIT;

BEGIN TRANSACTION;

-- ## On corrige les valeurs pour le prénom et le nom
UPDATE utilisateur SET prenom='admin' WHERE courriel='admin@ma-moulinette.fr';
UPDATE utilisateur SET nom='@ma-moulinette.fr' WHERE courriel='admin@ma-moulinette.fr';

COMMIT;

BEGIN TRANSACTION;

-- ## On supprime ma table equipe
DROP TABLE IF EXISTS equipe;

-- ## Ajoute la table equipe
CREATE TABLE IF NOT EXISTS "equipe" (
	"id"	INTEGER NOT NULL,
	"titre"	VARCHAR(32) NOT NULL UNIQUE,
	"description"	VARCHAR(128) NOT NULL,
	"date_modification"	DATETIME DEFAULT NULL,
	"date_enregistrement"	DATETIME NOT NULL,
	UNIQUE("titre"),
	PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;

BEGIN TRANSACTION;

-- ## On supprime ma table equipe
DROP TABLE IF EXISTS portefeuille;

-- ## Ajout de la table portefeuille
CREATE TABLE IF NOT EXISTS portefeuille (
	"id"	INTEGER NOT NULL,
	"titre"	VARCHAR(32) NOT NULL UNIQUE,
	"equipe"	VARCHAR(32) NOT NULL,
	"liste"	CLOB NOT NULL,
	"date_modification"	DATETIME DEFAULT NULL,
	"date_enregistrement"	DATETIME NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;

BEGIN TRANSACTION;

-- ## Création de la table batch
CREATE TABLE IF NOT EXISTS batch (
	"id"	INTEGER NOT NULL,
	"statut"	BOOLEAN NOT NULL,
	"titre"	VARCHAR(32) NOT NULL UNIQUE,
	"description"	VARCHAR(128) NOT NULL,
	"responsable"	VARCHAR(128) NOT NULL,
	"portefeuille"	VARCHAR(32) NOT NULL UNIQUE,
	"nombre_projet"	INTEGER NOT NULL,
	"execution"	VARCHAR(16),
	"date_modification"	DATETIME DEFAULT NULL,
	"date_enregistrement"	DATETIME NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);

COMMIT;

BEGIN TRANSACTION;

-- ## Création de la table batch traitement

CREATE TABLE IF NOT EXISTS batch_traitement (
	"id"	INTEGER NOT NULL,
	"demarrage"	VARCHAR(16) NOT NULL,
	"resultat"	BOOLEAN NOT NULL,
	"titre"	VARCHAR(32) NOT NULL,
	"portefeuille"	VARCHAR(32) NOT NULL,
	"nombre_projet"	INTEGER NOT NULL,
	"responsable"	VARCHAR(128) NOT NULL,
	"debut_traitement"	DATETIME DEFAULT NULL,
	"fin_traitement"	DATETIME DEFAULT NULL,
	"date_enregistrement"	DATETIME NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);


BEGIN TRANSACTION;

-- ## Ajout d'une contrainte d'intégrité UNIQUE

CREATE UNIQUE INDEX "UNIQ_2449BA15FF7747B4" ON "equipe" ("titre");
CREATE UNIQUE INDEX "UNIQ_2955FFFEFF7747B4" ON "portefeuille" ("titre");
CREATE UNIQUE INDEX "UNIQ_F80B52D4FF7747B4" ON "batch" ("titre");
CREATE UNIQUE INDEX "UNIQ_F80B52D42955FFFE" ON "batch" ("portefeuille");

COMMIT;

BEGIN TRANSACTION;

-- ## Ajout de l'attribut version_autre dans la table historique
CREATE TEMPORARY TABLE "__temp__historique" AS SELECT
	"maven_key","version","date_version","nom_projet","version_release","version_snapshot",
	"suppress_warning","no_sonar","nombre_ligne","nombre_ligne_code","couverture","duplication","tests_unitaires","nombre_defaut",
	"nombre_bug","nombre_vulnerability","nombre_code_smell","frontend","backend","autre","dette","nombre_anomalie_bloquant",
	"nombre_anomalie_critique","nombre_anomalie_info","nombre_anomalie_majeur","nombre_anomalie_mineur","note_reliability",	"note_security",
	"note_sqale","note_hotspot","hotspot_high","hotspot_medium","hotspot_low","hotspot_total","favori","initial","bug_blocker",
	"bug_critical","bug_major","bug_minor","bug_info","vulnerability_blocker","vulnerability_critical","vulnerability_major",
	"vulnerability_minor","vulnerability_info","code_smell_blocker","code_smell_critical","code_smell_major","code_smell_minor",
	"code_smell_info","date_enregistrement" FROM historique;

DROP TABLE historique;

CREATE TABLE "historique" (
	"maven_key"	VARCHAR(128) NOT NULL,	"version"	VARCHAR(32) NOT NULL,	"date_version"	VARCHAR(128) NOT NULL,	"nom_projet"	VARCHAR(128) NOT NULL,
	"version_release"	INTEGER NOT NULL,	"version_snapshot"	INTEGER NOT NULL,	"version_autre"	INTEGER NOT NULL, "suppress_warning"	INTEGER NOT NULL,	"no_sonar"	INTEGER NOT NULL,
	"nombre_ligne"	INTEGER NOT NULL,	"nombre_ligne_code"	INTEGER NOT NULL,	"couverture"	DOUBLE PRECISION NOT NULL,	"duplication"	DOUBLE PRECISION NOT NULL,
	"tests_unitaires"	INTEGER NOT NULL,	"nombre_defaut"	INTEGER NOT NULL,	"nombre_bug"	INTEGER NOT NULL,	"nombre_vulnerability"	INTEGER NOT NULL,
	"nombre_code_smell"	INTEGER NOT NULL,	"frontend"	INTEGER NOT NULL,	"backend"	INTEGER NOT NULL,	"autre"	INTEGER NOT NULL,	"dette"	INTEGER NOT NULL,
	"nombre_anomalie_bloquant"	INTEGER NOT NULL,	"nombre_anomalie_critique"	INTEGER NOT NULL,	"nombre_anomalie_info"	INTEGER NOT NULL,
	"nombre_anomalie_majeur"	INTEGER NOT NULL,	"nombre_anomalie_mineur"	INTEGER NOT NULL,	"note_reliability"	VARCHAR(4) NOT NULL,
	"note_security"	VARCHAR(4) NOT NULL,	"note_sqale"	VARCHAR(4) NOT NULL,	"note_hotspot"	VARCHAR(4) NOT NULL, "hotspot_high"	INTEGER NOT NULL,
	"hotspot_medium"	INTEGER NOT NULL,	"hotspot_low"	INTEGER NOT NULL,	"hotspot_total"	INTEGER NOT NULL,	"favori"	BOOLEAN NOT NULL,
	"initial"	BOOLEAN NOT NULL,	"bug_blocker"	INTEGER NOT NULL,	"bug_critical"	INTEGER NOT NULL,	"bug_major"	INTEGER NOT NULL,
	"bug_minor"	INTEGER NOT NULL,	"bug_info"	INTEGER NOT NULL,	"vulnerability_blocker"	INTEGER NOT NULL,	"vulnerability_critical"	INTEGER NOT NULL,
	"vulnerability_major"	INTEGER NOT NULL,	"vulnerability_minor"	INTEGER NOT NULL,	"vulnerability_info"	INTEGER NOT NULL,
	"code_smell_blocker"	INTEGER NOT NULL,	"code_smell_critical"	INTEGER NOT NULL,	"code_smell_major"	INTEGER NOT NULL,
	"code_smell_minor"	INTEGER NOT NULL,	"code_smell_info"	INTEGER NOT NULL,	"date_enregistrement"	DATETIME NOT NULL,
	PRIMARY KEY("maven_key","version","date_version"));

	INSERT INTO historique (
	"maven_key","version","date_version","nom_projet","version_release","version_snapshot","version_autre", "suppress_warning","no_sonar","nombre_ligne",
	"nombre_ligne_code", "couverture","duplication", "tests_unitaires","nombre_defaut","nombre_bug","nombre_vulnerability","nombre_code_smell",
	"frontend","backend","autre","dette","nombre_anomalie_bloquant","nombre_anomalie_critique","nombre_anomalie_info","nombre_anomalie_majeur",
	"nombre_anomalie_mineur","note_reliability","note_security","note_sqale","note_hotspot","hotspot_high","hotspot_medium","hotspot_low",
	"hotspot_total","favori","initial","bug_blocker","bug_critical","bug_major","bug_minor","bug_info","vulnerability_blocker",
	"vulnerability_critical","vulnerability_major","vulnerability_minor","vulnerability_info","code_smell_blocker","code_smell_critical",
	"code_smell_major","code_smell_minor","code_smell_info","date_enregistrement")
	SELECT 	"maven_key","version","date_version","nom_projet","version_release","version_snapshot","version_autre","suppress_warning","no_sonar","nombre_ligne",
	"nombre_ligne_code", "couverture","duplication","tests_unitaires","nombre_defaut","nombre_bug","nombre_vulnerability","nombre_code_smell",
	"frontend", "backend","autre","dette","nombre_anomalie_bloquant","nombre_anomalie_critique","nombre_anomalie_info","nombre_anomalie_majeur",
	"nombre_anomalie_mineur","note_reliability","note_security","note_sqale","note_hotspot","hotspot_high","hotspot_medium","hotspot_low",
	"hotspot_total","favori","initial","bug_blocker","bug_critical","bug_major","bug_minor","bug_info","vulnerability_blocker",
	"vulnerability_critical","vulnerability_major","vulnerability_minor","vulnerability_info","code_smell_blocker","code_smell_critical",
	"code_smell_major","code_smell_minor","code_smell_info","date_enregistrement" FROM __temp__historique;

DROP TABLE __temp__historique;

COMMIT;

BEGIN TRANSACTION;

-- 2023-10-19 : Création de la table profiles_historique + ajout date_courte et langage

CREATE TABLE IF NOT EXISTS "profiles_historique" (
	"date_courte" DATETIME NOT NULL,
	"langage" VARCHAR(16) NOT NULL,
	"date"	DATETIME NOT NULL,
	"action"	VARCHAR(16) NOT NULL,
	"auteur"	VARCHAR(64) NOT NULL,
	"regle"	VARCHAR(128) NOT NULL,
	"description"	TEXTE NOT NULL,
	"detail"	CLOB,
	"date_enregistrement"	DATETIME NOT NULL,
	PRIMARY KEY("date","regle")
);

COMMIT;

BEGIN TRANSACTION;

-- ## On ajoute la colonne preference à la table utilisateur

ALTER TABLE utilisateur ADD COLUMN preference CLOB DEFAULT NULL;

-- ## On met à jour la colonne equipe et preference à []
UPDATE utilisateur
SET equipe = '[]'
WHERE equipe IS NULL;

UPDATE utilisateur
SET preference = '[]'
WHERE preference IS NULL;

COMMIT;

BEGIN TRANSACTION;

-- ## On ajoute la table des todo

CREATE TABLE todo (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  maven_key VARCHAR(128) NOT NULL,
  rule VARCHAR(128) NOT NULL,
  component CLOB NOT NULL,
  line INTEGER NOT NULL,
  date_enregistrement DATETIME NOT NULL);

COMMIT;

BEGIN TRANSACTION;

-- ## On ajoute la colonne todo à la table historique

ALTER TABLE historique ADD COLUMN todo INT DEFAULT 0;
UPDATE historique SET todo=0 WHERE todo IS NULL;

COMMIT;

BEGIN TRANSACTION;

-- ## On ajoute l'équipe AUCUNE

INSERT INTO utilisateur (titre, description, date_enregistrement)
VALUES ('AUCUNE', 'Personne ne m''aime !', '1980-01-01 00:00:00');

-- ## On ajoute à l'utilisateur admin l'équipe AUCUNE
UPDATE utilisateur SET equipe='["AUCUNE"]' WHERE courriel='admin@ma-moulinette.fr';

COMMIT;

BEGIN TRANSACTION;

-- ## On supprime la table liste_projet
DROP TABLE IF EXISTS liste_projet;

-- # On supprime la table tags
DROP TABLE IF EXISTS tags;

-- ## Ajout de la table liste_projet
CREATE TABLE IF NOT EXISTS liste_projet (
	"id"	INTEGER NOT NULL,
	"maven_key"	VARCHAR(128) NOT NULL,
	"name"	VARCHAR(64) NOT NULL,
	"tags"	CLOB NOT NULL,
	"visibility"	VARCHAR(8) NOT NULL,
	"date_enregistrement"	DATETIME NOT NULL,
	PRIMARY KEY("id" AUTOINCREMENT)
);

-- On supprime la colone anomalie.liste
ALTER TABLE anomalie DROP COLUMN liste;

-- On supprime la colone historique.favori
ALTER TABLE historique DROP COLUMN favori;

COMMIT;

BEGIN TRANSACTION;

-- 2023-10-11 : On supprime la table tags
DROP TABLE IF EXISTS favori;

COMMIT;

BEGIN TRANSACTION;

-- 2023-11-03 : Création de la table des analyses

CREATE TABLE IF NOT EXISTS "analyse" (
	"id"	INTEGER NOT NULL,
	"maven_key"	varchar(128),
	"project_name"	varchar(64),
	"analyse_id"	varchar(26),
	"status"	varchar(16),
	"submitted_at"	DATETIME,
	"submitter_login"	varchar(32),
	"started_at"	DATETIME,
	"executed_at"	DATETIME,
	"execution_time_ms"	INTEGER,
	PRIMARY KEY("id")
);

COMMIT;
