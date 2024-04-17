/*
 * File: fixtures-postinstall.sql
 * Project: ma-moulinette
 *
 * File Created: Wednesday, 30th November 2022 12:45:06 pm
 * Laurent HADJADJ <laurent_h@me.com>.
 * Licensed Creative Common  CC-BY-NC-SA 4.0.
 * ---
 * Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 * http://creativecommons.org/licenses/by-nc-sa/4.0/
 * ----------------------------------------------------------------------
 * Last Modified: Mon May 08 2023
 * Modified By: HADJADJ Laurent
 * ----------------------------------------------------------------------
 * HISTORY:
 * Date      	By	Comments
 * ----------	---	-----------------------------------------------------
 */

BEGIN TRANSACTION;

-- 2024-04-16 : réinitialisation des séquences pour le shéma data

UPDATE sqlite_sequence SET seq = 0 WHERE seq > 0;

-- ## Initialise la table des versions. 0 (false) and 1 (true).
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.0.0', '2022-01-04', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.1.0', '2022-04-24', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.2.0', '2022-05-05', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.2.6', '2022-06-02', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.3.0', '2022-07-03', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.4.0', '2022-07-06', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.5.0-RC1', '2022-10-06', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.5.0', '2022-10-12', date('now'));
-- ## Ajout de la version 1.6.0 dans la table ma_moulinette
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.6.0', '2022-11-29', date('now'));
-- ## Ajout de la version 2.0.0-RC1 dans la table ma_moulinette
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('2.0.0-RC1', '2022-12-13', date('now'));
-- ## Ajout de la version 2.0.0 dans la table ma_moulinette
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('2.0.0-RC2', '2023-05-08', date('now'));
-- ## Ajout de la version 2.0.0 dans la table ma_moulinette
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('2.0.0-RC3', '2024-04-22', date('now'));

-- ## Ajout du compte admin
INSERT INTO utilisateur
(init, courriel, roles,
  password,
  prenom, nom, date_enregistrement, actif, avatar,
  equipe)
VALUES
(1,'admin@ma-moulinette.fr','["ROLE_GESTIONNAIRE"]',
'$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K',
'admin','@ma-moulinette','1980-01-01 00:00:00',1,'chiffre/01.png',
'[]');

-- ## Ajout des comptes de tests (bcrypt)
INSERT INTO utilisateur
(courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('aurelie.petit-coeur@ma-moulinette.fr','["ROLE_GESTIONNAIRE"]',
'$2y$13$HMk1rgFp5OiveduUd.dNXeaxq1y/HiActAv3hiMpAFCNsCjNHIFya',
'Aurélie', 'PETIT COEUR','1980-01-01 00:00:00', 0,'fille-1/05.png',
'[]');

INSERT INTO utilisateur
(courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('emma.van-de-berg@ma-moulinette.fr','["ROLE_BATCH"]',
'$2y$13$BrmmLZ3WiFwZcOllwh9zNOrjBRH9RSLEdLCW2y8by5CFX5zS.b1MG',
'Emma', 'VAN DE BERG','1980-01-01 00:00:00', 0,'fille-2/03.png',
'[]');

INSERT INTO utilisateur
(courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('nathan.jones@ma-moulinette.fr','["ROLE_COLLECTE"]',
'$2y$13$hwX0QJOw8fSgjiBq1CL/FuJsf4miOeLJRBw8jzt1WrsV/qLR.DxN.',
'Nathan', 'Jones','1980-01-01 00:00:00', 0,'garcon-1/05.png',
'[]');

INSERT INTO utilisateur
(courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('josh.liberman@ma-moulinette.fr','["ROLE_UTILISATEUR"]',
'$2y$13$ON.wYv3nmwkB9N3eOSubt.HFA46NjBHgyvOo6PBs3PVcCPtRb5MSa',
'Josh', 'LIBERMAN','1980-01-01 00:00:00', 0,'garcon-1/10.png',
'[]');


-- ## Ajout de l'équipe par défaut
INSERT INTO equipe (titre, description, date_enregistrement)
VALUES ('["AUCUNE"]', 'Personne ne m''aime !', '1980-01-01 00:00:00');

-- ## On met à jour la colonne equipe et preference
UPDATE utilisateur
SET equipe = '[]'
WHERE equipe IS NULL or equipe = "";

UPDATE utilisateur
SET preference = '{
"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}'
WHERE preference IS NULL OR preference = "";

COMMIT;
