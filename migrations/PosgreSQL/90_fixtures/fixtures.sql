/*
####################################################
##                                                ##
##         Données d'initialisation du projet     ##
##               V1.4.0 - 13/04/2026              ##
##                                                ##
####################################################*/

/* #### Le script doit être lancé avec l'utilisateur propriétaire de la base, ici db_user #### */

--- 2024-05-22 : Laurent HADJADJ - Modification du nom de l'équipe -> ["AUCUNE"] en AUCUNE
--- 2024-05-28 : Laurent HADJADJ - Remplacement de NOW() par '1980-01-01 00:00:00'
--- 2024-11-19 : Laurent HADJADJ - Reprise des insert de Zakaria pour les référentiels OWASP 2017 et 2021.
--- 2025-02-03 : Laurent HADJADJ - Ajout du nom du schema en préfixe des tables.
--- 2025-07-22 : Laurent HADJADJ - Utilisation de true/false au lieu de 0/1 pour reset_password.
--- 2026-04-05 : Laurent HADJADJ - Ajout de la colonne groupe_id et liste_groupe_fonctionnel dans la table utilisateur.
--- 2026-04-13 : Ajout du support pour OWASP 2025.

\c ma_moulinette db_user

BEGIN;

-- =====================================================================
-- Table : ma_moulinette.ma_moulinette
-- =====================================================================

INSERT INTO ma_moulinette.ma_moulinette (version, date_version, date_enregistrement)
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
('2.0.0', '2026-03-31', NOW());

-- =====================================================================
-- Table : ma_moulinette.utilisateur
-- =====================================================================

-- ADMIN
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, liste_groupe_fonctionnel, groupe_utilisateur, groupe_id)
VALUES (
'{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false,"bookmark":false},
"suivi_projet":[],"favori_projet":[],"favori_version":[]}',
false,
'admin@ma-moulinette.fr',
'["ROLE_INTERNAL"]',
'$2y$13$6n72QhYwz.iufebkV.XaAOO4IOm3zOYcfzPUmal.jDTs8/QFq1p4K',
'Admin',
'@ma-moulinette',
'1980-01-01 00:00:00',
true,
'chiffre/01.png',
'["@AUCUN"]',
'Aucun',
'12345678901234567890123456'
);

-- AURÉLIE PETIT COEUR
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, liste_groupe_fonctionnel, groupe_utilisateur, groupe_id)
VALUES (
'{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false,"bookmark":false},
"suivi_projet":[],"favori_projet":[],"favori_version":[]}',
true,
'aurelie.petit-coeur@ma-moulinette.fr',
'["ROLE_GESTIONNAIRE"]',
'$2y$13$HMk1rgFp5OiveduUd.dNXeaxq1y/HiActAv3hiMpAFCNsCjNHIFya',
'Aurélie',
'PETIT COEUR',
'1980-01-01 00:00:00',
false,
'fille-1/05.png',
'["@AUCUN"]',
'En attente',
'00000000000000000000000000'
);

-- EMMA VAN DE BERG
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, liste_groupe_fonctionnel, groupe_utilisateur, groupe_id)
VALUES (
'{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false,"bookmark":false},
"suivi_projet":[],"favori_projet":[],"favori_version":[]}',
true,
'emma.van-de-berg@ma-moulinette.fr',
'["ROLE_BATCH"]',
'$2y$13$BrmmLZ3WiFwZcOllwh9zNOrjBRH9RSLEdLCW2y8by5CFX5zS.b1MG',
'Emma',
'VAN DE BERG',
'1980-01-01 00:00:00',
false,
'fille-2/03.png',
'["@AUCUN"]',
'En attente',
'00000000000000000000000000'
);

-- NATHAN JONES
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, liste_groupe_fonctionnel, groupe_utilisateur, groupe_id)
VALUES (
'{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false,"bookmark":false},
"suivi_projet":[],"favori_projet":[],"favori_version":[]}',
true,
'nathan.jones@ma-moulinette.fr',
'["ROLE_COLLECTE"]',
'$2y$13$hwX0QJOw8fSgjiBq1CL/FuJsf4miOeLJRBw8jzt1WrsV/qLR.DxN.',
'Nathan',
'Jones',
'1980-01-01 00:00:00',
false,
'garcon-1/05.png',
'["@AUCUN"]',
'En attente',
'00000000000000000000000000'
);

-- JOSH LIBERMAN
INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar, liste_groupe_fonctionnel, groupe_utilisateur, groupe_id)
VALUES (
'{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false,"bookmark":false},
"suivi_projet":[],"favori_projet":[],"favori_version":[]}',
true,
'josh.liberman@ma-moulinette.fr',
'["ROLE_UTILISATEUR"]',
'$2y$13$ON.wYv3nmwkB9N3eOSubt.HFA46NjBHgyvOo6PBs3PVcCPtRb5MSa',
'Josh',
'LIBERMAN',
'1980-01-01 00:00:00',
false,
'garcon-1/10.png',
'["@AUCUN"]',
'En attente',
'00000000000000000000000000'
);

-- =====================================================================
-- Groupe par défaut
-- =====================================================================

INSERT INTO ma_moulinette.groupe_utilisateur (groupe_utilisateur, groupe_id, description, date_enregistrement)
VALUES ('En attente', '00000000000000000000000000', 'Utilisateur en attente d’affectation.', '1980-01-01 00:00:00');
INSERT INTO ma_moulinette.groupe_utilisateur (groupe_utilisateur, groupe_id, description, date_enregistrement)
VALUES ('Aucun', '11111111111111111111111111', 'Aucun groupe.', '1980-01-01 00:00:00');

-- =====================================================================
-- OWASP 2027-2021
-- =====================================================================
INSERT INTO  ma_moulinette.owasp_top10 (year, category, description, lien) VALUES
(2017, 'A1 - Attaques d’injection', 'Les failles d’injection, telles que l’injection SQL, NoSQL, OS et LDAP, se produisent lorsque des données non fiables sont envoyées à un interpréteur dans le cadre d’une commande ou d’une requête. Les données hostiles de l’attaquant peuvent inciter l’interpréteur à exécuter des commandes non souhaitées ou à accéder à des données sans autorisation appropriée.','__a01-2017-injection.html.twig'),
(2017, 'A2 - Authentification défaillante', 'Les fonctions d’application liées à l’authentification et à la gestion des sessions sont souvent mises en œuvre de manière incorrecte, ce qui permet aux attaquants de compromettre les mots de passe, les clés ou les jetons de session, ou d’exploiter d’autres défauts de mise en œuvre pour prendre l’identité d’autres utilisateurs de manière temporaire ou permanente.','__a02-2017-broken_authentication.html.twig'),
(2017, 'A3 - Fuites de données sensibles', 'De nombreuses applications Web et API ne protègent pas correctement les données sensibles, telles que les données financières, les données relatives à la santé et les informations nominatives. Les attaquants peuvent voler ou modifier ces données faiblement protégées pour commettre des fraudes à la carte de crédit, des vols d’identité ou d’autres délits. Les données sensibles peuvent être compromises sans protection supplémentaire, comme le cryptage au repos ou en transit, et nécessitent des précautions particulières lorsqu’elles sont échangées avec le navigateur.','__a03-2017-sensitive_data_disclosure.html.twig'),
(2017, 'A4 - Entités externes XML (XXE)', 'De nombreux processeurs XML anciens ou mal configurés évaluent les références à des entités externes dans les documents XML. Les entités externes peuvent être utilisées pour divulguer des fichiers internes à l’aide du gestionnaire URI de fichiers, des partages de fichiers internes, l’analyse de ports internes, l’exécution de code à distance et des attaques par déni de service.','__a04-2017-xxe.html.twig'),
(2017, 'A5 - Contrôle d’accès défaillant', 'Les restrictions sur ce que les utilisateurs authentifiés sont autorisés à faire ne sont souvent pas correctement appliquées. Les attaquants peuvent exploiter ces failles pour accéder à des fonctionnalités et/ou des données non autorisées, comme l’accès aux comptes d’autres utilisateurs, la visualisation de fichiers sensibles, la modification des données d’autres utilisateurs, la modification des droits d’accès, etc.','__a05-2017-broken_access_control.html.twig'),
(2017, 'A6 - Configurations défaillantes', 'La mauvaise configuration de la sécurité est le problème le plus fréquemment rencontré. Elle résulte généralement de configurations par défaut non sécurisées, de configurations incomplètes ou ad hoc, d’un stockage en nuage ouvert, d’en-têtes HTTP mal configurés et de messages d’erreur verbeux contenant des informations sensibles. Non seulement tous les systèmes d’exploitation, les frameworks, les bibliothèques et les applications doivent être configurés de manière sécurisée, mais ils doivent également faire l’objet de correctifs ou de mises à jour en temps utile.','__a06-2017-security_misconfiguration.html.twig'),
(2017, 'A7 - Attaques cross-site scripting (XSS)', 'Les failles XSS se produisent chaque fois qu’une application inclut des données non fiables dans une nouvelle page Web sans validation ni échappement appropriés, ou met à jour une page Web existante avec des données fournies par l’utilisateur en utilisant une API de navigateur capable de créer du HTML ou du JavaScript. Le XSS permet aux attaquants d’exécuter des scripts dans le navigateur de la victime, ce qui peut détourner les sessions de l’utilisateur, défigurer les sites Web ou rediriger l’utilisateur vers des sites malveillants.','__a07-2017-xss.html.twig'),
(2017, 'A8 - Désérialisation sans validation', 'Une désérialisation non sécurisée conduit souvent à l’exécution de code à distance. Même si les défauts de désérialisation n’entraînent pas l’exécution de code à distance, ils peuvent être utilisés pour réaliser des attaques, notamment des attaques par rejeu, des attaques par injection et des attaques par élévation de privilèges.','__a08-2017-insecure_deserialization.html.twig'),
(2017, 'A9 - Composants tiers vulnérables', 'Les composants, tels que les bibliothèques, les frameworks et autres modules logiciels, s’exécutent avec les mêmes privilèges que l’application. Si un composant vulnérable est exploité, une telle attaque peut faciliter de graves pertes de données ou la prise de contrôle du serveur. Les applications et les API utilisant des composants présentant des vulnérabilités connues peuvent miner les défenses des applications et permettre diverses attaques et incidences.','__a09-2017-known_vulns.html.twig'),
(2017, 'A10 - Journalisation et surveillance insuffisantes', 'L’insuffisance de la journalisation et de la surveillance, associée à l’absence ou à l’inefficacité de l’intégration avec la réponse aux incidents, permet aux attaquants d’attaquer davantage les systèmes, de maintenir la persistance, de pivoter vers d’autres systèmes et d’altérer, d’extraire ou de détruire des données. La plupart des études sur les brèches montrent que le délai de détection d’une brèche est supérieur à 200 jours, généralement détectée par des parties externes plutôt que par des processus ou une surveillance internes.','__a10-2017-logging_detection_response.html.twig'),
(2021, 'A1 - Contrôle d’accès défaillant', 'Les restrictions sur ce que les utilisateurs authentifiés sont autorisés à faire ne sont souvent pas correctement appliquées. Les attaquants peuvent exploiter ces failles pour accéder à des fonctionnalités et/ou des données non autorisées, comme l’accès aux comptes d’autres utilisateurs, la visualisation de fichiers sensibles, la modification des données d’autres utilisateurs, la modification des droits d’accès, etc.', '__a01-2021-Broken_Access_Control.html.twig'),
(2021, 'A2 - Défaillances cryptographiques', 'De nombreuses applications Web et API ne protègent pas correctement les données sensibles, telles que les données financières, les données relatives à la santé et les informations nominatives. Les attaquants peuvent voler ou modifier ces données faiblement protégées pour commettre des fraudes à la carte de crédit, des vols d’identité ou d’autres délits. Les données sensibles peuvent être compromises sans protection supplémentaire, comme le cryptage au repos ou en transit, et nécessitent des précautions particulières lorsqu’elles sont échangées avec le navigateur.', '__a02-2021-cryptographic_failures.html.twig'),
(2021, 'A3 - Injection', 'Les failles d’injection, telles que l’injection SQL, NoSQL, OS et LDAP, se produisent lorsque des données non fiables sont envoyées à un interpréteur dans le cadre d’une commande ou d’une requête. Les données hostiles de l’attaquant peuvent inciter l’interpréteur à exécuter des commandes non souhaitées ou à accéder à des données sans autorisation appropriée.','__a03-2021-injection.html.twig'),
(2021, 'A4 - Conception non sécurisée', 'La mauvaise configuration de la sécurité est le problème le plus fréquemment rencontré. Elle résulte généralement de configurations par défaut non sécurisées, de configurations incomplètes ou ad hoc, d’un stockage en nuage ouvert, d’en-têtes HTTP mal configurés et de messages d’erreur verbeux contenant des informations sensibles. Non seulement tous les systèmes d’exploitation, les frameworks, les bibliothèques et les applications doivent être configurés de manière sécurisée, mais ils doivent également faire l’objet de correctifs ou de mises à jour en temps utile.','__a04-2021-insecure_design.html.twig'),
(2021, 'A5 - Mauvaise configuration de sécurité', 'Les composants, tels que les bibliothèques, les frameworks et autres modules logiciels, s’exécutent avec les mêmes privilèges que l’application. Si un composant vulnérable est exploité, une telle attaque peut faciliter de graves pertes de données ou la prise de contrôle du serveur. Les applications et les API utilisant des composants présentant des vulnérabilités connues peuvent miner les défenses des applications et permettre diverses attaques et incidences.','__a05-2021-security_misconfiguration.html.twig'),
(2021, 'A6 - Composants vulnérables et obsolètes', 'Les fonctions d’application liées à l’authentification et à la gestion des sessions sont souvent mises en œuvre de manière incorrecte, ce qui permet aux attaquants de compromettre les mots de passe, les clés ou les jetons de session, ou d’exploiter d’autres défauts de mise en œuvre pour prendre l’identité d’autres utilisateurs de manière temporaire ou permanente.','__a06-2021-vulnerable_and_outdated_components.html.twig'),
(2021, 'A7 - Identification et authentification de mauvaise qualité', 'Les failles XSS se produisent chaque fois qu’une application inclut des données non fiables dans une nouvelle page Web sans validation ni échappement appropriés, ou met à jour une page Web existante avec des données fournies par l’utilisateur en utilisant une API de navigateur capable de créer du HTML ou du JavaScript. Le XSS permet aux attaquants d’exécuter des scripts dans le navigateur de la victime, ce qui peut détourner les sessions de l’utilisateur, défigurer les sites Web ou rediriger l’utilisateur vers des sites malveillants.','__a07-2021-identification_and_authentication_failures.html.twig'),
(2021, 'A8 - Manque d’intégrité des données et du logiciel', 'Une désérialisation non sécurisée conduit souvent à l’exécution de code à distance. Même si les défauts de désérialisation n’entraînent pas l’exécution de code à distance, ils peuvent être utilisés pour réaliser des attaques, notamment des attaques par rejeu, des attaques par injection et des attaques par élévation de privilèges.','__a08-2021-software_and_data_integrity_failures.html.twig'),
(2021, 'A9 - Carence des systèmes de contrôle et de journalisation', 'L’insuffisance de la journalisation et de la surveillance, associée à l’absence ou à l’inefficacité de l’intégration avec la réponse aux incidents, permet aux attaquants d’attaquer davantage les systèmes, de maintenir la persistance, de pivoter vers d’autres systèmes et d’altérer, d’extraire ou de détruire des données. La plupart des études sur les brèches montrent que le délai de détection d’une brèche est supérieur à 200 jours, généralement détectée par des parties externes plutôt que par des processus ou une surveillance internes.','__a09-2021-carence-des-systemes-de-controle-et-de-journalisation.html.twig'),
(2021, 'A10 - Falsification de requête côté serveur (SSRF)', 'Les failles de fuite de données et de logiciels sont des failles graves permettant aux attaquants de voler des informations sensibles.','__a10-2021-security_logging_and_monitoring_failures.html.twig'),
(2025, 'A1 - Contrôle d’accès défaillant', 'Les restrictions sur ce que les utilisateurs authentifiés sont autorisés à faire ne sont souvent pas correctement appliquées. Les attaquants peuvent exploiter ces failles pour accéder à des fonctionnalités et/ou des données non autorisées, comme l’accès aux comptes d’autres utilisateurs, la visualisation de fichiers sensibles, la modification des données d’autres utilisateurs, la modification des droits d’accès, etc.', '__a01-2025-broken-access-control.html.twig'),
(2025, 'A2 - Mauvaise configuration de sécurité', 'Les composants, tels que les bibliothèques, les frameworks et autres modules logiciels, s’exécutent avec les mêmes privilèges que l’application. Si un composant vulnérable est exploité, une telle attaque peut faciliter de graves pertes de données ou la prise de contrôle du serveur. Les applications et les API utilisant des composants présentant des vulnérabilités connues peuvent miner les défenses des applications et permettre diverses attaques et incidences.','__a02-2025-security-misconfiguration.html.twig'),
(2025, 'A3 - Défaillances de la chaîne d’approvisionnement des logiciels (nouveau)', 'Risques provenant d’outils, de bibliothèques et de composants tiers sur lesquels repose votre logiciel - une cible de plus en plus importante pour les attaquants.','__a03-2025-software-supply-chain-failures.html.twig'),
(2025, 'A4 - Défaillances cryptographiques', 'De nombreuses applications Web et API ne protègent pas correctement les données sensibles, telles que les données financières, les données relatives à la santé et les informations nominatives. Les attaquants peuvent voler ou modifier ces données faiblement protégées pour commettre des fraudes à la carte de crédit, des vols d’identité ou d’autres délits. Les données sensibles peuvent être compromises sans protection supplémentaire, comme le cryptage au repos ou en transit, et nécessitent des précautions particulières lorsqu’elles sont échangées avec le navigateur.', '__a04-2025-cryptographic- failures.html.twig'),
(2025, 'A5 - Injection', 'Les failles d’injection, telles que l’injection SQL, NoSQL, OS et LDAP, se produisent lorsque des données non fiables sont envoyées à un interpréteur dans le cadre d’une commande ou d’une requête. Les données hostiles de l’attaquant peuvent inciter l’interpréteur à exécuter des commandes non souhaitées ou à accéder à des données sans autorisation appropriée.','__a05-2025-injection.html.twig'),
(2025, 'A6 - Conception non sécurisée', 'La mauvaise configuration de la sécurité est le problème le plus fréquemment rencontré. Elle résulte généralement de configurations par défaut non sécurisées, de configurations incomplètes ou ad hoc, d’un stockage en nuage ouvert, d’en-têtes HTTP mal configurés et de messages d’erreur verbeux contenant des informations sensibles. Non seulement tous les systèmes d’exploitation, les frameworks, les bibliothèques et les applications doivent être configurés de manière sécurisée, mais ils doivent également faire l’objet de correctifs ou de mises à jour en temps utile.','__a06-2025-Insecure Design.html.twig'),
(2025, 'A7 - Défauts d’authentification', 'Les failles XSS se produisent chaque fois qu’une application inclut des données non fiables dans une nouvelle page Web sans validation ni échappement appropriés, ou met à jour une page Web existante avec des données fournies par l’utilisateur en utilisant une API de navigateur capable de créer du HTML ou du JavaScript. Le XSS permet aux attaquants d’exécuter des scripts dans le navigateur de la victime, ce qui peut détourner les sessions de l’utilisateur, défigurer les sites Web ou rediriger l’utilisateur vers des sites malveillants.','__a07-2025-authentication-failures.html.twig'),
(2025, 'A8 - Défauts d’intégrité des logiciels ou des données', 'Une désérialisation non sécurisée conduit souvent à l’exécution de code à distance. Même si les défauts de désérialisation n’entraînent pas l’exécution de code à distance, ils peuvent être utilisés pour réaliser des attaques, notamment des attaques par rejeu, des attaques par injection et des attaques par élévation de privilèges.','__a08-2025-software-or-data-integrity-failures.html.twig'),
(2025, 'A9 - Défaillances en matière de journalisation et d’alerte', 'L’insuffisance de la journalisation et de la surveillance, associée à l’absence ou à l’inefficacité de l’intégration avec la réponse aux incidents, permet aux attaquants d’attaquer davantage les systèmes, de maintenir la persistance, de pivoter vers d’autres systèmes et d’altérer, d’extraire ou de détruire des données. La plupart des études sur les brèches montrent que le délai de détection d’une brèche est supérieur à 200 jours, généralement détectée par des parties externes plutôt que par des processus ou une surveillance internes.','__a09-2025-security-logging-and-alerting-failures.html.twig'),
(2025, 'A10 - Mauvaise gestion des conditions exceptionnelles (nouveau)', 'Lorsque les applications ne gèrent pas bien les situations inattendues, telles que les dépassements de délai, les surcharges ou les entrées étranges, elles créent des ouvertures pour les attaquants.','__a10-2025-mishandling-of-exceptional-conditions.html.twig');

COMMIT;
