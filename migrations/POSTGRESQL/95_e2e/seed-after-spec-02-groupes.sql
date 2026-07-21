/*
####################################################
##                                                ##
##  Seed E2E - etat apres spec 02                 ##
##  V1.0.0 - 26/04/2026                           ##
##                                                ##
####################################################*/

-- Seed minimal qui replique l'etat post-spec 02 (5 groupes utilisateur crees
-- par l'internal). Permet de tester les specs 03+ en isolation sans rejouer
-- spec 02 via l'UI a chaque fois.
--
-- Usage : appele APRES reset-e2e-data.sql via bin/e2e/seed-e2e.ps1
-- Pas de ON CONFLICT : la table n'a pas de contrainte UNIQUE sur
-- groupe_utilisateur (controle d'unicite gere au niveau application).
-- Le reset prealable wipe les groupes custom -> pas de duplicat possible.

\c ma_moulinette db_user

INSERT INTO ma_moulinette.groupe_utilisateur
    (groupe_utilisateur, groupe_id, description, date_enregistrement)
VALUES
    ('admin',                   '01HZE2EE01ADMINAAAAAAAAAAA', 'Groupe E2E ADMIN',                  CURRENT_TIMESTAMP),
    ('consultation',            '01HZE2EE02CONSULTATIONAAAA', 'Groupe E2E CONSULTATION',           CURRENT_TIMESTAMP),
    ('collecte',                '01HZE2EE03COLLECTEAAAAAAAA', 'Groupe E2E COLLECTE',               CURRENT_TIMESTAMP),
    ('gestionnaire metier',     '01HZE2EE04GESTIONMETIERAAA', 'Groupe E2E GESTIONNAIRE METIER',    CURRENT_TIMESTAMP),
    ('gestionnaire applicatif', '01HZE2EE05GESTIONAPPLIAAAA', 'Groupe E2E GESTIONNAIRE APPLICATIF', CURRENT_TIMESTAMP);
