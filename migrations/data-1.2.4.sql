BEGIN TRANSACTION;

-- ############### Evolutions 1.2.4 #####################
-- ## Migration 1.1.0 vers 1.2.4

-- ## Remplacement du nom de la colonne batch par autre
ALTER TABLE anomalie RENAME batch TO autre;
ALTER TABLE historique RENAME batch TO autre;
ALTER TABLE hotspot_details RENAME batch TO autre;

-- ## Ajout de la colonne niveau à la table hotspot_details
ALTER TABLE hotspot_details ADD COLUMN niveau INTERGER;

-- ## Mise à jour de la colonne niveau
UPDATE hotspot_details SET niveau=1 WHERE severity='HIGH';
UPDATE hotspot_details SET niveau=2 WHERE severity='MEDIUM';
UPDATE hotspot_details SET niveau=3 WHERE severity='LOW';

COMMIT;
