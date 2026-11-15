-- Comptes et groupes de l'instance de démonstration, appliqués par restaurer-demo.sh APRÈS le chargement des données.
-- Variable psql attendue : hash (empreinte bcrypt du mot de passe, calculée par le script ; jamais écrite ici).
--
-- Modèle de droits :
--   * groupe utilisateur   « ma-moulinette-demo » : rattachement du compte ;
--   * groupe fonctionnel   « ma-moulinette »      : préfixe de tag SonarQube. L'application ne propose que les projets
--     dont un tag commence par « ma-moulinette » (ListeProjetRepository::selectListeProjetByGroupe).
-- Rôle ROLE_UTILISATEUR, le minimum. JAMAIS ROLE_GESTIONNAIRE : il ouvre le CRUD EasyAdmin des utilisateurs et des groupes
-- (activer un compte, attribuer des rôles). Avec ce rôle minimal, le bouton de mise à jour de la page d'accueil est inactif.

\set ON_ERROR_STOP on

-- Le compte d'initialisation des fixtures n'a rien à faire dans une instance exposée.
DELETE FROM ma_moulinette.utilisateur WHERE courriel = 'admin@ma-moulinette.fr';
DELETE FROM ma_moulinette.utilisateur WHERE courriel = 'demo@ma-moulinette.fr';
DELETE FROM ma_moulinette.groupe_utilisateur WHERE groupe_utilisateur = 'ma-moulinette-demo';
DELETE FROM ma_moulinette.groupe_fonctionnel WHERE groupe_fonctionnel = 'ma-moulinette';

INSERT INTO ma_moulinette.groupe_utilisateur (groupe_utilisateur, groupe_id, description, date_enregistrement)
VALUES ('ma-moulinette-demo', 'DEMO000000000000000000DEMO', 'Compte de démonstration (lecture seule).', now());

INSERT INTO ma_moulinette.groupe_fonctionnel (groupe_fonctionnel, description, date_enregistrement)
VALUES ('ma-moulinette', 'Projets de démonstration (tag SonarQube « ma-moulinette »).', now());

INSERT INTO ma_moulinette.utilisateur
(preference, reset_password, courriel, roles, password, prenom, nom, date_enregistrement, actif, avatar,
liste_groupe_fonctionnel, groupe_utilisateur, groupe_id)
VALUES (
'{"statut":{"suivi_projet":false,"favori_projet":false,"favori_version":false},
"suivi_projet":[],"favori_projet":[],"favori_version":[]}',
false,
'demo@ma-moulinette.fr',
'["ROLE_UTILISATEUR"]',
:'hash',
'Démo',
'Ma-Moulinette',
now(),
true,
'chiffre/01.png',
'["ma-moulinette"]',
'ma-moulinette-demo',
'DEMO000000000000000000DEMO'
);
