BEGIN TRANSACTION;

-- ############### Evolutions 1.1.0 #####################
-- ## Migration 1.0.0 vers 1.1.0

-- ## Supression des tables
DROP TABLE anomalie_details;
DROP TABLE temp_anomalie;

-- ## Ajout de la table anomalie_details
CREATE TABLE IF NOT EXISTS anomalie_details
  (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    maven_key VARCHAR (128) NOT NULL,
    name VARCHAR (128) NOT NULL,
    bug_blocker INTEGER NOT NULL,
    bug_critical INTEGER NOT NULL,
    bug_info INTEGER NOT NULL,
    bug_major INTEGER NOT NULL,
    bug_minor INTEGER NOT NULL,
    vulnerability_blocker INTEGER NOT NULL,
    vulnerability_critical INTEGER NOT NULL,
    vulnerability_info INTEGER NOT NULL,
    vulnerability_major INTEGER NOT NULL,
    vulnerability_minor INTEGER NOT NULL,
    code_smell_blocker INTEGER NOT NULL,
    code_smell_critical INTEGER NOT NULL,
    code_smell_info INTEGER NOT NULL,
    code_smell_major INTEGER NOT NULL,
    code_smell_minor INTEGER NOT NULL,
    date_enregistrement DATETIME NOT NULL);

-- ## Modificaton de table historique
ALTER TABLE historique ADD COLUMN bug_blocker INTEGER;
ALTER TABLE historique ADD COLUMN bug_critical INTEGER;
ALTER TABLE historique ADD COLUMN bug_major INTEGER;
ALTER TABLE historique ADD COLUMN bug_minor INTEGER;
ALTER TABLE historique ADD COLUMN bug_info INTEGER;
ALTER TABLE historique ADD COLUMN vulnerability_blocker INTEGER;
ALTER TABLE historique ADD COLUMN vulnerability_critical INTEGER;
ALTER TABLE historique ADD COLUMN vulnerability_major INTEGER;
ALTER TABLE historique ADD COLUMN vulnerability_minor INTEGER;
ALTER TABLE historique ADD COLUMN vulnerability_info INTEGER;
ALTER TABLE historique ADD COLUMN code_smell_blocker INTEGER;
ALTER TABLE historique ADD COLUMN code_smell_critical INTEGER;
ALTER TABLE historique ADD COLUMN code_smell_major INTEGER;
ALTER TABLE historique ADD COLUMN code_smell_minor INTEGER;
ALTER TABLE historique ADD COLUMN code_smell_info INTEGER;

COMMIT;
