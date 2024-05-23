/*
####################################################
##                                                ##
##         Données d'initialisation du projet     ##
##               V1.0.0 - 14/05/2024              ##
##                                                ##
####################################################*/

BEGIN;

-- Insertion des versions dans le schema ma_moulinette et la table ma_moulinette avec les champs correctement ordonnés
INSERT INTO ma_moulinette ("version", date_version, date_enregistrement)
VALUES
('1.0.0', '2022-01-04', NOW()),
('1.1.0', '2022-04-24', NOW()),
('1.2.0', '2022-05-05', NOW()),
('1.2.6', '2022-06-02', NOW()),
('1.3.0', '2022-07-03', NOW()),
('1.4.0', '2022-07-06', NOW()),
('1.5.0-RC1', '2022-10-06', NOW()),
('1.5.0', '2022-10-12', NOW()),
('1.6.0', '2022-11-29', NOW()),
('2.0.0-RC1', '2022-12-13', NOW()),
('2.0.0-RC2', '2023-05-08', NOW()),
('2.0.0-RC3', '2024-04-22', NOW());


-- ## Ajout du compte admin
INSERT INTO utilisateur
(preference, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 1, 'admin@ma-moulinette.fr', '["ROLE_GESTIONNAIRE"]',
'$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K', 'Admin', '@ma-moulinette', '1980-01-01', true, 'chiffre/01.png', '["AUCUNE"]');

-- Insertion pour 'Aurélie PETIT COEUR'
INSERT INTO utilisateur
(preference, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 0, 'aurelie.petit-coeur@ma-moulinette.fr', '["ROLE_GESTIONNAIRE"]',
'$2y$13$HMk1rgFp5OiveduUd.dNXeaxq1y/HiActAv3hiMpAFCNsCjNHIFya', 'Aurélie', 'PETIT COEUR', NOW(), false, 'fille-1/05.png', '["AUCUNE"]');

-- Insertion pour 'Emma VAN DE BERG'
INSERT INTO utilisateur
(preference, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 0, 'emma.van-de-berg@ma-moulinette.fr', '["ROLE_BATCH"]',
'$2y$13$BrmmLZ3WiFwZcOllwh9zNOrjBRH9RSLEdLCW2y8by5CFX5zS.b1MG', 'Emma', 'VAN DE BERG', NOW(), false, 'fille-2/03.png', '["AUCUNE"]');

-- Insertion pour 'Nathan Jones'
INSERT INTO utilisateur
(preference, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 0, 'nathan.jones@ma-moulinette.fr', '["ROLE_COLLECTE"]',
'$2y$13$hwX0QJOw8fSgjiBq1CL/FuJsf4miOeLJRBw8jzt1WrsV/qLR.DxN.', 'Nathan', 'Jones', NOW(), false, 'garcon-1/05.png', '["AUCUNE"]');

-- Insertion pour 'Josh LIBERMAN'
INSERT INTO utilisateur
(preference, init, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, equipe)
VALUES
('{"statut":{"projet":false,"favori":false,"version":false,"bookmark":false},
"projet":[],"favori":[],"version":[],"bookmark":[]}', 0, 'josh.liberman@ma-moulinette.fr', '["ROLE_UTILISATEUR"]',
'$2y$13$ON.wYv3nmwkB9N3eOSubt.HFA46NjBHgyvOo6PBs3PVcCPtRb5MSa', 'Josh', 'LIBERMAN', NOW(), false, 'garcon-1/10.png', '["AUCUNE"]');


-- ## Ajout de l'équipe par défaut
INSERT INTO equipe (titre, description, date_enregistrement)
VALUES ('["AUCUNE"]', 'Personne ne m''aime !', '1980-01-01 00:00:00');

-- ## Ajout données Owasp 2017-2021
INSERT INTO owasp_top10 (year, category, description) values
(2017, 'A1 - Attaques d injection', 'Les failles d injection, telles que l injection SQL, NoSQL, OS et LDAP, se produisent lorsque des données non fiables sont envoyées à un interpréteur dans le cadre d une commande ou d une requête. Les données hostiles de l attaquant peuvent inciter l interpréteur à exécuter des commandes non souhaitées ou à accéder à des données sans autorisation appropriée.'),
(2017, 'A2 - Authentification défaillante', 'Les fonctions d application liées à l authentification et à la gestion des sessions sont souvent mises en œuvre de manière incorrecte, ce qui permet aux attaquants de compromettre les mots de passe, les clés ou les jetons de session, ou d exploiter d autres défauts de mise en œuvre pour prendre l identité d autres utilisateurs de manière temporaire ou permanente.'),
(2017, 'A3 - Fuites de données sensibles', 'De nombreuses applications Web et API ne protègent pas correctement les données sensibles, telles que les données financières, les données relatives à la santé et les informations nominatives. Les attaquants peuvent voler ou modifier ces données faiblement protégées pour commettre des fraudes à la carte de crédit, des vols d identité ou d autres délits. Les données sensibles peuvent être compromises sans protection supplémentaire, comme le cryptage au repos ou en transit, et nécessitent des précautions particulières lorsqu elles sont échangées avec le navigateur.'),
(2017, 'A4 - Entités externes XML (XXE)', 'De nombreux processeurs XML anciens ou mal configurés évaluent les références à des entités externes dans les documents XML. Les entités externes peuvent être utilisées pour divulguer des fichiers internes à l aide du gestionnaire URI de fichiers, des partages de fichiers internes, l analyse de ports internes, l exécution de code à distance et des attaques par déni de service.'),
(2017, 'A5 - Contrôle d accès défaillant', 'Les restrictions sur ce que les utilisateurs authentifiés sont autorisés à faire ne sont souvent pas correctement appliquées. Les attaquants peuvent exploiter ces failles pour accéder à des fonctionnalités et/ou des données non autorisées, comme l accès aux comptes d autres utilisateurs, la visualisation de fichiers sensibles, la modification des données d autres utilisateurs, la modification des droits d accès, etc.'),
(2017, 'A6 - Configurations défaillantes', 'La mauvaise configuration de la sécurité est le problème le plus fréquemment rencontré. Elle résulte généralement de configurations par défaut non sécurisées, de configurations incomplètes ou ad hoc, d un stockage en nuage ouvert, d en-têtes HTTP mal configurés et de messages d erreur verbeux contenant des informations sensibles. Non seulement tous les systèmes d exploitation, les frameworks, les bibliothèques et les applications doivent être configurés de manière sécurisée, mais ils doivent également faire l objet de correctifs ou de mises à jour en temps utile.'),
(2017, 'A7 - Attaques cross-site scripting (XSS)', 'Les failles XSS se produisent chaque fois qu une application inclut des données non fiables dans une nouvelle page Web sans validation ni échappement appropriés, ou met à jour une page Web existante avec des données fournies par l utilisateur en utilisant une API de navigateur capable de créer du HTML ou du JavaScript. Le XSS permet aux attaquants d exécuter des scripts dans le navigateur de la victime, ce qui peut détourner les sessions de l utilisateur, défigurer les sites Web ou rediriger l utilisateur vers des sites malveillants.'),
(2017, 'A8 - Désérialisation sans validation', 'Une désérialisation non sécurisée conduit souvent à l exécution de code à distance. Même si les défauts de désérialisation n entraînent pas l exécution de code à distance, ils peuvent être utilisés pour réaliser des attaques, notamment des attaques par rejeu, des attaques par injection et des attaques par élévation de privilèges.'),
(2017, 'A9 - Composants tiers vulnérables', 'Les composants, tels que les bibliothèques, les frameworks et autres modules logiciels, s exécutent avec les mêmes privilèges que l application. Si un composant vulnérable est exploité, une telle attaque peut faciliter de graves pertes de données ou la prise de contrôle du serveur. Les applications et les API utilisant des composants présentant des vulnérabilités connues peuvent miner les défenses des applications et permettre diverses attaques et incidences.'),
(2017, 'A10 - Journalisation et surveillance insuffisantes', 'L insuffisance de la journalisation et de la surveillance, associée à l absence ou à l inefficacité de l intégration avec la réponse aux incidents, permet aux attaquants d attaquer davantage les systèmes, de maintenir la persistance, de pivoter vers d autres systèmes et d altérer, d extraire ou de détruire des données. La plupart des études sur les brèches montrent que le délai de détection d une brèche est supérieur à 200 jours, généralement détectée par des parties externes plutôt que par des processus ou une surveillance internes.'),
(2021, 'A1 - Contrôle d accès défaillant', 'Les restrictions sur ce que les utilisateurs authentifiés sont autorisés à faire ne sont souvent pas correctement appliquées. Les attaquants peuvent exploiter ces failles pour accéder à des fonctionnalités et/ou des données non autorisées, comme l accès aux comptes d autres utilisateurs, la visualisation de fichiers sensibles, la modification des données d autres utilisateurs, la modification des droits d accès, etc.'),
(2021, 'A2 - Défaillances cryptographiques', 'De nombreuses applications Web et API ne protègent pas correctement les données sensibles, telles que les données financières, les données relatives à la santé et les informations nominatives. Les attaquants peuvent voler ou modifier ces données faiblement protégées pour commettre des fraudes à la carte de crédit, des vols d identité ou d autres délits. Les données sensibles peuvent être compromises sans protection supplémentaire, comme le cryptage au repos ou en transit, et nécessitent des précautions particulières lorsqu elles sont échangées avec le navigateur.'),
(2021, 'A3 - Injection', 'Les failles d injection, telles que l injection SQL, NoSQL, OS et LDAP, se produisent lorsque des données non fiables sont envoyées à un interpréteur dans le cadre d une commande ou d une requête. Les données hostiles de l attaquant peuvent inciter l interpréteur à exécuter des commandes non souhaitées ou à accéder à des données sans autorisation appropriée.'),
(2021, 'A4 - Conception non sécurisée', 'La mauvaise configuration de la sécurité est le problème le plus fréquemment rencontré. Elle résulte généralement de configurations par défaut non sécurisées, de configurations incomplètes ou ad hoc, d un stockage en nuage ouvert, d en-têtes HTTP mal configurés et de messages d erreur verbeux contenant des informations sensibles. Non seulement tous les systèmes d exploitation, les frameworks, les bibliothèques et les applications doivent être configurés de manière sécurisée, mais ils doivent également faire l objet de correctifs ou de mises à jour en temps utile.'),
(2021, 'A5 - Mauvaise configuration de sécurité', 'Les composants, tels que les bibliothèques, les frameworks et autres modules logiciels, s exécutent avec les mêmes privilèges que l application. Si un composant vulnérable est exploité, une telle attaque peut faciliter de graves pertes de données ou la prise de contrôle du serveur. Les applications et les API utilisant des composants présentant des vulnérabilités connues peuvent miner les défenses des applications et permettre diverses attaques et incidences.'),
(2021, 'A6 - Composants vulnérables et obsolètes', 'Les fonctions d application liées à l authentification et à la gestion des sessions sont souvent mises en œuvre de manière incorrecte, ce qui permet aux attaquants de compromettre les mots de passe, les clés ou les jetons de session, ou d exploiter d autres défauts de mise en œuvre pour prendre l identité d autres utilisateurs de manière temporaire ou permanente.'),
(2021, 'A7 - Identification et authentification de mauvaise qualité', 'Les failles XSS se produisent chaque fois qu une application inclut des données non fiables dans une nouvelle page Web sans validation ni échappement appropriés, ou met à jour une page Web existante avec des données fournies par l utilisateur en utilisant une API de navigateur capable de créer du HTML ou du JavaScript. Le XSS permet aux attaquants d exécuter des scripts dans le navigateur de la victime, ce qui peut détourner les sessions de l utilisateur, défigurer les sites Web ou rediriger l utilisateur vers des sites malveillants.'),
(2021, 'A8 - Manque d intégrité des données et du logiciel', 'Une désérialisation non sécurisée conduit souvent à l exécution de code à distance. Même si les défauts de désérialisation n entraînent pas l exécution de code à distance, ils peuvent être utilisés pour réaliser des attaques, notamment des attaques par rejeu, des attaques par injection et des attaques par élévation de privilèges.'),
(2021, 'A9 - Carence des systèmes de contrôle et de journalisation', 'L insuffisance de la journalisation et de la surveillance, associée à l absence ou à l inefficacité de l intégration avec la réponse aux incidents, permet aux attaquants d attaquer davantage les systèmes, de maintenir la persistance, de pivoter vers d autres systèmes et d altérer, d extraire ou de détruire des données. La plupart des études sur les brèches montrent que le délai de détection d une brèche est supérieur à 200 jours, généralement détectée par des parties externes plutôt que par des processus ou une surveillance internes.');
(2021, 'A10 - Falsification de requête côté serveur (SSRF)', 'Les failles de fuite de données et de logiciels sont des failles graves permettant aux attaquants de voler des informations sensibles.');

COMMIT;
