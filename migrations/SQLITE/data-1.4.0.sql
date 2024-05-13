BEGIN TRANSACTION;

-- ############### Evolutions 1.4.0 #####################
-- ## Migration 1.3.0 vers 1.4.0

-- ## Ajout de l'attribut liste dans la table anomalie avec comme valeur par défaut 1.
ALTER TABLE anomalie ADD COLUMN liste BOOLEAN DEFAULT 1 NOT NULL;

COMMIT;
