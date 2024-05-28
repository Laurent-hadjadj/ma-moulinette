/*
####################################################
##                                                ##
##         Création des tables et des objets      ##
##               V1.2.1 - 22/05/2024              ##
##                                                ##
####################################################*/

-- 22/05/2024 : Laurent HADJADJ - Suprresion des ", modification de la table notes (ajout du clé primaire unique et suppression de l'attribut date.

-- Création du schema
CREATE SCHEMA IF NOT EXISTS ma_moulinette;

-- Création des tables :

-- Création de la table activite

CREATE TABLE activite (
  id INTEGER NOT NULL, -- Identifiant unique de l'activité
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  project_name varchar(64) NOT NULL, -- Nom du projet associé à l'activité
  analyse_id varchar(26) NOT NULL, -- Identifiant de l'analyse
  status varchar(16) NOT NULL, -- Statut de l'activité
  submitter_login varchar(32) NOT NULL, -- Login de l'utilisateur soumettant l'activité
  submitted_at timestamp(0) NOT NULL, -- Date et heure de la soumission de l'activité
  started_at timestamp(0) NOT NULL, -- Date et heure du debut de l'execution de l'activité
  executed_at timestamp(0) NOT NULL, -- Date et heure de fin d'exécution de l'activité
  execution_time INTEGER NOT NULL, -- Temps d'execution de l'activité
  CONSTRAINT activite_pkey PRIMARY KEY (id)
);

-- Création de la table anomalie

CREATE TABLE anomalie (
  id INTEGER NOT NULL, -- Identifiant unique de l'anomalie
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  project_name varchar(128) NOT NULL, -- Nom du projet associé à l'anomalie
  anomalie_total INTEGER NOT NULL, -- Nombre total d'anomalies
  dette_minute INTEGER NOT NULL, -- Totales de la dette technique en minute
  dette_reliability_minute INTEGER NOT NULL, -- Dette technique pour la fiabilité en minute
  dette_vulnerability_minute INTEGER NOT NULL, -- Dette technique pour les vulnérabilités en minute
  dette_code_smell_minute INTEGER NOT NULL, -- Dette technique pour les mauvauises pratiques en minute
  dette_reliability varchar(32) NOT NULL, -- Dette technique pour la fiabilité
  dette_vulnerability varchar(32) NOT NULL, -- Dette technique pour les vulnérabilités
  dette varchar(32) NOT NULL, -- Dette générale
  dette_code_smell varchar(32) NOT NULL, -- Dette technique pour les mauvaises pratiques
  frontend INTEGER NOT NULL, -- Problèmes liés au frontend
  backend INTEGER NOT NULL, -- Problèmes liés au backend
  autre INTEGER NOT NULL, -- Autres problèmes techniques
  blocker INTEGER NOT NULL, -- Problèmes bloquants
  critical INTEGER NOT NULL, -- Problèmes critiques
  major INTEGER NOT NULL, -- Problèmes majeurs
  info INTEGER NOT NULL, -- Informations sur les problèmes mineurs
  minor INTEGER NOT NULL, -- Problèmes mineurs
  bug INTEGER NOT NULL, -- Nombre total de bugs
  vulnerability INTEGER NOT NULL, -- Nombre total de vulnérabilités
  code_smell INTEGER NOT NULL, -- Nombre total de mauvaise pratique
  date_enregistrement date NOT NULL, -- Date d'enregistrement de l'anomalie
  CONSTRAINT anomalie_pkey PRIMARY KEY (id)
);

-- Création de la table anomalie_details

CREATE TABLE anomalie_details (
  id INTEGER NOT NULL, -- Identifiant unique pour les détails de l'anomalie
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  name varchar(128) NOT NULL, -- Nom de l'anomalie
  bug_blocker INTEGER NOT NULL, -- Nombre de bugs bloquants
  bug_critical INTEGER NOT NULL, -- Nombre de bugs critiques
  bug_info INTEGER NOT NULL, -- Nombre de bugs d'information
  bug_major INTEGER NOT NULL, -- Nombre de bugs majeurs
  bug_minor INTEGER NOT NULL, -- Nombre de bugs mineurs
  vulnerability_blocker INTEGER NOT NULL, -- Nombre de vulnérabilités bloquantes
  vulnerability_critical INTEGER NOT NULL, -- Nombre de vulnérabilités critiques
  vulnerability_info INTEGER NOT NULL, -- Nombre de vulnérabilités d'information
  vulnerability_major INTEGER NOT NULL, -- Nombre de vulnérabilités majeures
  vulnerability_minor INTEGER NOT NULL, -- Nombre de vulnérabilités mineures
  code_smell_blocker INTEGER NOT NULL, -- Nombre d'mauvaises pratiques bloquantes
  code_smell_critical INTEGER NOT NULL, -- Nombre d'mauvaises pratiques critiques
  code_smell_info INTEGER NOT NULL, -- Nombre d'mauvaises pratiques d'information
  code_smell_major INTEGER NOT NULL, -- Nombre d'mauvaises pratiques majeures
  code_smell_minor INTEGER NOT NULL, -- Nombre d'mauvaises pratiques mineures
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement des détails de l'anomalie
  CONSTRAINT anomalie_details_pkey PRIMARY KEY (id)
);

-- Création de la table batch

CREATE TABLE batch (
  id INTEGER NOT NULL, -- Identifiant unique du batch
  statut bool NOT NULL, -- Statut d'activité du batch
  titre varchar(32) NOT NULL, -- Titre du batch, unique
  description varchar(128) NOT NULL, -- Description du batch
  responsable varchar(128) NOT NULL, -- Nom de l'utilisateur responsable
  portefeuille varchar(32) NOT NULL, -- Portefeuille de projet, unique
  nombre_projet INTEGER NOT NULL, -- Nombre de projets dans le batch
  execution varchar(8) DEFAULT NULL::character varying NULL, -- État d'exécution du batch
  date_modification timestamp(0) DEFAULT NULL::timestamp without time zone NULL, -- Date de la dernière modification du batch
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement du batch
  CONSTRAINT batch_pkey PRIMARY KEY (id)
);

-- Création de la table batch_traitement

CREATE TABLE batch_traitement (
  id INTEGER NOT NULL, -- Identifiant unique du traitement
  demarrage varchar(16) NOT NULL, -- Mode démarrage du traitement
  resultat bool NOT NULL, -- Indique si le traitement a réussi ou échoué
  titre varchar(32) NOT NULL, -- Titre du traitement
  portefeuille varchar(32) NOT NULL, -- Nom du portefeuille de projets associé
  nombre_projet INTEGER NOT NULL, -- Nombre de projets traités
  responsable varchar(128) NOT NULL, -- Responsable du traitement
  debut_traitement timestamp(0) DEFAULT NULL::timestamp without time zone NULL, -- Date et heure de début du traitement
  fin_traitement timestamp(0) DEFAULT NULL::timestamp without time zone NULL, -- Date et heure de fin du traitement
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement du traitement dans le système
  CONSTRAINT batch_traitement_pkey PRIMARY KEY (id)
);

-- Création de la table equipe

CREATE TABLE equipe (
  id INTEGER NOT NULL, -- Identifiant unique de l'équipe
  titre varchar(32) NOT NULL, -- Titre de l'équipe, unique
  description varchar(128) NOT NULL, -- Description de l'équipe
  date_modification timestamp(0) DEFAULT NULL::timestamp without time zone NULL, -- Date de la dernière modification de l'équipe
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement de l'équipe
  CONSTRAINT equipe_pkey PRIMARY KEY (id)
);

-- Création de la table historique

CREATE TABLE historique (
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  version varchar(32) NOT NULL, -- Version du projet dans l'historique
  date_version varchar(128) NOT NULL, -- Date de la version du projet
  nom_projet varchar(128) NOT NULL, -- Nom du projet associé à cette version
  version_release INTEGER NOT NULL, -- Indicateur de release pour la version spécifique
  version_snapshot INTEGER NOT NULL, -- Indicateur de snapshot pour la version spécifique
  version_autre INTEGER NOT NULL, -- Indicateur pour les autres types de versions
  suppress_warning INTEGER NOT NULL, -- Compteur des suppressions d'avertissements
  no_sonar INTEGER NOT NULL, -- Compteur de l'utilisation de NoSonar
  nombre_ligne INTEGER NOT NULL, -- Nombre total de lignes dans le projet
  nombre_ligne_code INTEGER NOT NULL, -- Nombre total de lignes de code dans le projet
  couverture float8 NOT NULL, -- Pourcentage de couverture de code par les tests
  duplication float8 NOT NULL, -- Pourcentage de duplication dans le code
  tests_unitaires INTEGER NOT NULL, -- Nombre de tests unitaires exécutés
  nombre_defaut INTEGER NOT NULL, -- Nombre total de défauts détectés
  nombre_bug INTEGER NOT NULL, -- Nombre total de bugs détectés
  nombre_vulnerability INTEGER NOT NULL, -- Nombre total de vulnérabilités détectées
  nombre_code_smell INTEGER NOT NULL, -- Nombre total de mauvaises pratiques détectés
  frontend INTEGER NOT NULL, -- Nombre total de bug spécifiques front-end
  backend INTEGER NOT NULL, -- Nombre total de bug spécifiques back-end
  autre INTEGER NOT NULL, -- Nombre total de bug autre
  dette INTEGER NOT NULL, -- Somme de la dette technique accumulée
  sqale_debt_ratio float8 NOT NULL, -- Ratio de la dette technique (SQALE)
  nombre_anomalie_bloquant INTEGER NOT NULL, -- Nombre d'anomalies bloquantes
  nombre_anomalie_critique INTEGER NOT NULL, -- Nombre d'anomalies critiques
  nombre_anomalie_info INTEGER NOT NULL, -- Nombre d'anomalies d'information
  nombre_anomalie_majeur INTEGER NOT NULL, -- Nombre d'anomalies majeures
  nombre_anomalie_mineur INTEGER NOT NULL, -- Nombre d'anomalies mineures
  note_reliability varchar(16) NOT NULL, -- Note de fiabilité attribuée au projet
  note_security varchar(16) NOT NULL, -- Note de sécurité attribuée au projet
  note_sqale varchar(16) NOT NULL, -- Note SQALE attribuée au projet
  note_hotspot varchar(16) NOT NULL, -- Note pour les hotspots de sécurité
  hotspot_high INTEGER NOT NULL, -- Nombre de hotspots de sécurité de niveau élevé
  hotspot_medium INTEGER NOT NULL, -- Nombre de hotspots de sécurité de niveau moyen
  hotspot_low INTEGER NOT NULL, -- Nombre de hotspots de sécurité de niveau faible
  hotspot_total INTEGER NOT NULL, -- Nombre total de hotspots de sécurité
  initial bool NOT NULL, -- Indique si c'est l'initialisation du projet
  bug_blocker INTEGER NOT NULL, -- Nombre de bugs bloquants
  bug_critical INTEGER NOT NULL, -- Nombre de bugs critiques
  bug_major INTEGER NOT NULL, -- Nombre de bugs majeurs
  bug_minor INTEGER NOT NULL, -- Nombre de bugs mineurs
  bug_info INTEGER NOT NULL, -- Nombre de bugs d'information
  vulnerability_blocker INTEGER NOT NULL, -- Nombre de vulnérabilités bloquantes
  vulnerability_critical INTEGER NOT NULL, -- Nombre de vulnérabilités critiques
  vulnerability_major INTEGER NOT NULL, -- Nombre de vulnérabilités majeures
  vulnerability_minor INTEGER NOT NULL, -- Nombre de vulnérabilités mineures
  vulnerability_info INTEGER NOT NULL, -- Nombre de vulnérabilités d'information
  code_smell_blocker INTEGER NOT NULL, -- Nombre de mauvaises pratiques bloquants
  code_smell_critical INTEGER NOT NULL, -- Nombre de mauvaises pratiques critiques
  code_smell_major INTEGER NOT NULL, -- Nombre de mauvaises pratiques majeurs
  code_smell_minor INTEGER NOT NULL, -- Nombre de mauvaises pratiques mineurs
  code_smell_info INTEGER NOT NULL, -- Nombre de mauvaises pratiques d'information
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement de l'historique
  CONSTRAINT historique_pkey PRIMARY KEY (maven_key, version, date_version)
);

-- Création de la table hotspot_details

CREATE TABLE hotspot_details (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque détail de hotspot
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  version varchar(32) NOT NULL, -- Version du détail de hotspot
  date_version timestamp(0) NOT NULL, -- Date de la version du détail de hotspot
  severity varchar(8) NOT NULL, -- Sévérité du hotspot
  niveau INTEGER NOT NULL, -- Niveau de risque du hotspot
  status varchar(16) NOT NULL, -- Statut du hotspot
  frontend INTEGER NOT NULL, -- Implémentation frontend associée au hotspot
  backend INTEGER NOT NULL, -- Implémentation backend associée au hotspot
  autre INTEGER NOT NULL, -- Autres implémentations associées au hotspot
  file varchar(255) NOT NULL, -- Fichier associé au hotspot
  line INTEGER NOT NULL, -- Ligne du fichier où se situe le hotspot
  rule varchar(255) NOT NULL, -- Règle associée au hotspot
  message varchar(255) NOT NULL, -- Message descriptif du hotspot
  key varchar(32) NOT NULL, -- Clé unique du hotspot
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement du détail de hotspot
  CONSTRAINT hotspot_details_pkey PRIMARY KEY (id)
);

-- Création de la table hotspot_owasp

CREATE TABLE hotspot_owasp (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque hotspot OWASP
  maven_key varchar(255) NOT NULL, -- Clé Maven du hotspot OWASP
  version varchar(32) NOT NULL, -- Version du hotspot OWASP
  date_version timestamp(0) NOT NULL, -- Date de la version du hotspot OWASP
  menace varchar(8) NOT NULL, -- Menace évaluée du hotspot OWASP
  probability varchar(8) NOT NULL, -- Probabilité du hotspot OWASP
  status varchar(16) NOT NULL, -- Statut du hotspot OWASP
  niveau INTEGER NOT NULL, -- Niveau de risque du hotspot OWASP
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement du hotspot OWASP
  CONSTRAINT hotspot_owasp_pkey PRIMARY KEY (id)
);

-- Création de la table hotspots

CREATE TABLE hotspots (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque hotspot
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  version varchar(32) NOT NULL, -- Version du hotspot
  date_version timestamp(0) NOT NULL, -- Date de la version du hotspot
  key varchar(32) NOT NULL, -- Clé unique du hotspot
  probability varchar(8) NOT NULL, -- Probabilité de risque du hotspot
  status varchar(16) NOT NULL, -- Statut du hotspot
  niveau INTEGER NOT NULL, -- Niveau de risque du hotspot
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement du hotspot
  CONSTRAINT hotspots_pkey PRIMARY KEY (id)
);

-- Création de la table information_projet

CREATE TABLE information_projet (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque instance de InformationProjet
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  analyse_key varchar(32) NOT NULL, -- Clé d'analyse du projet
  date timestamp(0) NOT NULL, -- Date de l'analyse du projet
  project_version varchar(32) NOT NULL, -- Version du projet lors de l'analyse
  type varchar(32) NOT NULL, -- Type d'analyse effectuée
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement de l'information du projet
  CONSTRAINT information_projet_pkey PRIMARY KEY (id)
);

-- Création de la table liste_projet

CREATE TABLE liste_projet (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque instance de ListeProjet
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  name varchar(128) NOT NULL, -- Nom du projet
  tags json NOT NULL, -- Tags associés au projet sous forme de tableau JSON
  visibility varchar(8) NOT NULL, -- Visibilité du projet
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement du projet
  CONSTRAINT liste_projet_pkey PRIMARY KEY (id)
);

-- Création de la table ma_moulinette

CREATE TABLE ma_moulinette (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque instance de MaMoulinette
  version varchar(16) NOT NULL, -- Numéro de version de Ma-Moulinette
  date_version timestamp(0) NOT NULL, -- Date de la release
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement de la version
  CONSTRAINT ma_moulinette_pkey PRIMARY KEY (id)
);

-- Création de la fonction utilisée par le trigger

CREATE OR REPLACE FUNCTION notify_messenger_messages()
RETURNS trigger AS $$
BEGIN
    PERFORM pg_notify('queue_name', 'Nouveau message dans messenger_messages');
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Création de la table messenger_messages

CREATE TABLE messenger_messages (
  id bigserial NOT NULL,
  body text NOT NULL,
  headers text NOT NULL,
  queue_name varchar(190) NOT NULL,
  created_at timestamp(0) NOT NULL, -- (DC2Type:datetime_immutable)
  available_at timestamp(0) NOT NULL, -- (DC2Type:datetime_immutable)
  delivered_at timestamp(0) DEFAULT NULL::timestamp without time zone NULL, -- (DC2Type:datetime_immutable)
  CONSTRAINT messenger_messages_pkey PRIMARY KEY (id)
);

--  Création de la table mesures

CREATE TABLE mesures (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque mesure
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  project_name varchar(128) NOT NULL, -- Nom du projet
  lines INTEGER NOT NULL, -- Nombre total de lignes du projet
  ncloc INTEGER NOT NULL, -- Lignes de code non commentées
  coverage float8 NOT NULL, -- Pourcentage de couverture par les tests
  sqale_debt_ratio float8 NOT NULL, -- Ratio dette technique (SQALE)
  duplication_density float8 NOT NULL, -- Densité de duplication du code
  tests INTEGER NOT NULL, -- Nombre total de tests
  issues INTEGER NOT NULL, -- Nombre total de problèmes identifiés
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement de la mesure
  CONSTRAINT mesures_pkey PRIMARY KEY (id)
);

-- Création de la table no_sonar

CREATE TABLE no_sonar (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque entrée NoSonar
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  rule varchar(128) NOT NULL, -- Règle NoSonar appliquée
  component text NOT NULL, -- Composant auquel la règle est appliquée
  line INTEGER NOT NULL, -- Ligne où la règle NoSonar est appliquée
  date_enregistrement date NOT NULL, -- Date d'enregistrement de l'entrée NoSonar
  CONSTRAINT no_sonar_pkey PRIMARY KEY (id)
);

-- Création de la table notes

CREATE TABLE notes (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque entrée notes
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  type varchar(16) NOT NULL, -- Type de la note
  value INTEGER NOT NULL, -- Valeur de la note
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement de la note
  CONSTRAINT notes_pkey PRIMARY KEY (id)
);

-- Création de la table owasp

CREATE TABLE owasp (
  id INTEGER NOT NULL, -- Clé Maven unique
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  version varchar(32) NOT NULL, -- Version du projet
  date_version timestamp(0) NOT NULL, -- Date de la version du projet
  effort_total INTEGER NOT NULL, -- Total effort score for security issues
  a1 INTEGER NOT NULL, -- Score for OWASP Top 10 - A1: Injection vulnerabilities
  a2 INTEGER NOT NULL, -- Score for OWASP Top 10 - A2: Broken Authentication
  a3 INTEGER NOT NULL, -- Score for OWASP Top 10 - A3: Sensitive Data Exposure
  a4 INTEGER NOT NULL, -- Score total pour A4
  a5 INTEGER NOT NULL, -- Score total pour A5
  a6 INTEGER NOT NULL, -- Score total pour A6
  a7 INTEGER NOT NULL, -- Score total pour A7
  a8 INTEGER NOT NULL, -- Score total pour A8
  a9 INTEGER NOT NULL, -- Score total pour A9
  a10 INTEGER NOT NULL, -- Score total pour A10
  a1_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A1
  a1_critical INTEGER NOT NULL, -- Nombre de faille critique de type A1
  a1_major INTEGER NOT NULL, -- Nombre de faille majeure de type A1
  a1_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A1
  a1_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A1
  a2_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A2
  a2_critical INTEGER NOT NULL, -- Nombre de faille critique de type A2
  a2_major INTEGER NOT NULL, -- Nombre de faille majeure de type A2
  a2_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A2
  a2_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A2
  a3_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A3
  a3_critical INTEGER NOT NULL, -- Nombre de faille critique de type A3
  a3_major INTEGER NOT NULL, -- Nombre de faille majeure de type A3
  a3_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A3
  a3_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A3
  a4_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A4
  a4_critical INTEGER NOT NULL, -- Nombre de faille critique de type A4
  a4_major INTEGER NOT NULL, -- Nombre de faille majeure de type A4
  a4_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A4
  a4_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A4
  a5_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A5
  a5_critical INTEGER NOT NULL, -- Nombre de faille critique de type A5
  a5_major INTEGER NOT NULL, -- Nombre de faille majeure de type A5
  a5_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A5
  a5_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A5
  a6_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A6
  a6_critical INTEGER NOT NULL, -- Nombre de faille critique de type A6
  a6_major INTEGER NOT NULL, -- Nombre de faille majeure de type A6
  a6_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A6
  a6_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A6
  a7_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A7
  a7_critical INTEGER NOT NULL, -- Nombre de faille critique de type A7
  a7_major INTEGER NOT NULL, -- Nombre de faille majeure de type A7
  a7_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A7
  a7_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A7
  a8_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A8
  a8_critical INTEGER NOT NULL, -- Nombre de faille critique de type A8
  a8_major INTEGER NOT NULL, -- Nombre de faille majeure de type A8
  a8_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A8
  a8_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A8
  a9_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A9
  a9_critical INTEGER NOT NULL, -- Nombre de faille critique de type A9
  a9_major INTEGER NOT NULL, -- Nombre de faille majeure de type A9
  a9_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A9
  a9_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A9
  a10_blocker INTEGER NOT NULL, -- Nombre de faille bloquante de type A10
  a10_critical INTEGER NOT NULL, -- Nombre de faille critique de type A10
  a10_major INTEGER NOT NULL, -- Nombre de faille majeure de type A10
  a10_info INTEGER NOT NULL, -- Nombre de faille d’informations de type A10
  a10_minor INTEGER NOT NULL, -- Nombre de faille mineure de type A10
  date_enregistrement timestamp(0) NOT NULL, -- Date d’enregistrement des données
  CONSTRAINT owasp_pkey PRIMARY KEY (id)
);

-- Création de la table portefeuille

CREATE TABLE portefeuille (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque portefeuille
  titre varchar(32) NOT NULL, -- Titre unique du portefeuille
  equipe varchar(32) NOT NULL, -- Nom de l'équipe associée au portefeuille
  liste json NOT NULL, -- Liste des éléments ou des activités du portefeuille
  date_modification timestamp(0) DEFAULT NULL::timestamp without time zone NULL, -- Date de la dernière modification du portefeuille
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement du portefeuille
  CONSTRAINT portefeuille_pkey PRIMARY KEY (id)
);

-- profiles

CREATE TABLE profiles (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque profil
  key varchar(255) NOT NULL, -- Clé unique du profil
  name varchar(128) NOT NULL, -- Nom du profil
  language_name varchar(64) NOT NULL, -- Nom du langage de programmation
  active_rule_count INTEGER NOT NULL, -- Nombre de règles actives associées au profil
  rules_update_at timestamp(0) NOT NULL, -- Date de la dernière mise à jour des règles
  referentiel_default bool NOT NULL, -- Indique si le profil est le profil par défaut
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement du profil
  CONSTRAINT profiles_pkey PRIMARY KEY (id)
);

-- Création de la table profiles_historique

CREATE TABLE profiles_historique (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque historique de profil
  date_courte timestamp(0) NOT NULL, -- Date courte associée à l’historique
  language varchar(16) NOT NULL, -- Langage de programmation associé
  date timestamp(0) NOT NULL, -- Date complète de l’événement de l’historique
  action varchar(16) NOT NULL, -- Action réalisée, par exemple modification ou création
  auteur varchar(64) NOT NULL, -- Auteur de l’action dans l’historique
  regle varchar(128) NOT NULL, -- Règle ou norme concernée par l’historique
  description text NOT NULL, -- Description détaillée de l’événement historique
  detail bytea NOT NULL, -- Détails supplémentaires ou données binaires associées à l’événement
  date_enregistrement timestamp(0) NOT NULL, -- Date d’enregistrement de l’entrée historique dans la base de données
  CONSTRAINT profiles_historique_pkey PRIMARY KEY (id)
);

-- Création de la table properties

CREATE TABLE properties (
  id INTEGER NOT NULL, -- Identifiant unique pour chaque propriété
  type varchar(255) NOT NULL, -- Type de propriété
  projet_bd INTEGER NOT NULL, -- Identifiant du projet dans la base de données
  projet_sonar INTEGER NOT NULL, -- Identifiant du projet dans Sonar
  profil_bd INTEGER NOT NULL, -- Identifiant du profil dans la base de données
  profil_sonar INTEGER NOT NULL, -- Identifiant du profil dans Sonar
  date_creation timestamp(0) NOT NULL, -- Date de création de la propriété
  date_modification_projet timestamp(0) DEFAULT NULL::timestamp without time zone NULL, -- Date de la dernière modification du projet
  date_modification_profil timestamp(0) DEFAULT NULL::timestamp without time zone NULL, -- Date de la dernière modification du profil
  CONSTRAINT properties_pkey PRIMARY KEY (id)
);

-- Création de la table repartition

CREATE TABLE repartition (
  id INTEGER NOT NULL, -- ID unique pour chaque répartition
  maven_key varchar(128) NOT NULL, -- Clé Maven unique identifiant le projet
  name varchar(128) NOT NULL, -- Nom de la répartition
  component text NOT NULL, -- Détails du composant concerné par la répartition
  type varchar(16) NOT NULL, -- Type de la répartition
  severity varchar(8) NOT NULL, -- Gravité de la répartition
  setup INTEGER NOT NULL, -- Paramètre de configuration pour la répartition
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement de la répartition dans le système
  CONSTRAINT repartition_pkey PRIMARY KEY (id)
);

-- Création de la table todo

CREATE TABLE todo (
  id INTEGER NOT NULL, -- ID unique pour chaque tâche
  maven_key varchar(255) NOT NULL, -- Clé Maven unique identifiant le projet
  rule varchar(128) NOT NULL, -- Règle appliquée à la tâche
  component text NOT NULL, -- Détails du composant concerné par la tâche
  line INTEGER NOT NULL, -- Numéro de ligne du code associée à la tâche
  date_enregistrement timestamp(0) NOT NULL, -- Date d'enregistrement de la tâche dans le système
  CONSTRAINT todo_pkey PRIMARY KEY (id)
);

-- Création de la table utilisateur

CREATE TABLE utilisateur (
  id INTEGER NOT NULL, -- clé unique de la table
  prenom varchar(32) NOT NULL, -- Prénom de l'utilisateur
  nom varchar(64) NOT NULL, -- Nom de l'utilisateur
  avatar varchar(128) DEFAULT NULL::character varying NULL, -- Avatar de l'utilisateur
  courriel varchar(320) NOT NULL, -- Adresse de courriel, clé unique
  roles json NULL, -- Liste des rôles
  equipe json NULL, -- Liste des équipes
  password varchar(64) NOT NULL, -- Mot de passe de l'utilisateur
  actif bool DEFAULT false NOT NULL, -- L'utilisateur est déseactivé
  preference json NOT NULL, -- Préférences de l'utilisateur
  init INTEGER NOT NULL, -- Indicateur de réinitilisation du mot de passe
  date_modification timestamp(0) DEFAULT NULL::timestamp without time zone NULL, -- Date de modification
  date_enregistrement timestamp(0) NOT NULL, -- Date de création
  CONSTRAINT utilisateur_pkey PRIMARY KEY (id)
);

-- Création des sequences :
-- Activite
CREATE SEQUENCE activite_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE activite ALTER COLUMN id SET DEFAULT nextval('activite_id_seq');

-- Anomalie
CREATE SEQUENCE anomalie_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE anomalie ALTER COLUMN id SET DEFAULT nextval('anomalie_id_seq');

-- Anomalie_details
CREATE SEQUENCE anomalie_details_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE anomalie_details ALTER COLUMN id SET DEFAULT nextval('anomalie_details_id_seq');

-- Batch
CREATE SEQUENCE batch_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE batch ALTER COLUMN id SET DEFAULT nextval('batch_id_seq');

-- Batch_traitement
CREATE SEQUENCE batch_traitement_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE batch_traitement ALTER COLUMN id SET DEFAULT nextval('batch_traitement_id_seq');

-- Equipe
CREATE SEQUENCE equipe_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE equipe ALTER COLUMN id SET DEFAULT nextval('equipe_id_seq');

-- Historique
CREATE SEQUENCE historique_id_seq START WITH 1 INCREMENT BY 1;
-- Pas d'ID à incrémenter ici; la clé primaire est composée (pas de modification nécessaire)

-- Hotspot_details
CREATE SEQUENCE hotspot_details_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE hotspot_details ALTER COLUMN id SET DEFAULT nextval('hotspot_details_id_seq');

-- Hotspot_owasp
CREATE SEQUENCE hotspot_owasp_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE hotspot_owasp ALTER COLUMN id SET DEFAULT nextval('hotspot_owasp_id_seq');

-- Hotspots
CREATE SEQUENCE hotspots_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE hotspots ALTER COLUMN id SET DEFAULT nextval('hotspots_id_seq');

-- Information_projet
CREATE SEQUENCE information_projet_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE information_projet ALTER COLUMN id SET DEFAULT nextval('information_projet_id_seq');

-- Liste_projet
CREATE SEQUENCE liste_projet_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE liste_projet ALTER COLUMN id SET DEFAULT nextval('liste_projet_id_seq');

-- Ma_moulinette
CREATE SEQUENCE ma_moulinette_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE ma_moulinette ALTER COLUMN id SET DEFAULT nextval('ma_moulinette_id_seq');

-- Mesures
CREATE SEQUENCE mesures_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE mesures ALTER COLUMN id SET DEFAULT nextval('mesures_id_seq');

-- No_sonar
CREATE SEQUENCE no_sonar_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE no_sonar ALTER COLUMN id SET DEFAULT nextval('no_sonar_id_seq');

-- Notes
CREATE SEQUENCE notes_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE notes ALTER COLUMN id SET DEFAULT nextval('notes_id_seq');

-- Owasp
CREATE SEQUENCE owasp_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE owasp ALTER COLUMN id SET DEFAULT nextval('owasp_id_seq');

-- Portefeuille
CREATE SEQUENCE portefeuille_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE portefeuille ALTER COLUMN id SET DEFAULT nextval('portefeuille_id_seq');

-- Profiles
CREATE SEQUENCE profiles_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE profiles ALTER COLUMN id SET DEFAULT nextval('profiles_id_seq');

-- Profiles_historique
CREATE SEQUENCE profiles_historique_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE profiles_historique ALTER COLUMN id SET DEFAULT nextval('profiles_historique_id_seq');

-- Properties
CREATE SEQUENCE properties_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE properties ALTER COLUMN id SET DEFAULT nextval('properties_id_seq');

-- Repartition
CREATE SEQUENCE repartition_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE repartition ALTER COLUMN id SET DEFAULT nextval('repartition_id_seq');

-- To.do
CREATE SEQUENCE todo_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE todo ALTER COLUMN id SET DEFAULT nextval('todo_id_seq');

-- Utilisateur
CREATE SEQUENCE utilisateur_id_seq START WITH 1 INCREMENT BY 1;
ALTER TABLE utilisateur ALTER COLUMN id SET DEFAULT nextval('utilisateur_id_seq');

-- Création des indexes, triggers et les commentaires
--Indexes :
--Batch
CREATE UNIQUE INDEX uniq_f80b52d42955fffe ON batch USING btree (portefeuille);
CREATE UNIQUE INDEX uniq_f80b52d4ff7747b4 ON batch USING btree (titre);

--equipe
CREATE UNIQUE INDEX uniq_2449ba15ff7747b4 ON equipe USING btree (titre);

--messenger_messages
CREATE INDEX idx_75ea56e016ba31db ON messenger_messages USING btree (delivered_at);
CREATE INDEX idx_75ea56e0e3bd61ce ON messenger_messages USING btree (available_at);
CREATE INDEX idx_75ea56e0fb7336f0 ON messenger_messages USING btree (queue_name);

--portfeuille
CREATE UNIQUE INDEX uniq_2955fffeff7747b4 ON portefeuille USING btree (titre);

--utilisateur
CREATE UNIQUE INDEX uniq_1d1c63b344fb41c9 ON utilisateur USING btree (courriel);


--Triggers :
--messenger_messages
CREATE TRIGGER notify_trigger AFTER INSERT OR UPDATE
ON messenger_messages FOR EACH ROW EXECUTE FUNCTION notify_messenger_messages();

--Commentaires :
--activite
COMMENT ON COLUMN activite.id IS 'Identifiant unique de l''activité';
COMMENT ON COLUMN activite.maven_key IS 'Clé Maven de l''activité';
COMMENT ON COLUMN activite.project_name IS 'Nom du projet associé à l''activité';
COMMENT ON COLUMN activite.analyse_id IS 'Identifiant de l''analyse';
COMMENT ON COLUMN activite.status IS 'Statut de l''activité';
COMMENT ON COLUMN activite.submitter_login IS 'Login de l''utilisateur soumettant l''activité';
COMMENT ON COLUMN activite.executed_at IS 'Date et heure d''exécution de l''activité';

--anomalie
COMMENT ON COLUMN anomalie.id IS 'Identifiant unique de l''anomalie';
COMMENT ON COLUMN anomalie.maven_key IS 'Clé Maven de l''anomalie';
COMMENT ON COLUMN anomalie.project_name IS 'Nom du projet associé à l''anomalie';
COMMENT ON COLUMN anomalie.anomalie_total IS 'Nombre total d''anomalies';
COMMENT ON COLUMN anomalie.dette_minute IS 'Totales de la dette technique en minute';
COMMENT ON COLUMN anomalie.dette_reliability_minute IS 'Dette pour la fiabilité en minute';
COMMENT ON COLUMN anomalie.dette_vulnerability_minute IS 'Dette pour les vulnérabilités en minute';
COMMENT ON COLUMN anomalie.dette_code_smell_minute IS 'Dette pour les  mauvaises pratiques en minute';
COMMENT ON COLUMN anomalie.dette_reliability IS 'Dette technique pour la fiabilité';
COMMENT ON COLUMN anomalie.dette_vulnerability IS 'Dette technique pour les  vulnérabilités';
COMMENT ON COLUMN anomalie.dette IS 'Dette technique générale';
COMMENT ON COLUMN anomalie.dette_code_smell IS 'Dette technique pour les mauvaises pratiques';
COMMENT ON COLUMN anomalie.frontend IS 'Problèmes liés au frontend';
COMMENT ON COLUMN anomalie.backend IS 'Problèmes liés au backend';
COMMENT ON COLUMN anomalie.autre IS 'Autres problèmes techniques';
COMMENT ON COLUMN anomalie.blocker IS 'Problèmes bloquants';
COMMENT ON COLUMN anomalie.critical IS 'Problèmes critiques';
COMMENT ON COLUMN anomalie.major IS 'Problèmes majeurs';
COMMENT ON COLUMN anomalie.info IS 'Informations sur les problèmes mineurs';
COMMENT ON COLUMN anomalie.minor IS 'Problèmes mineurs';
COMMENT ON COLUMN anomalie.bug IS 'Nombre total de bugs';
COMMENT ON COLUMN anomalie.vulnerability IS 'Nombre total de vulnérabilités';
COMMENT ON COLUMN anomalie.code_smell IS 'Nombre total de mauvaises pratiques';
COMMENT ON COLUMN anomalie.date_enregistrement IS 'Date d''enregistrement de l''anomalie';

--anomalie_details
COMMENT ON COLUMN anomalie_details.id IS 'Identifiant unique pour les détails de l''anomalie';
COMMENT ON COLUMN anomalie_details.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN anomalie_details.name IS 'Nom de l''anomalie';
COMMENT ON COLUMN anomalie_details.bug_blocker IS 'Nombre de bugs bloquants';
COMMENT ON COLUMN anomalie_details.bug_critical IS 'Nombre de bugs critiques';
COMMENT ON COLUMN anomalie_details.bug_info IS 'Nombre de bugs d''information';
COMMENT ON COLUMN anomalie_details.bug_major IS 'Nombre de bugs majeurs';
COMMENT ON COLUMN anomalie_details.bug_minor IS 'Nombre de bugs mineurs';
COMMENT ON COLUMN anomalie_details.vulnerability_blocker IS 'Nombre de vulnérabilités bloquantes';
COMMENT ON COLUMN anomalie_details.vulnerability_critical IS 'Nombre de vulnérabilités critiques';
COMMENT ON COLUMN anomalie_details.vulnerability_info IS 'Nombre de vulnérabilités d''information';
COMMENT ON COLUMN anomalie_details.vulnerability_major IS 'Nombre de vulnérabilités majeures';
COMMENT ON COLUMN anomalie_details.vulnerability_minor IS 'Nombre de vulnérabilités mineures';
COMMENT ON COLUMN anomalie_details.code_smell_blocker IS 'Nombre de mauvaises pratiques bloquantes';
COMMENT ON COLUMN anomalie_details.code_smell_critical IS 'Nombre de mauvaises pratiques critiques';
COMMENT ON COLUMN anomalie_details.code_smell_info IS 'Nombre de mauvaises pratiques d''information';
COMMENT ON COLUMN anomalie_details.code_smell_major IS 'Nombre de mauvaises pratiques majeures';
COMMENT ON COLUMN anomalie_details.code_smell_minor IS 'Nombre de mauvaises pratiques mineures';
COMMENT ON COLUMN anomalie_details.date_enregistrement IS 'Date d''enregistrement des détails de l''anomalie';

--batch
COMMENT ON COLUMN batch.id IS 'Identifiant unique du batch';
COMMENT ON COLUMN batch.statut IS 'Statut d''activité du batch';
COMMENT ON COLUMN batch.titre IS 'Titre du batch, unique';
COMMENT ON COLUMN batch.description IS 'Description du batch';
COMMENT ON COLUMN batch.responsable IS 'Nom de l''utilisateur responsable';
COMMENT ON COLUMN batch.portefeuille IS 'Portefeuille de projet, unique';
COMMENT ON COLUMN batch.nombre_projet IS 'Nombre de projets dans le batch';
COMMENT ON COLUMN batch.execution IS 'État d''exécution du batch';
COMMENT ON COLUMN batch.date_modification IS 'Date de la dernière modification du batch';
COMMENT ON COLUMN batch.date_enregistrement IS 'Date d''enregistrement du batch';

--batch_traitement
COMMENT ON COLUMN batch_traitement.id IS 'Identifiant unique du traitement';
COMMENT ON COLUMN batch_traitement.demarrage IS 'Mode démarrage du traitement';
COMMENT ON COLUMN batch_traitement.resultat IS 'Indique si le traitement a réussi ou échoué';
COMMENT ON COLUMN batch_traitement.titre IS 'Titre du traitement';
COMMENT ON COLUMN batch_traitement.portefeuille IS 'Nom du portefeuille de projets associé';
COMMENT ON COLUMN batch_traitement.nombre_projet IS 'Nombre de projets traités';
COMMENT ON COLUMN batch_traitement.responsable IS 'Responsable du traitement';
COMMENT ON COLUMN batch_traitement.debut_traitement IS 'Date et heure de début du traitement';
COMMENT ON COLUMN batch_traitement.fin_traitement IS 'Date et heure de fin du traitement';
COMMENT ON COLUMN batch_traitement.date_enregistrement IS 'Date d''enregistrement du traitement dans le système';

--equipe
COMMENT ON COLUMN equipe.id IS 'Identifiant unique de l''équipe';
COMMENT ON COLUMN equipe.titre IS 'Titre de l''équipe, unique';
COMMENT ON COLUMN equipe.description IS 'Description de l''équipe';
COMMENT ON COLUMN equipe.date_modification IS 'Date de la dernière modification de l''équipe';
COMMENT ON COLUMN equipe.date_enregistrement IS 'Date d''enregistrement de l''équipe';

--historique
COMMENT ON COLUMN historique.maven_key IS 'Clé Maven pour l''historique des projets';
COMMENT ON COLUMN historique.version IS 'Version du projet dans l''historique';
COMMENT ON COLUMN historique.date_version IS 'Date de la version du projet';
COMMENT ON COLUMN historique.nom_projet IS 'Nom du projet associé à cette version';
COMMENT ON COLUMN historique.version_release IS 'Indicateur de release pour la version spécifique';
COMMENT ON COLUMN historique.version_snapshot IS 'Indicateur de snapshot pour la version spécifique';
COMMENT ON COLUMN historique.version_autre IS 'Indicateur pour les autres types de versions';
COMMENT ON COLUMN historique.suppress_warning IS 'Compteur des suppressions d''avertissements';
COMMENT ON COLUMN historique.no_sonar IS 'Compteur de l''utilisation de NoSonar';
COMMENT ON COLUMN historique.nombre_ligne IS 'Nombre total de lignes dans le projet';
COMMENT ON COLUMN historique.nombre_ligne_code IS 'Nombre total de lignes de code dans le projet';
COMMENT ON COLUMN historique.couverture IS 'Pourcentage de couverture de code par les tests';
COMMENT ON COLUMN historique.duplication IS 'Pourcentage de duplication dans le code';
COMMENT ON COLUMN historique.tests_unitaires IS 'Nombre de tests unitaires exécutés';
COMMENT ON COLUMN historique.nombre_defaut IS 'Nombre total de défauts détectés';
COMMENT ON COLUMN historique.nombre_bug IS 'Nombre total de bugs détectés';
COMMENT ON COLUMN historique.nombre_vulnerability IS 'Nombre total de vulnérabilités détectées';
COMMENT ON COLUMN historique.nombre_code_smell IS 'Nombre total de mauvaises pratiques détectés';
COMMENT ON COLUMN historique.frontend IS 'Développements spécifiques front-end';
COMMENT ON COLUMN historique.backend IS 'Développements spécifiques back-end';
COMMENT ON COLUMN historique.autre IS 'Autres développements spécifiques';
COMMENT ON COLUMN historique.dette IS 'Somme de la dette technique accumulée';
COMMENT ON COLUMN historique.sqale_debt_ratio IS 'Ratio de la dette technique (SQALE)';
COMMENT ON COLUMN historique.nombre_anomalie_bloquant IS 'Nombre d''anomalies bloquantes';
COMMENT ON COLUMN historique.nombre_anomalie_critique IS 'Nombre d''anomalies critiques';
COMMENT ON COLUMN historique.nombre_anomalie_info IS 'Nombre d''anomalies d''information';
COMMENT ON COLUMN historique.nombre_anomalie_majeur IS 'Nombre d''anomalies majeures';
COMMENT ON COLUMN historique.nombre_anomalie_mineur IS 'Nombre d''anomalies mineures';
COMMENT ON COLUMN historique.note_reliability IS 'Note de fiabilité attribuée au projet';
COMMENT ON COLUMN historique.note_security IS 'Note de sécurité attribuée au projet';
COMMENT ON COLUMN historique.note_sqale IS 'Note SQALE attribuée au projet';
COMMENT ON COLUMN historique.note_hotspot IS 'Note pour les hotspots de sécurité';
COMMENT ON COLUMN historique.hotspot_high IS 'Nombre de hotspots de sécurité de niveau élevé';
COMMENT ON COLUMN historique.hotspot_medium IS 'Nombre de hotspots de sécurité de niveau moyen';
COMMENT ON COLUMN historique.hotspot_low IS 'Nombre de hotspots de sécurité de niveau faible';
COMMENT ON COLUMN historique.hotspot_total IS 'Nombre total de hotspots de sécurité';
COMMENT ON COLUMN historique.initial IS 'Indique si c''est le projet de référence';
COMMENT ON COLUMN historique.bug_blocker IS 'Nombre de bugs bloquants';
COMMENT ON COLUMN historique.bug_critical IS 'Nombre de bugs critiques';
COMMENT ON COLUMN historique.bug_major IS 'Nombre de bugs majeurs';
COMMENT ON COLUMN historique.bug_minor IS 'Nombre de bugs mineurs';
COMMENT ON COLUMN historique.bug_info IS 'Nombre de bugs d''information';
COMMENT ON COLUMN historique.vulnerability_blocker IS 'Nombre de vulnérabilités bloquantes';
COMMENT ON COLUMN historique.vulnerability_critical IS 'Nombre de vulnérabilités critiques';
COMMENT ON COLUMN historique.vulnerability_major IS 'Nombre de vulnérabilités majeures';
COMMENT ON COLUMN historique.vulnerability_minor IS 'Nombre de vulnérabilités mineures';
COMMENT ON COLUMN historique.vulnerability_info IS 'Nombre de vulnérabilités d''information';
COMMENT ON COLUMN historique.code_smell_blocker IS 'Nombre de mauvaises pratiques bloquants';
COMMENT ON COLUMN historique.code_smell_critical IS 'Nombre de mauvaises pratiques critiques';
COMMENT ON COLUMN historique.code_smell_major IS 'Nombre de mauvaises pratiques majeurs';
COMMENT ON COLUMN historique.code_smell_minor IS 'Nombre de mauvaises pratiques mineurs';
COMMENT ON COLUMN historique.code_smell_info IS 'Nombre de mauvaises pratiques d''information';
COMMENT ON COLUMN historique.date_enregistrement IS 'Date d''enregistrement de l''historique';

--hotspot_details
COMMENT ON COLUMN hotspot_details.id IS 'Identifiant unique pour chaque détail de hotspot';
COMMENT ON COLUMN hotspot_details.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN hotspot_details.version IS 'Version du détail de hotspot';
COMMENT ON COLUMN hotspot_details.date_version IS 'Date de la version du détail de hotspot';
COMMENT ON COLUMN hotspot_details.severity IS 'Sévérité du hotspot';
COMMENT ON COLUMN hotspot_details.niveau IS 'Niveau de risque du hotspot';
COMMENT ON COLUMN hotspot_details.status IS 'Statut du hotspot';
COMMENT ON COLUMN hotspot_details.frontend IS 'Hotspot associée au frontend';
COMMENT ON COLUMN hotspot_details.backend IS 'Hotspot associée au backend';
COMMENT ON COLUMN hotspot_details.autre IS 'Hotspot autre';
COMMENT ON COLUMN hotspot_details.file IS 'Fichier associé au hotspot';
COMMENT ON COLUMN hotspot_details.line IS 'Ligne du fichier où se situe le hotspot';
COMMENT ON COLUMN hotspot_details.rule IS 'Règle associée au hotspot';
COMMENT ON COLUMN hotspot_details.message IS 'Message descriptif du hotspot';
COMMENT ON COLUMN hotspot_details.key IS 'Clé unique du hotspot';
COMMENT ON COLUMN hotspot_details.date_enregistrement IS 'Date d''enregistrement du détail de hotspot';

--hotspot_owasp
COMMENT ON COLUMN hotspot_owasp.id IS 'Identifiant unique pour chaque hotspot OWASP';
COMMENT ON COLUMN hotspot_owasp.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN hotspot_owasp.version IS 'Version du hotspot OWASP';
COMMENT ON COLUMN hotspot_owasp.date_version IS 'Date de la version du hotspot OWASP';
COMMENT ON COLUMN hotspot_owasp.menace IS 'Menace évaluée du hotspot OWASP';
COMMENT ON COLUMN hotspot_owasp.probability IS 'Probabilité du hotspot OWASP';
COMMENT ON COLUMN hotspot_owasp.status IS 'Statut du hotspot OWASP';
COMMENT ON COLUMN hotspot_owasp.niveau IS 'Niveau de risque du hotspot OWASP';
COMMENT ON COLUMN hotspot_owasp.date_enregistrement IS 'Date d''enregistrement du hotspot OWASP';

--hotspots
COMMENT ON COLUMN hotspots.id IS 'Identifiant unique pour chaque hotspot';
COMMENT ON COLUMN hotspots.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN hotspots.version IS 'Version du hotspot';
COMMENT ON COLUMN hotspots.date_version IS 'Date de la version du hotspot';
COMMENT ON COLUMN hotspots.key IS 'Clé unique du hotspot';
COMMENT ON COLUMN hotspots.probability IS 'Probabilité de risque du hotspot';
COMMENT ON COLUMN hotspots.status IS 'Statut du hotspot';
COMMENT ON COLUMN hotspots.niveau IS 'Niveau de risque du hotspot';
COMMENT ON COLUMN hotspots.date_enregistrement IS 'Date d''enregistrement du hotspot';

--information_projet
COMMENT ON COLUMN information_projet.id IS 'Identifiant unique pour chaque instance de InformationProjet';
COMMENT ON COLUMN information_projet.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN information_projet.analyse_key IS 'Clé d''analyse du projet';
COMMENT ON COLUMN information_projet.date IS 'Date de l''analyse du projet';
COMMENT ON COLUMN information_projet.project_version IS 'Version du projet lors de l''analyse';
COMMENT ON COLUMN information_projet.type IS 'Type d''analyse effectuée';
COMMENT ON COLUMN information_projet.date_enregistrement IS 'Date d''enregistrement de l''information du projet';

--liste_projet
COMMENT ON COLUMN liste_projet.id IS 'Identifiant unique pour chaque instance de ListeProjet';
COMMENT ON COLUMN liste_projet.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN liste_projet.name IS 'Nom du projet';
COMMENT ON COLUMN liste_projet.tags IS 'Tags associés au projet sous forme de tableau JSON';
COMMENT ON COLUMN liste_projet.visibility IS 'Visibilité du projet';
COMMENT ON COLUMN liste_projet.date_enregistrement IS 'Date d''enregistrement du projet';

--ma_moulinette
COMMENT ON COLUMN ma_moulinette.id IS 'Unique identifier for each MaMoulinette instance';
COMMENT ON COLUMN ma_moulinette.version IS 'Version number of the MaMoulinette';
COMMENT ON COLUMN ma_moulinette.date_version IS 'Date when the version was created';
COMMENT ON COLUMN ma_moulinette.date_enregistrement IS 'Date when this record was registered';

--messenger_message
COMMENT ON COLUMN messenger_messages.created_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN messenger_messages.available_at IS '(DC2Type:datetime_immutable)';
COMMENT ON COLUMN messenger_messages.delivered_at IS '(DC2Type:datetime_immutable)';

--mesures
COMMENT ON COLUMN mesures.id IS 'Identifiant unique pour chaque mesure';
COMMENT ON COLUMN mesures.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN mesures.project_name IS 'Nom du projet';
COMMENT ON COLUMN mesures.lines IS 'Nombre total de lignes du projet';
COMMENT ON COLUMN mesures.ncloc IS 'Lignes de code non commentées';
COMMENT ON COLUMN mesures.coverage IS 'Pourcentage de couverture par les tests';
COMMENT ON COLUMN mesures.sqale_debt_ratio IS 'Ratio dette technique (SQALE)';
COMMENT ON COLUMN mesures.duplication_density IS 'Densité de duplication du code';
COMMENT ON COLUMN mesures.tests IS 'Nombre total de tests';
COMMENT ON COLUMN mesures.issues IS 'Nombre total de problèmes identifiés';
COMMENT ON COLUMN mesures.date_enregistrement IS 'Date d''enregistrement de la mesure';

--no_sonar
COMMENT ON COLUMN no_sonar.id IS 'Identifiant unique pour chaque entrée NoSonar';
COMMENT ON COLUMN no_sonar.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN no_sonar.rule IS 'Règle NoSonar appliquée';
COMMENT ON COLUMN no_sonar.component IS 'Composant auquel la règle est appliquée';
COMMENT ON COLUMN no_sonar.line IS 'Ligne où la règle NoSonar est appliquée';
COMMENT ON COLUMN no_sonar.date_enregistrement IS 'Date d''enregistrement de l''entrée NoSonar';

--notes
COMMENT ON COLUMN notes.id IS 'Identifiant unique pour chaque entrée Notes';
COMMENT ON COLUMN notes.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN notes.type IS 'Type de la note';
COMMENT ON COLUMN notes.value IS 'Valeur de la note';
COMMENT ON COLUMN notes.date_enregistrement IS 'Date d''enregistrement de la note';

--owasp
COMMENT ON COLUMN owasp.id IS 'Clé d''identification unique ';
COMMENT ON COLUMN owasp.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN owasp.version IS 'Version du projet';
COMMENT ON COLUMN owasp.date_version IS 'Date de release du projet';
COMMENT ON COLUMN owasp.effort_total IS 'Effort total pour corriger les failles de sécurité';
COMMENT ON COLUMN owasp.a1 IS 'Score OWASP Top 10 - A1';
COMMENT ON COLUMN owasp.a2 IS 'Score OWASP Top 10 - A2';
COMMENT ON COLUMN owasp.a3 IS 'Score OWASP Top 10 - A3';
COMMENT ON COLUMN owasp.a4 IS 'Score OWASP Top 10 - A4';
COMMENT ON COLUMN owasp.a5 IS 'Score OWASP Top 10 - A5';
COMMENT ON COLUMN owasp.a6 IS 'Score OWASP Top 10 - A6';
COMMENT ON COLUMN owasp.a7 IS 'Score OWASP Top 10 - A7';
COMMENT ON COLUMN owasp.a8 IS 'Score OWASP Top 10 - A8';
COMMENT ON COLUMN owasp.a9 IS 'Score OWASP Top 10 - A9';
COMMENT ON COLUMN owasp.a10 IS 'Score OWASP Top 10 - A10';
COMMENT ON COLUMN owasp.a1_blocker IS 'Nombre de faille bloquante de type A1';
COMMENT ON COLUMN owasp.a1_critical IS 'Nombre de faille critique de type A1';
COMMENT ON COLUMN owasp.a1_major IS 'Nombre de faille majeurs de type A1';
COMMENT ON COLUMN owasp.a1_info IS 'Nombre de faille d’information de type A1';
COMMENT ON COLUMN owasp.a1_minor IS 'Nombre de faille mineur de type A1';
COMMENT ON COLUMN owasp.a2_blocker IS 'Nombre de faille bloqueur de type A2';
COMMENT ON COLUMN owasp.a2_critical IS 'Nombre de faille critique de type A2';
COMMENT ON COLUMN owasp.a2_major IS 'Nombre de faille majeure de type A2';
COMMENT ON COLUMN owasp.a2_info IS 'Nombre de faille d’information de type A2';
COMMENT ON COLUMN owasp.a2_minor IS 'Nombre de faille mineure de type A2';
COMMENT ON COLUMN owasp.a3_blocker IS 'Nombre de faille bloquante de type A3';
COMMENT ON COLUMN owasp.a3_critical IS 'Nombre de faille critique de type A3';
COMMENT ON COLUMN owasp.a3_major IS 'Nombre de faille majeure de type A3';
COMMENT ON COLUMN owasp.a3_info IS 'Nombre de faille d’informations de type A3';
COMMENT ON COLUMN owasp.a3_minor IS 'Nombre de faille mineure de type A3';
COMMENT ON COLUMN owasp.a4_blocker IS 'Nombre de faille bloquante de type A4';
COMMENT ON COLUMN owasp.a4_critical IS 'Nombre de faille critique de type A4';
COMMENT ON COLUMN owasp.a4_major IS 'Nombre de faille majeure de type A4';
COMMENT ON COLUMN owasp.a4_info IS 'Nombre de faille d’informations de type A4';
COMMENT ON COLUMN owasp.a4_minor IS 'Nombre de faille mineure de type A4';
COMMENT ON COLUMN owasp.a5_blocker IS 'Nombre de faille bloquante de type A5';
COMMENT ON COLUMN owasp.a5_critical IS 'Nombre de faille critique de type A5';
COMMENT ON COLUMN owasp.a5_major IS 'Nombre de faille majeure de type A5';
COMMENT ON COLUMN owasp.a5_info IS 'Nombre de faille d’informations de type A5';
COMMENT ON COLUMN owasp.a5_minor IS 'Nombre de faille mineure de type A5';
COMMENT ON COLUMN owasp.a6_blocker IS 'Nombre de faille bloquante de type A6';
COMMENT ON COLUMN owasp.a6_critical IS 'Nombre de faille critique de type A6';
COMMENT ON COLUMN owasp.a6_major IS 'Nombre de faille majeure de type A6';
COMMENT ON COLUMN owasp.a6_info IS 'Nombre de faille d’informations de type A6';
COMMENT ON COLUMN owasp.a6_minor IS 'Nombre de faille mineure de type A6';
COMMENT ON COLUMN owasp.a7_blocker IS 'Nombre de faille bloquante de type A7';
COMMENT ON COLUMN owasp.a7_critical IS 'Nombre de faille critique de type A7';
COMMENT ON COLUMN owasp.a7_major IS 'Nombre de faille majeure de type A7';
COMMENT ON COLUMN owasp.a7_info IS 'Nombre de faille d’informations de type A7';
COMMENT ON COLUMN owasp.a7_minor IS 'Nombre de faille mineure de type A7';
COMMENT ON COLUMN owasp.a8_blocker IS 'Nombre de faille bloquante de type A8';
COMMENT ON COLUMN owasp.a8_critical IS 'Nombre de faille critique de type A8';
COMMENT ON COLUMN owasp.a8_major IS 'Nombre de faille majeure de type A8';
COMMENT ON COLUMN owasp.a8_info IS 'Nombre de faille d’informations de type A8';
COMMENT ON COLUMN owasp.a8_minor IS 'Nombre de faille mineure de type A8';
COMMENT ON COLUMN owasp.a9_blocker IS 'Nombre de faille bloquante de type A9';
COMMENT ON COLUMN owasp.a9_critical IS 'Nombre de faille critique de type A9';
COMMENT ON COLUMN owasp.a9_major IS 'Nombre de faille majeure de type A9';
COMMENT ON COLUMN owasp.a9_info IS 'Nombre de faille d’informations de type A9';
COMMENT ON COLUMN owasp.a9_minor IS 'Nombre de faille mineure de type A9';
COMMENT ON COLUMN owasp.a10_blocker IS 'Nombre de faille bloquante de type A10';
COMMENT ON COLUMN owasp.a10_critical IS 'Nombre de faille critique de type A10';
COMMENT ON COLUMN owasp.a10_major IS 'Nombre de faille majeure de type A10';
COMMENT ON COLUMN owasp.a10_info IS 'Nombre de faille d’informations de type A10';
COMMENT ON COLUMN owasp.a10_minor IS 'Nombre de faille mineure de type A10';
COMMENT ON COLUMN owasp.date_enregistrement IS 'Date d’enregistrement des données';

--portfeuille
COMMENT ON COLUMN portefeuille.id IS 'Identifiant unique pour chaque portefeuille';
COMMENT ON COLUMN portefeuille.titre IS 'Titre unique du portefeuille';
COMMENT ON COLUMN portefeuille.equipe IS 'Nom de l''équipe associée au portefeuille';
COMMENT ON COLUMN portefeuille.liste IS 'Liste des éléments ou des activités du portefeuille';
COMMENT ON COLUMN portefeuille.date_modification IS 'Date de la dernière modification du portefeuille';
COMMENT ON COLUMN portefeuille.date_enregistrement IS 'Date d''enregistrement du portefeuille';

--profiles
COMMENT ON COLUMN profiles.id IS 'Identifiant unique pour chaque profil';
COMMENT ON COLUMN profiles.key IS 'Clé unique du profil';
COMMENT ON COLUMN profiles.name IS 'Nom du profil';
COMMENT ON COLUMN profiles.language_name IS 'Nom du langage de programmation';
COMMENT ON COLUMN profiles.active_rule_count IS 'Nombre de règles actives associées au profil';
COMMENT ON COLUMN profiles.rules_update_at IS 'Date de la dernière mise à jour des règles';
COMMENT ON COLUMN profiles.referentiel_default IS 'Indique si le profil est le profil par défaut';
COMMENT ON COLUMN profiles.date_enregistrement IS 'Date d''enregistrement du profil';

--profiles_historique
COMMENT ON COLUMN profiles_historique.id IS 'Identifiant unique pour chaque historique de profil';
COMMENT ON COLUMN profiles_historique.date_courte IS 'Date courte associée à l’historique';
COMMENT ON COLUMN profiles_historique.language IS 'Langage de programmation associé';
COMMENT ON COLUMN profiles_historique.date IS 'Date complète de l’événement de l’historique';
COMMENT ON COLUMN profiles_historique.action IS 'Action réalisée, par exemple modification ou création';
COMMENT ON COLUMN profiles_historique.auteur IS 'Auteur de l’action dans l’historique';
COMMENT ON COLUMN profiles_historique.regle IS 'Règle ou norme concernée par l’historique';
COMMENT ON COLUMN profiles_historique.description IS 'Description détaillée de l’événement historique';
COMMENT ON COLUMN profiles_historique.detail IS 'Détails supplémentaires ou données binaires associées à l’événement';
COMMENT ON COLUMN profiles_historique.date_enregistrement IS 'Date d’enregistrement de l’entrée historique dans la base de données';

--properties
COMMENT ON COLUMN properties.id IS 'Identifiant unique pour chaque propriété';
COMMENT ON COLUMN properties.type IS 'Type de propriété';
COMMENT ON COLUMN properties.projet_bd IS 'Identifiant du projet dans la base de données';
COMMENT ON COLUMN properties.projet_sonar IS 'Identifiant du projet dans Sonar';
COMMENT ON COLUMN properties.profil_bd IS 'Identifiant du profil dans la base de données';
COMMENT ON COLUMN properties.profil_sonar IS 'Identifiant du profil dans Sonar';
COMMENT ON COLUMN properties.date_creation IS 'Date de création de la propriété';
COMMENT ON COLUMN properties.date_modification_projet IS 'Date de la dernière modification du projet';
COMMENT ON COLUMN properties.date_modification_profil IS 'Date de la dernière modification du profil';

--repartition
COMMENT ON COLUMN repartition.id IS 'ID unique pour chaque répartition';
COMMENT ON COLUMN repartition.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN repartition.name IS 'Nom de la répartition';
COMMENT ON COLUMN repartition.component IS 'Détails du composant concerné par la répartition';
COMMENT ON COLUMN repartition.type IS 'Type de la répartition';
COMMENT ON COLUMN repartition.severity IS 'Gravité de la répartition';
COMMENT ON COLUMN repartition.setup IS 'Paramètre de configuration pour la répartition';
COMMENT ON COLUMN repartition.date_enregistrement IS 'Date d''enregistrement de la répartition dans le système';

--to.do
COMMENT ON COLUMN todo.id IS 'ID unique pour chaque tâche';
COMMENT ON COLUMN todo.maven_key IS 'Clé Maven unique pour le projet';
COMMENT ON COLUMN todo.rule IS 'Règle appliquée à la tâche';
COMMENT ON COLUMN todo.component IS 'Détails du composant concerné par la tâche';
COMMENT ON COLUMN todo.line IS 'Numéro de ligne du code associée à la tâche';
COMMENT ON COLUMN todo.date_enregistrement IS 'Date d''enregistrement de la tâche dans le système';

--utilisateur
COMMENT ON COLUMN utilisateur.id IS 'clé unique de la table';
COMMENT ON COLUMN utilisateur.prenom IS 'Prénom de l''utilisateur';
COMMENT ON COLUMN utilisateur.nom IS 'Nom de l''utilisateur';
COMMENT ON COLUMN utilisateur.avatar IS 'Avatar de l''utilisateur';
COMMENT ON COLUMN utilisateur.courriel IS 'Adresse de courriel, clé unique';
COMMENT ON COLUMN utilisateur.roles IS 'Liste des rôles';
COMMENT ON COLUMN utilisateur.equipe IS 'Liste des équipes';
COMMENT ON COLUMN utilisateur.password IS 'Mot de passe de l''utilisateur';
COMMENT ON COLUMN utilisateur.actif IS 'L''utilisateur est déseactivé';
COMMENT ON COLUMN utilisateur.preference IS 'Préférences de l''utilisateur';
COMMENT ON COLUMN utilisateur.init IS 'Indicateur de réinitilisation du mot de passe';
COMMENT ON COLUMN utilisateur.date_modification IS 'Date de modification';
COMMENT ON COLUMN utilisateur.date_enregistrement IS 'Date de création';
