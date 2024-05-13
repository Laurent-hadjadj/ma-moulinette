BEGIN;

-- Insertion des versions dans le schema ma_moulinette et la table ma_moulinette avec les champs correctement ordonnés
INSERT INTO ma_moulinette.ma_moulinette (id, "version", date_version, date_enregistrement)
VALUES
(0, '1.0.0', '2022-01-04', NOW()),
(1, '1.1.0', '2022-04-24', NOW()),
(2, '1.2.0', '2022-05-05', NOW()),
(3, '1.2.6', '2022-06-02', NOW()),
(4, '1.3.0', '2022-07-03', NOW()),
(5, '1.4.0', '2022-07-06', NOW()),
(6, '1.5.0-RC1', '2022-10-06', NOW()),
(7, '1.5.0', '2022-10-12', NOW()),
(8, '1.6.0', '2022-11-29', NOW()),
(9, '2.0.0-RC1', '2022-12-13', NOW()),
(10, '2.0.0-RC2', '2023-05-08', NOW()),
(11, '2.0.0-RC3', '2024-04-22', NOW());


-- ## Ajout du compte admin
INSERT INTO ma_moulinette.utilisateur
(preference, id, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 1, 1, 'admin@ma-moulinette.fr', '["ROLE_GESTIONNAIRE"]',
'$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K', 'Admin', '@ma-moulinette', '1980-01-01', true, 'chiffre/01.png', '[]');

-- Insertion pour 'Aurélie PETIT COEUR'
INSERT INTO ma_moulinette.utilisateur
(preference, id, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 2, 0, 'aurelie.petit-coeur@ma-moulinette.fr', '["ROLE_GESTIONNAIRE"]',
'$2y$13$HMk1rgFp5OiveduUd.dNXeaxq1y/HiActAv3hiMpAFCNsCjNHIFya', 'Aurélie', 'PETIT COEUR', NOW(), false, 'fille-1/05.png', '[]');

-- Insertion pour 'Emma VAN DE BERG'
INSERT INTO ma_moulinette.utilisateur
(preference, id, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 3, 0, 'emma.van-de-berg@ma-moulinette.fr', '["ROLE_BATCH"]',
'$2y$13$BrmmLZ3WiFwZcOllwh9zNOrjBRH9RSLEdLCW2y8by5CFX5zS.b1MG', 'Emma', 'VAN DE BERG', NOW(), false, 'fille-2/03.png', '[]');

-- Insertion pour 'Nathan Jones'
INSERT INTO ma_moulinette.utilisateur
(preference, id, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 4, 0, 'nathan.jones@ma-moulinette.fr', '["ROLE_COLLECTE"]',
'$2y$13$hwX0QJOw8fSgjiBq1CL/FuJsf4miOeLJRBw8jzt1WrsV/qLR.DxN.', 'Nathan', 'Jones', NOW(), false, 'garcon-1/05.png', '[]');

-- Insertion pour 'Josh LIBERMAN'
INSERT INTO ma_moulinette.utilisateur
(preference, id, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 5, 0, 'josh.liberman@ma-moulinette.fr', '["ROLE_UTILISATEUR"]',
'$2y$13$ON.wYv3nmwkB9N3eOSubt.HFA46NjBHgyvOo6PBs3PVcCPtRb5MSa', 'Josh', 'LIBERMAN', NOW(), false, 'garcon-1/10.png', '[]');


-- ## Ajout de l'équipe par défaut
INSERT INTO ma_moulinette.equipe (id, titre, description, date_enregistrement)
VALUES (1, '["AUCUNE"]', 'Personne ne m''aime !', '1980-01-01 00:00:00');

COMMIT;
