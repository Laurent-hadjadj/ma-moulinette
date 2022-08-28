BEGIN TRANSACTION;

-- ############### Evolutions 1.3.0 #####################
-- ## Migration 1.2.4 vers 1.3.0

-- ## Ajout de la table repartition
CREATE TABLE repartition (
  id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
  maven_key VARCHAR(128) NOT NULL, name VARCHAR(128) NOT NULL,
  component CLOB NOT NULL, type VARCHAR(16) NOT NULL,
  severity VARCHAR(8) NOT NULL, setup UNSIGNED BIG INT NOT NULL,
  date_enregistrement DATETIME NOT NULL);

COMMIT;
