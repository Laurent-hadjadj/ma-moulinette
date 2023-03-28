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
 * Last Modified: Tue Feb 28 2023
 * Modified By: HADJADJ Laurent
 * ----------------------------------------------------------------------
 * HISTORY:
 * Date      	By	Comments
 * ----------	---	-----------------------------------------------------
 */
BEGIN TRANSACTION;

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

INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.5.0-RC1', '2022-10-06', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('1.5.0', '2022-10-12', date('now'));
-- ## Ajout de la version 1.6.0 dans la table ma_moulinette
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('2.0.0-RC1', '2022-12-13', date('now'));
INSERT INTO ma_moulinette (version, date_version, date_enregistrement) VALUES ('2.0.0', '2023-01-01', date('now'));


-- ## Ajout du compte admin
INSERT INTO utilisateur
(courriel, roles,  password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('admin@ma-moulinette.fr',
'["ROLE_GESTIONNAIRE"]',
'$2y$10$g1KdFM/ARBc7DG0UClLOl./4Cv.urhltS8zWPtOVzq78qkcSjliGa',
'admin',
'@ma-moulinette',
'1980-01-01 00:00:00',
1,
'chiffre/01.png',
'');

-- ## Ajout des comptes de tests
INSERT INTO utilisateur
(courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('aurelie.petit-coeur@ma-moulinette.fr','["ROLE_GESTIONNAIRE"]',
'$2y$13$O81lc.HQTtb5oJSEt.8PLuvlMgOGTgGKiLYdmn/xMP3j4Kq2yIA52',
'Aurélie', 'PETIT-COEUR','1980-01-01 00:00:00', 0,'fille-1/05.png',
'');

INSERT INTO utilisateur
(courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('josh.liberman@ma-moulinette.fr','["ROLE_UTILISATEUR"]',
'$2y$13$Xkrgtpq3bejvCdfd7qLvhujDT9XpgbxRLGEA0FoOfZGPmgW0ADBpS',
'Josh', 'LIBERMAN','1980-01-01 00:00:00', 0,'garcon-2/08.png',
'');

INSERT INTO utilisateur
(courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('emma.van-de-berg@ma-moulinette.fr','["ROLE_BATCH"]',
'$2y$13$JIieJW5yYYe7NHeYcjzS0OpGNkZ5Lc1klrQZL3K0ZN4AUieasKpiG',
'Emma', 'VAN DE BERG','1980-01-01 00:00:00', 0,'fille-2/03.png',
'');

-- ## Ajout de l'équipe par défaut
INSERT INTO utilisateur (titre, description, date_enregistrement, )
VALUES ('AUCUNE', 'Personne ne m''aime !', '1980-01-01 00:00:00');

COMMIT;
