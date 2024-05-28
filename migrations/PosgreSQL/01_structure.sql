/*
####################################################
##                                                ##
##         Creation des tables et des objets      ##
##               V1.3.0 - 28/05/2024              ##
##                                                ##
####################################################*/

-- 22/05/2024 : Laurent HADJADJ - Surpression des ", modification de la table notes (ajout du cle primaire unique et suppression de l'attribut date.
-- 28/05/2024 : Laurent HADJADJ - Mise à jour du script - réécriture complet

/* ##### Le script doit être lancé avec l'utilisateur propriétaire de la base, ici db_user ##### */

-- SCHEMA: ma_moulinette

DROP SCHEMA ma_moulinette ;

CREATE SCHEMA ma_moulinette AUTHORIZATION db_user;
COMMENT ON SCHEMA ma_moulinette IS 'Schéma de la base de données Ma-moulinette';

-- Table: ma_moulinette.activite

DROP TABLE ma_moulinette.activite;
CREATE TABLE ma_moulinette.activite
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  project_name character varying(64) NOT NULL,
  analyse_id character varying(26) NOT NULL,
  status character varying(16) NOT NULL,
  submitter_login character varying(32) NOT NULL,
  executed_at TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.activite OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.activite TO db_user;

COMMENT ON COLUMN ma_moulinette.activite.id IS 'Identifiant unique de l’activité';
COMMENT ON COLUMN ma_moulinette.activite.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.activite.project_name IS 'Nom du projet associé à l’activité';
COMMENT ON COLUMN ma_moulinette.activite.analyse_id IS 'Identifiant de l’analyse';
COMMENT ON COLUMN ma_moulinette.activite.status IS 'Statut de l’activité';
COMMENT ON COLUMN ma_moulinette.activite.submitter_login IS 'Login de l’utilisateur soumettant l’activité';
COMMENT ON COLUMN ma_moulinette.activite.executed_at IS 'Date et heure d’exécution de l’activité';

-- Table: ma_moulinette.anomalie

DROP TABLE ma_moulinette.anomalie;
CREATE TABLE ma_moulinette.anomalie
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  project_name character varying(128) NOT NULL,
  anomalie_total integer NOT NULL,
  dette_minute integer NOT NULL,
  dette_reliability_minute integer NOT NULL,
  dette_vulnerability_minute integer NOT NULL,
  dette_code_smell_minute integer NOT NULL,
  dette_reliability character varying(32) NOT NULL,
  dette_vulnerability character varying(32) NOT NULL,
  dette character varying(32) NOT NULL,
  dette_code_smell character varying(32) NOT NULL,
  frontend integer NOT NULL,
  backend integer NOT NULL,
  autre integer NOT NULL,
  blocker integer NOT NULL,
  critical integer NOT NULL,
  major integer NOT NULL,
  info integer NOT NULL,
  minor integer NOT NULL,
  bug integer NOT NULL,
  vulnerability integer NOT NULL,
  code_smell integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.anomalie OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.anomalie TO db_user;

COMMENT ON COLUMN ma_moulinette.anomalie.id IS 'Identifiant unique de l’anomalie';
COMMENT ON COLUMN ma_moulinette.anomalie.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.anomalie.project_name IS 'Nom du projet';
COMMENT ON COLUMN ma_moulinette.anomalie.anomalie_total IS 'Nombre total d’anomalies';
COMMENT ON COLUMN ma_moulinette.anomalie.dette_minute IS 'Minutes totales de la dette technique';
COMMENT ON COLUMN ma_moulinette.anomalie.dette_reliability_minute IS 'Minutes de la dette de fiabilité';
COMMENT ON COLUMN ma_moulinette.anomalie.dette_vulnerability_minute IS 'Minutes de la dette de vulnérabilité';
COMMENT ON COLUMN ma_moulinette.anomalie.dette_code_smell_minute IS 'Minutes de la dette de odeurs de code';
COMMENT ON COLUMN ma_moulinette.anomalie.dette_reliability IS 'Dette de fiabilité';
COMMENT ON COLUMN ma_moulinette.anomalie.dette_vulnerability IS 'Dette de vulnérabilité';
COMMENT ON COLUMN ma_moulinette.anomalie.dette IS 'Dette générale';
COMMENT ON COLUMN ma_moulinette.anomalie.dette_code_smell IS 'Dette des mauvaises pratiques';
COMMENT ON COLUMN ma_moulinette.anomalie.frontend IS 'Problèmes liés au frontend';
COMMENT ON COLUMN ma_moulinette.anomalie.backend IS 'Problèmes liés au backend';
COMMENT ON COLUMN ma_moulinette.anomalie.autre IS 'Autres problèmes techniques';
COMMENT ON COLUMN ma_moulinette.anomalie.blocker IS 'Problèmes bloquants';
COMMENT ON COLUMN ma_moulinette.anomalie.critical IS 'Problèmes critiques';
COMMENT ON COLUMN ma_moulinette.anomalie.major IS 'Problèmes majeurs';
COMMENT ON COLUMN ma_moulinette.anomalie.info IS 'Informations sur les problèmes mineurs';
COMMENT ON COLUMN ma_moulinette.anomalie.minor IS 'Problèmes mineurs';
COMMENT ON COLUMN ma_moulinette.anomalie.bug IS 'Nombre total de bugs';
COMMENT ON COLUMN ma_moulinette.anomalie.vulnerability IS 'Nombre total de vulnérabilités';
COMMENT ON COLUMN ma_moulinette.anomalie.code_smell IS 'Nombre total de mauvaises pratiques';
COMMENT ON COLUMN ma_moulinette.anomalie.date_enregistrement IS 'Date d’enregistrement de l’anomalie';

-- Table: ma_moulinette.anomalie_details

DROP TABLE ma_moulinette.anomalie_details;
CREATE TABLE ma_moulinette.anomalie_details
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  name character varying(128) NOT NULL,
  bug_blocker integer NOT NULL,
  bug_critical integer NOT NULL,
  bug_info integer NOT NULL,
  bug_major integer NOT NULL,
  bug_minor integer NOT NULL,
  vulnerability_blocker integer NOT NULL,
  vulnerability_critical integer NOT NULL,
  vulnerability_info integer NOT NULL,
  vulnerability_major integer NOT NULL,
  vulnerability_minor integer NOT NULL,
  code_smell_blocker integer NOT NULL,
  code_smell_critical integer NOT NULL,
  code_smell_info integer NOT NULL,
  code_smell_major integer NOT NULL,
  code_smell_minor integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.anomalie_details OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.anomalie_details TO db_user;

COMMENT ON COLUMN ma_moulinette.anomalie_details.id IS 'Identifiant unique pour les détails de l’anomalie';
COMMENT ON COLUMN ma_moulinette.anomalie_details.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.anomalie_details.name IS 'Nom de l’anomalie';
COMMENT ON COLUMN ma_moulinette.anomalie_details.bug_blocker IS 'Nombre de bugs bloquants';
COMMENT ON COLUMN ma_moulinette.anomalie_details.bug_critical IS 'Nombre de bugs critiques';
COMMENT ON COLUMN ma_moulinette.anomalie_details.bug_info IS 'Nombre de bugs d’information';
COMMENT ON COLUMN ma_moulinette.anomalie_details.bug_major IS 'Nombre de bugs majeurs';
COMMENT ON COLUMN ma_moulinette.anomalie_details.bug_minor IS 'Nombre de bugs mineurs';
COMMENT ON COLUMN ma_moulinette.anomalie_details.vulnerability_blocker IS 'Nombre de vulnérabilités bloquantes';
COMMENT ON COLUMN ma_moulinette.anomalie_details.vulnerability_critical IS 'Nombre de vulnérabilités critiques';
COMMENT ON COLUMN ma_moulinette.anomalie_details.vulnerability_info IS 'Nombre de vulnérabilités d’information';
COMMENT ON COLUMN ma_moulinette.anomalie_details.vulnerability_major IS 'Nombre de vulnérabilités majeures';
COMMENT ON COLUMN ma_moulinette.anomalie_details.vulnerability_minor IS 'Nombre de vulnérabilités mineures';
COMMENT ON COLUMN ma_moulinette.anomalie_details.code_smell_blocker IS 'Nombre de mauvaises pratiques bloquantes';
COMMENT ON COLUMN ma_moulinette.anomalie_details.code_smell_critical IS 'Nombre de mauvaises pratiques critiques';
COMMENT ON COLUMN ma_moulinette.anomalie_details.code_smell_info IS 'Nombre de mauvaises pratiques d’information';
COMMENT ON COLUMN ma_moulinette.anomalie_details.code_smell_major IS 'Nombre de mauvaises pratiques majeures';
COMMENT ON COLUMN ma_moulinette.anomalie_details.code_smell_minor IS 'Nombre de mauvaises pratiques mineures';
COMMENT ON COLUMN ma_moulinette.anomalie_details.date_enregistrement IS 'Date d’enregistrement des détails de l’anomalie';

-- Table: ma_moulinette.batch

DROP TABLE ma_moulinette.batch;
CREATE TABLE ma_moulinette.batch
(
  id SERIAL PRIMARY KEY,
  statut boolean NOT NULL,
  titre character varying(32) NOT NULL,
  description character varying(128) NOT NULL,
  responsable character varying(128) NOT NULL,
  portefeuille character varying(32) NOT NULL,
  nombre_projet integer NOT NULL,
  execution character varying(8) DEFAULT NULL::character varying,
  date_modification TIMESTAMP DEFAULT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.batch OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.batch TO db_user;

COMMENT ON COLUMN ma_moulinette.batch.id IS 'Identifiant unique du batch';
COMMENT ON COLUMN ma_moulinette.batch.statut IS 'Statut d’activité du batch';
COMMENT ON COLUMN ma_moulinette.batch.titre IS 'Titre du batch, unique';
COMMENT ON COLUMN ma_moulinette.batch.description IS 'Description du batch';
COMMENT ON COLUMN ma_moulinette.batch.responsable IS 'Nom de l’utilisateur responsable';
COMMENT ON COLUMN ma_moulinette.batch.portefeuille IS 'Portefeuille de projet, unique';
COMMENT ON COLUMN ma_moulinette.batch.nombre_projet IS 'Nombre de projets dans le batch';
COMMENT ON COLUMN ma_moulinette.batch.execution IS 'État d’exécution du batch';
COMMENT ON COLUMN ma_moulinette.batch.date_modification IS 'Date de la dernière modification du batch';
COMMENT ON COLUMN ma_moulinette.batch.date_enregistrement IS 'Date d’enregistrement du batch';

-- Table: ma_moulinette.batch_traitement

DROP TABLE ma_moulinette.batch_traitement;
CREATE TABLE ma_moulinette.batch_traitement
(
  id SERIAL PRIMARY KEY,
  demarrage character varying(16) NOT NULL,
  resultat boolean NOT NULL,
  titre character varying(32) NOT NULL,
  portefeuille character varying(32) NOT NULL,
  nombre_projet integer NOT NULL,
  responsable character varying(128) NOT NULL,
  debut_traitement TIMESTAMPTZ DEFAULT NULL,
  fin_traitement TIMESTAMPTZ DEFAULT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.batch_traitement OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.batch_traitement TO db_user;

COMMENT ON COLUMN ma_moulinette.batch_traitement.id IS 'Identifiant unique du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.demarrage IS 'Mode de démarrage du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.resultat IS 'Indique si le traitement a réussi ou échoué';
COMMENT ON COLUMN ma_moulinette.batch_traitement.titre IS 'Titre du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.portefeuille IS 'Nom du portefeuille de projets associé';
COMMENT ON COLUMN ma_moulinette.batch_traitement.nombre_projet IS 'Nombre de projets traités';
COMMENT ON COLUMN ma_moulinette.batch_traitement.responsable IS 'Responsable du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.debut_traitement IS 'Date et heure de début du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.fin_traitement IS 'Date et heure de fin du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.date_enregistrement IS 'Date d’enregistrement du traitement dans le système';

-- Table: ma_moulinette.equipe

DROP TABLE ma_moulinette.equipe;
CREATE TABLE ma_moulinette.equipe
(
  id SERIAL PRIMARY KEY,
  titre character varying(32) NOT NULL,
  description character varying(128) NOT NULL,
  date_modification TIMESTAMP DEFAULT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.equipe OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.equipe TO db_user;

COMMENT ON COLUMN ma_moulinette.equipe.id IS 'Identifiant unique de l’équipe';
COMMENT ON COLUMN ma_moulinette.equipe.titre IS 'Titre de l’équipe, unique';
COMMENT ON COLUMN ma_moulinette.equipe.description IS 'Description de l’équipe';
COMMENT ON COLUMN ma_moulinette.equipe.date_modification IS 'Date de la dernière modification de l’équipe';
COMMENT ON COLUMN ma_moulinette.equipe.date_enregistrement IS 'Date d’enregistrement de l’équipe';

-- Table: ma_moulinette.historique

DROP TABLE ma_moulinette.historique;
CREATE TABLE ma_moulinette.historique
(
  maven_key character varying(255) NOT NULL,
  version character varying(32) NOT NULL,
  date_version character varying(128) NOT NULL,
  nom_projet character varying(128) NOT NULL,
  version_release integer NOT NULL,
  version_snapshot integer NOT NULL,
  version_autre integer NOT NULL,
  suppress_warning integer NOT NULL,
  no_sonar integer NOT NULL,
  nombre_ligne integer NOT NULL,
  nombre_ligne_code integer NOT NULL,
  couverture double precision NOT NULL,
  duplication double precision NOT NULL,
  tests_unitaires integer NOT NULL,
  nombre_defaut integer NOT NULL,
  nombre_bug integer NOT NULL,
  nombre_vulnerability integer NOT NULL,
  nombre_code_smell integer NOT NULL,
  frontend integer NOT NULL,
  backend integer NOT NULL,
  autre integer NOT NULL,
  dette integer NOT NULL,
  sqale_debt_ratio double precision NOT NULL,
  nombre_anomalie_bloquant integer NOT NULL,
  nombre_anomalie_critique integer NOT NULL,
  nombre_anomalie_info integer NOT NULL,
  nombre_anomalie_majeur integer NOT NULL,
  nombre_anomalie_mineur integer NOT NULL,
  note_reliability character varying(16) NOT NULL,
  note_security character varying(16) NOT NULL,
  note_sqale character varying(16) NOT NULL,
  note_hotspot character varying(16) NOT NULL,
  hotspot_high integer NOT NULL,
  hotspot_medium integer NOT NULL,
  hotspot_low integer NOT NULL,
  hotspot_total integer NOT NULL,
  initial boolean NOT NULL,
  bug_blocker integer NOT NULL,
  bug_critical integer NOT NULL,
  bug_major integer NOT NULL,
  bug_minor integer NOT NULL,
  bug_info integer NOT NULL,
  vulnerability_blocker integer NOT NULL,
  vulnerability_critical integer NOT NULL,
  vulnerability_major integer NOT NULL,
  vulnerability_minor integer NOT NULL,
  vulnerability_info integer NOT NULL,
  code_smell_blocker integer NOT NULL,
  code_smell_critical integer NOT NULL,
  code_smell_major integer NOT NULL,
  code_smell_minor integer NOT NULL,
  code_smell_info integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL,
  CONSTRAINT historique_pkey PRIMARY KEY (maven_key, version, date_version)
);

ALTER TABLE ma_moulinette.historique OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.historique TO db_user;

COMMENT ON COLUMN ma_moulinette.historique.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.historique.version IS 'Version du projet dans l’historique';
COMMENT ON COLUMN ma_moulinette.historique.date_version IS 'Date de la version du projet';
COMMENT ON COLUMN ma_moulinette.historique.nom_projet IS 'Nom du projet associé à cette version';
COMMENT ON COLUMN ma_moulinette.historique.version_release IS 'Indicateur de release pour la version spécifique';
COMMENT ON COLUMN ma_moulinette.historique.version_snapshot IS 'Indicateur de snapshot pour la version spécifique';
COMMENT ON COLUMN ma_moulinette.historique.version_autre IS 'Indicateur pour les autres types de versions';
COMMENT ON COLUMN ma_moulinette.historique.suppress_warning IS 'Compteur des suppressions d’avertissements';
COMMENT ON COLUMN ma_moulinette.historique.no_sonar IS 'Compteur de l’utilisation de NoSonar';
COMMENT ON COLUMN ma_moulinette.historique.nombre_ligne IS 'Nombre total de lignes dans le projet';
COMMENT ON COLUMN ma_moulinette.historique.nombre_ligne_code IS 'Nombre total de lignes de code dans le projet';
COMMENT ON COLUMN ma_moulinette.historique.couverture IS 'Pourcentage de couverture de code par les tests';
COMMENT ON COLUMN ma_moulinette.historique.duplication IS 'Pourcentage de duplication dans le code';
COMMENT ON COLUMN ma_moulinette.historique.tests_unitaires IS 'Nombre de tests unitaires exécutés';
COMMENT ON COLUMN ma_moulinette.historique.nombre_defaut IS 'Nombre total de défauts détectés';
COMMENT ON COLUMN ma_moulinette.historique.nombre_bug IS 'Nombre total de bugs détectés';
COMMENT ON COLUMN ma_moulinette.historique.nombre_vulnerability IS 'Nombre total de vulnérabilités détectées';
COMMENT ON COLUMN ma_moulinette.historique.nombre_code_smell IS 'Nombre total de mauvaises pratiques détectés';
COMMENT ON COLUMN ma_moulinette.historique.frontend IS 'Développements spécifiques front-end';
COMMENT ON COLUMN ma_moulinette.historique.backend IS 'Développements spécifiques back-end';
COMMENT ON COLUMN ma_moulinette.historique.autre IS 'Autres développements spécifiques';
COMMENT ON COLUMN ma_moulinette.historique.dette IS 'Somme de la dette technique accumulée';
COMMENT ON COLUMN ma_moulinette.historique.sqale_debt_ratio IS 'Ratio de la dette technique (SQALE)';
COMMENT ON COLUMN ma_moulinette.historique.nombre_anomalie_bloquant IS 'Nombre d’anomalies bloquantes';
COMMENT ON COLUMN ma_moulinette.historique.nombre_anomalie_critique IS 'Nombre d’anomalies critiques';
COMMENT ON COLUMN ma_moulinette.historique.nombre_anomalie_info IS 'Nombre d’anomalies d’information';
COMMENT ON COLUMN ma_moulinette.historique.nombre_anomalie_majeur IS 'Nombre d’anomalies majeures';
COMMENT ON COLUMN ma_moulinette.historique.nombre_anomalie_mineur IS 'Nombre d’anomalies mineures';
COMMENT ON COLUMN ma_moulinette.historique.note_reliability IS 'Note de fiabilité attribuée au projet';
COMMENT ON COLUMN ma_moulinette.historique.note_security IS 'Note de sécurité attribuée au projet';
COMMENT ON COLUMN ma_moulinette.historique.note_sqale IS 'Note SQALE attribuée au projet';
COMMENT ON COLUMN ma_moulinette.historique.note_hotspot IS 'Note pour les hotspots de sécurité';
COMMENT ON COLUMN ma_moulinette.historique.hotspot_high IS 'Nombre de hotspots de sécurité de niveau élevé';
COMMENT ON COLUMN ma_moulinette.historique.hotspot_medium IS 'Nombre de hotspots de sécurité de niveau moyen';
COMMENT ON COLUMN ma_moulinette.historique.hotspot_low IS 'Nombre de hotspots de sécurité de niveau faible';
COMMENT ON COLUMN ma_moulinette.historique.hotspot_total IS 'Nombre total de hotspots de sécurité';
COMMENT ON COLUMN ma_moulinette.historique.initial IS 'Indique si c’est la version de référence';
COMMENT ON COLUMN ma_moulinette.historique.bug_blocker IS 'Nombre de bugs bloquants';
COMMENT ON COLUMN ma_moulinette.historique.bug_critical IS 'Nombre de bugs critiques';
COMMENT ON COLUMN ma_moulinette.historique.bug_major IS 'Nombre de bugs majeurs';
COMMENT ON COLUMN ma_moulinette.historique.bug_minor IS 'Nombre de bugs mineurs';
COMMENT ON COLUMN ma_moulinette.historique.bug_info IS 'Nombre de bugs d’information';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_blocker IS 'Nombre de vulnérabilités bloquantes';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_critical IS 'Nombre de vulnérabilités critiques';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_major IS 'Nombre de vulnérabilités majeures';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_minor IS 'Nombre de vulnérabilités mineures';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_info IS 'Nombre de vulnérabilités d’information';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_blocker IS 'Nombre de mauvaises pratiques bloquants';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_critical IS 'Nombre de mauvaises pratiques critiques';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_major IS 'Nombre de mauvaises pratiques majeurs';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_minor IS 'Nombre de mauvaises pratiques mineurs';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_info IS 'Nombre de mauvaises pratiques d’information';
COMMENT ON COLUMN ma_moulinette.historique.date_enregistrement IS 'Date d’enregistrement de l’historique(DC2Type:datetimetz_immutable)';

-- Table: ma_moulinette.hotspot_details

DROP TABLE ma_moulinette.hotspot_details;
CREATE TABLE ma_moulinette.hotspot_details
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  version character varying(32) NOT NULL,
  date_version TIMESTAMPTZ NOT NULL,
  severity character varying(8) NOT NULL,
  niveau integer NOT NULL,
  status character varying(16) NOT NULL,
  frontend integer NOT NULL,
  backend integer NOT NULL,
  autre integer NOT NULL,
  file character varying(255) NOT NULL,
  line integer NOT NULL,
  rule character varying(255) NOT NULL,
  message character varying(255) NOT NULL,
  key character varying(32) NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.hotspot_details OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.hotspot_details TO db_user;

COMMENT ON COLUMN ma_moulinette.hotspot_details.id IS 'Identifiant unique pour chaque détail de hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.hotspot_details.version IS 'Version du détail de hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.date_version IS 'Date de la version du détail de hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.severity IS 'Sévérité du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.niveau IS 'Niveau de risque du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.status IS 'Statut du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.frontend IS 'Implémentation frontend associée au hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.backend IS 'Implémentation backend associée au hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.autre IS 'Autres implémentations associées au hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.file IS 'Fichier associé au hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.line IS 'Ligne du fichier où se situe le hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.rule IS 'Règle associée au hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.message IS 'Message descriptif du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.key IS 'Clé unique du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.date_enregistrement IS 'Date d’enregistrement du détail de hotspot';

-- Table: ma_moulinette.hotspot_owasp

DROP TABLE ma_moulinette.hotspot_owasp;
CREATE TABLE ma_moulinette.hotspot_owasp
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  version character varying(32) NOT NULL,
  date_version TIMESTAMPTZ NOT NULL,
  menace character varying(8) NOT NULL,
  probability character varying(8) NOT NULL,
  status character varying(16) NOT NULL,
  niveau integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.hotspot_owasp OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.hotspot_owasp TO db_user;

COMMENT ON COLUMN ma_moulinette.hotspot_owasp.id IS 'Identifiant unique pour chaque hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.version IS 'Version du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.date_version IS 'Date de la version du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.menace IS 'Menace évaluée du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.probability IS 'Probabilité du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.status IS 'Statut du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.niveau IS 'Niveau de risque du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.date_enregistrement IS 'Date d’enregistrement du hotspot OWASP';

-- Table: ma_moulinette.hotspots

DROP TABLE ma_moulinette.hotspots;
CREATE TABLE ma_moulinette.hotspots
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  version character varying(32) NOT NULL,
  date_version TIMESTAMPTZ NOT NULL,
  key character varying(32) NOT NULL,
  probability character varying(8) NOT NULL,
  status character varying(16) NOT NULL,
  niveau integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.hotspots OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.hotspots TO db_user;

COMMENT ON COLUMN ma_moulinette.hotspots.id IS 'Identifiant unique pour chaque hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.hotspots.version IS 'Version du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.date_version IS 'Date de la version du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.key IS 'Clé de l’analyse du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.probability IS 'Probabilité de risque du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.status IS 'Statut du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.niveau IS 'Niveau de risque du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.date_enregistrement IS 'Date d’enregistrement du hotspot';

-- Table: ma_moulinette.information_projet

DROP TABLE ma_moulinette.information_projet;
CREATE TABLE ma_moulinette.information_projet
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  analyse_key character varying(32) NOT NULL,
  date TIMESTAMPTZ NOT NULL,
  project_version character varying(32) NOT NULL,
  type character varying(32) NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.information_projet OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.information_projet TO db_user;

COMMENT ON COLUMN ma_moulinette.information_projet.id IS 'Identifiant unique pour chaque instance de InformationProjet';
COMMENT ON COLUMN ma_moulinette.information_projet.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.information_projet.analyse_key IS 'Clé d’analyse du projet';
COMMENT ON COLUMN ma_moulinette.information_projet.date IS 'Date de l’analyse du projet';
COMMENT ON COLUMN ma_moulinette.information_projet.project_version IS 'Version du projet lors de l’analyse';
COMMENT ON COLUMN ma_moulinette.information_projet.type IS 'Type d’analyse effectuée';
COMMENT ON COLUMN ma_moulinette.information_projet.date_enregistrement IS 'Date d’enregistrement de l’information du projet';

-- Table: ma_moulinette.liste_projet

DROP TABLE ma_moulinette.liste_projet;
CREATE TABLE ma_moulinette.liste_projet
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  name character varying(128) NOT NULL,
  tags json NOT NULL,
  visibility character varying(8) NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.liste_projet OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.liste_projet TO db_user;

COMMENT ON COLUMN ma_moulinette.liste_projet.id IS 'Identifiant unique pour chaque instance de ListeProjet';
COMMENT ON COLUMN ma_moulinette.liste_projet.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.liste_projet.name IS 'Nom du projet';
COMMENT ON COLUMN ma_moulinette.liste_projet.tags IS 'Tags associés au projet sous forme de tableau JSON';
COMMENT ON COLUMN ma_moulinette.liste_projet.visibility IS 'Visibilité du projet';
COMMENT ON COLUMN ma_moulinette.liste_projet.date_enregistrement IS 'Date d’enregistrement du projet';

-- Table: ma_moulinette.ma_moulinette

DROP TABLE ma_moulinette.ma_moulinette;
CREATE TABLE ma_moulinette.ma_moulinette
(
  id SERIAL PRIMARY KEY,
  version character varying(16) NOT NULL,
  date_version TIMESTAMPTZ NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.ma_moulinette OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.ma_moulinette TO db_user;

COMMENT ON COLUMN ma_moulinette.ma_moulinette.id IS 'Unique identifier for each MaMoulinette instance';
COMMENT ON COLUMN ma_moulinette.ma_moulinette.version IS 'Numéro de version de Ma-Moulinette';
COMMENT ON COLUMN ma_moulinette.ma_moulinette.date_version IS 'Date de publication de la version';
COMMENT ON COLUMN ma_moulinette.ma_moulinette.date_enregistrement IS 'Date d’enregistrement';

    -- Table: ma_moulinette.mesures

DROP TABLE ma_moulinette.mesures;
CREATE TABLE ma_moulinette.mesures
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  project_name character varying(128) NOT NULL,
  lines integer NOT NULL,
  ncloc integer NOT NULL,
  coverage double precision NOT NULL,
  sqale_debt_ratio double precision NOT NULL,
  duplication_density double precision NOT NULL,
  tests integer NOT NULL,
  issues integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.mesures OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.mesures TO db_user;

COMMENT ON COLUMN ma_moulinette.mesures.id IS 'Identifiant unique pour chaque mesure';
COMMENT ON COLUMN ma_moulinette.mesures.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.mesures.project_name IS 'Nom du projet';
COMMENT ON COLUMN ma_moulinette.mesures.lines IS 'Nombre total de lignes du projet';
COMMENT ON COLUMN ma_moulinette.mesures.ncloc IS 'Lignes de code non commentées';
COMMENT ON COLUMN ma_moulinette.mesures.coverage IS 'Pourcentage de couverture par les tests';
COMMENT ON COLUMN ma_moulinette.mesures.sqale_debt_ratio IS 'Ratio de dette technique (SQALE)';
COMMENT ON COLUMN ma_moulinette.mesures.duplication_density IS 'Densité de duplication du code';
COMMENT ON COLUMN ma_moulinette.mesures.tests IS 'Nombre total de tests';
COMMENT ON COLUMN ma_moulinette.mesures.issues IS 'Nombre total de problèmes identifiés';
COMMENT ON COLUMN ma_moulinette.mesures.date_enregistrement IS 'Date d’enregistrement de la mesure';

-- Table: ma_moulinette.no_sonar

DROP TABLE ma_moulinette.no_sonar;
CREATE TABLE ma_moulinette.no_sonar
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  rule character varying(128) NOT NULL,
  component text NOT NULL,
  line integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.no_sonar OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.no_sonar TO db_user;

COMMENT ON COLUMN ma_moulinette.no_sonar.id IS 'Identifiant unique pour chaque entrée NoSonar';
COMMENT ON COLUMN ma_moulinette.no_sonar.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.no_sonar.rule IS 'Règle NoSonar appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.component IS 'Composant auquel la règle est appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.line IS 'Ligne où la règle NoSonar est appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.date_enregistrement IS 'Date d’enregistrement de l’entrée NoSonar';

-- Table: ma_moulinette.notes

DROP TABLE ma_moulinette.notes;
CREATE TABLE ma_moulinette.notes
(
  id SERIAL PRIMARY KEY,
  maven_key varchar(255) NOT NULL,
  type varchar(16) NOT NULL,
  value INTEGER NOT NULL,
  date_enregistrement timestamp(0) NOT NULL
);

ALTER TABLE ma_moulinette.notes OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.notes TO db_user;

COMMENT ON COLUMN ma_moulinette.notes.maven_key IS 'Clé Maven unique identifiant la note';
COMMENT ON COLUMN ma_moulinette.notes.type IS 'Type de la note';
COMMENT ON COLUMN ma_moulinette.notes.value IS 'Valeur de la note';
COMMENT ON COLUMN ma_moulinette.notes.date_enregistrement IS 'Date d’enregistrement de la note';

-- Table: ma_moulinette.owasp

DROP TABLE ma_moulinette.owasp;
CREATE TABLE ma_moulinette.owasp
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  version character varying(32) NOT NULL,
  date_version TIMESTAMPTZ NOT NULL,
  effort_total integer NOT NULL,
  a1 integer NOT NULL,
  a2 integer NOT NULL,
  a3 integer NOT NULL,
  a4 integer NOT NULL,
  a5 integer NOT NULL,
  a6 integer NOT NULL,
  a7 integer NOT NULL,
  a8 integer NOT NULL,
  a9 integer NOT NULL,
  a10 integer NOT NULL,
  a1_blocker integer NOT NULL,
  a1_critical integer NOT NULL,
  a1_major integer NOT NULL,
  a1_info integer NOT NULL,
  a1_minor integer NOT NULL,
  a2_blocker integer NOT NULL,
  a2_critical integer NOT NULL,
  a2_major integer NOT NULL,
  a2_info integer NOT NULL,
  a2_minor integer NOT NULL,
  a3_blocker integer NOT NULL,
  a3_critical integer NOT NULL,
  a3_major integer NOT NULL,
  a3_info integer NOT NULL,
  a3_minor integer NOT NULL,
  a4_blocker integer NOT NULL,
  a4_critical integer NOT NULL,
  a4_major integer NOT NULL,
  a4_info integer NOT NULL,
  a4_minor integer NOT NULL,
  a5_blocker integer NOT NULL,
  a5_critical integer NOT NULL,
  a5_major integer NOT NULL,
  a5_info integer NOT NULL,
  a5_minor integer NOT NULL,
  a6_blocker integer NOT NULL,
  a6_critical integer NOT NULL,
  a6_major integer NOT NULL,
  a6_info integer NOT NULL,
  a6_minor integer NOT NULL,
  a7_blocker integer NOT NULL,
  a7_critical integer NOT NULL,
  a7_major integer NOT NULL,
  a7_info integer NOT NULL,
  a7_minor integer NOT NULL,
  a8_blocker integer NOT NULL,
  a8_critical integer NOT NULL,
  a8_major integer NOT NULL,
  a8_info integer NOT NULL,
  a8_minor integer NOT NULL,
  a9_blocker integer NOT NULL,
  a9_critical integer NOT NULL,
  a9_major integer NOT NULL,
  a9_info integer NOT NULL,
  a9_minor integer NOT NULL,
  a10_blocker integer NOT NULL,
  a10_critical integer NOT NULL,
  a10_major integer NOT NULL,
  a10_info integer NOT NULL,
  a10_minor integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.owasp OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.owasp TO db_user;

COMMENT ON COLUMN ma_moulinette.owasp.id IS 'Clé unique pour les enregistrement de la table';
COMMENT ON COLUMN ma_moulinette.owasp.maven_key IS 'Clé unique du projet';
COMMENT ON COLUMN ma_moulinette.owasp.version IS 'version du projet';
COMMENT ON COLUMN ma_moulinette.owasp.date_version IS 'Date de publication du projet';
COMMENT ON COLUMN ma_moulinette.owasp.effort_total IS 'Effort total pour corriger les anomalies';
COMMENT ON COLUMN ma_moulinette.owasp.a1 IS 'OWASP Top 10 - A1';
COMMENT ON COLUMN ma_moulinette.owasp.a2 IS 'OWASP Top 10 - A2';
COMMENT ON COLUMN ma_moulinette.owasp.a3 IS 'OWASP Top 10 - A3';
COMMENT ON COLUMN ma_moulinette.owasp.a4 IS 'OWASP Top 10 - A4';
COMMENT ON COLUMN ma_moulinette.owasp.a5 IS 'OWASP Top 10 - A5';
COMMENT ON COLUMN ma_moulinette.owasp.a6 IS 'OWASP Top 10 - A6';
COMMENT ON COLUMN ma_moulinette.owasp.a7 IS 'OWASP Top 10 - A7';
COMMENT ON COLUMN ma_moulinette.owasp.a8 IS 'OWASP Top 10 - A8';
COMMENT ON COLUMN ma_moulinette.owasp.a9 IS 'OWASP Top 10 - A9';
COMMENT ON COLUMN ma_moulinette.owasp.a10 IS 'OWASP Top 10 - A10';
COMMENT ON COLUMN ma_moulinette.owasp.a1_blocker IS 'Nombre d’anomalies bloquantes pour A1';
COMMENT ON COLUMN ma_moulinette.owasp.a1_critical IS 'Nombre d’anomalies critiques pour A1';
COMMENT ON COLUMN ma_moulinette.owasp.a1_major IS 'Nombre d’anomalies majeures pour A1';
COMMENT ON COLUMN ma_moulinette.owasp.a1_info IS 'Nombre d’informations pour A1';
COMMENT ON COLUMN ma_moulinette.owasp.a1_minor IS 'Nombre d’anomalies mineures pour A1';
COMMENT ON COLUMN ma_moulinette.owasp.a2_blocker IS 'Nombre d’anomalies bloquantes pour A2';
COMMENT ON COLUMN ma_moulinette.owasp.a2_critical IS 'Nombre d’anomalies critiques pour A2';
COMMENT ON COLUMN ma_moulinette.owasp.a2_major IS 'Nombre d’anomalies majeures pour A2';
COMMENT ON COLUMN ma_moulinette.owasp.a2_info IS 'Nombre d’informations pour A2';
COMMENT ON COLUMN ma_moulinette.owasp.a2_minor IS 'Nombre d’anomalies mineures pour A2';
COMMENT ON COLUMN ma_moulinette.owasp.a3_blocker IS 'Nombre d’anomalies bloquantes pour A3';
COMMENT ON COLUMN ma_moulinette.owasp.a3_critical IS 'Nombre d’anomalies critiques pour A3';
COMMENT ON COLUMN ma_moulinette.owasp.a3_major IS 'Nombre d’anomalies majeures pour A3';
COMMENT ON COLUMN ma_moulinette.owasp.a3_info IS 'Nombre d’informations pour A3';
COMMENT ON COLUMN ma_moulinette.owasp.a3_minor IS 'Nombre d’anomalies mineures pour A3';
COMMENT ON COLUMN ma_moulinette.owasp.a4_blocker IS 'Nombre d’anomalies bloquantes pour A4';
COMMENT ON COLUMN ma_moulinette.owasp.a4_critical IS 'Nombre d’anomalies critiques pour A4';
COMMENT ON COLUMN ma_moulinette.owasp.a4_major IS 'Nombre d’anomalies majeures pour A4';
COMMENT ON COLUMN ma_moulinette.owasp.a4_info IS 'Nombre d’informations pour A4';
COMMENT ON COLUMN ma_moulinette.owasp.a4_minor IS 'Nombre d’anomalies mineures pour A4';
COMMENT ON COLUMN ma_moulinette.owasp.a5_blocker IS 'Nombre d’anomalies bloquantes pour A5';
COMMENT ON COLUMN ma_moulinette.owasp.a5_critical IS 'Nombre d’anomalies critiques pour A5';
COMMENT ON COLUMN ma_moulinette.owasp.a5_major IS 'Nombre d’anomalies majeures pour A5';
COMMENT ON COLUMN ma_moulinette.owasp.a5_info IS 'Nombre d’informations pour A5';
COMMENT ON COLUMN ma_moulinette.owasp.a5_minor IS 'Nombre d’anomalies mineures pour A5';
COMMENT ON COLUMN ma_moulinette.owasp.a6_blocker IS 'Nombre d’anomalies bloquantes pour A6';
COMMENT ON COLUMN ma_moulinette.owasp.a6_critical IS 'Nombre d’anomalies critiques pour A6';
COMMENT ON COLUMN ma_moulinette.owasp.a6_major IS 'Nombre d’anomalies majeures pour A6';
COMMENT ON COLUMN ma_moulinette.owasp.a6_info IS 'Nombre d’informations pour A6';
COMMENT ON COLUMN ma_moulinette.owasp.a6_minor IS 'Nombre d’anomalies mineures pour A6';
COMMENT ON COLUMN ma_moulinette.owasp.a7_blocker IS 'Nombre d’anomalies bloquantes pour A7';
COMMENT ON COLUMN ma_moulinette.owasp.a7_critical IS 'Nombre d’anomalies critiques pour A7';
COMMENT ON COLUMN ma_moulinette.owasp.a7_major IS 'Nombre d’anomalies majeures pour A7';
COMMENT ON COLUMN ma_moulinette.owasp.a7_info IS 'Nombre d’informations pour A7';
COMMENT ON COLUMN ma_moulinette.owasp.a7_minor IS 'Nombre d’anomalies mineures pour A7';
COMMENT ON COLUMN ma_moulinette.owasp.a8_blocker IS 'Nombre d’anomalies bloquantes pour A8';
COMMENT ON COLUMN ma_moulinette.owasp.a8_critical IS 'Nombre d’anomalies critiques pour A8';
COMMENT ON COLUMN ma_moulinette.owasp.a8_major IS 'Nombre d’anomalies majeures pour A8';
COMMENT ON COLUMN ma_moulinette.owasp.a8_info IS 'Nombre d’informations pour A8';
COMMENT ON COLUMN ma_moulinette.owasp.a8_minor IS 'Nombre d’anomalies mineures pour A8';
COMMENT ON COLUMN ma_moulinette.owasp.a9_blocker IS 'Nombre d’anomalies bloquantes pour A9';
COMMENT ON COLUMN ma_moulinette.owasp.a9_critical IS 'Nombre d’anomalies critiques pour A9';
COMMENT ON COLUMN ma_moulinette.owasp.a9_major IS 'Nombre d’anomalies majeures pour A9';
COMMENT ON COLUMN ma_moulinette.owasp.a9_info IS 'Nombre d’informations pour A9';
COMMENT ON COLUMN ma_moulinette.owasp.a9_minor IS 'Nombre d’anomalies mineures pour A9';
COMMENT ON COLUMN ma_moulinette.owasp.a10_blocker IS 'Nombre d’anomalies bloquantes pour A10';
COMMENT ON COLUMN ma_moulinette.owasp.a10_critical IS 'Nombre d’anomalies critiques pour A10';
COMMENT ON COLUMN ma_moulinette.owasp.a10_major IS 'Nombre d’anomalies majeures pour A10';
COMMENT ON COLUMN ma_moulinette.owasp.a10_info IS 'Nombre d’informations pour A10';
COMMENT ON COLUMN ma_moulinette.owasp.a10_minor IS 'Nombre d’anomalies mineures pour A10';
COMMENT ON COLUMN ma_moulinette.owasp.date_enregistrement IS 'Date d’enregistrement des données';

-- Table: ma_moulinette.portefeuille

DROP TABLE ma_moulinette.portefeuille;
CREATE TABLE ma_moulinette.portefeuille
(
  id SERIAL PRIMARY KEY,
  titre character varying(32) NOT NULL,
  equipe character varying(32) NOT NULL,
  liste json NOT NULL,
  date_modification TIMESTAMP DEFAULT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.portefeuille OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.portefeuille TO db_user;

COMMENT ON COLUMN ma_moulinette.portefeuille.id IS 'Identifiant unique pour chaque portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.titre IS 'Titre unique du portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.equipe IS 'Nom de l’équipe associée au portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.liste IS 'Liste des éléments ou des activités du portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.date_modification IS 'Date de la dernière modification du portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.date_enregistrement IS 'Date d’enregistrement du portefeuille';

-- Table: ma_moulinette.portefeuille_historique

DROP TABLE ma_moulinette.portefeuille_historique;
CREATE TABLE ma_moulinette.portefeuille_historique
(
  id SERIAL PRIMARY KEY,
  date_courte TIMESTAMPTZ NOT NULL,
  language character varying(16) NOT NULL,
  date TIMESTAMPTZ NOT NULL,
  action character varying(16) NOT NULL,
  auteur character varying(64) NOT NULL,
  regle character varying(128) NOT NULL,
  description text NOT NULL,
  detail bytea NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.portefeuille_historique OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.portefeuille_historique TO db_user;

COMMENT ON COLUMN ma_moulinette.portefeuille_historique.id IS 'Identifiant unique pour chaque historique de profil';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.date_courte IS 'Date courte associée à l’historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.language IS 'language de programmation associé';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.date IS 'Date complète de l’événement de l’historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.action IS 'Action réalisée, par exemple modification ou création';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.auteur IS 'Auteur de l’action dans l’historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.regle IS 'Règle ou norme concernée par l’historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.description IS 'Description détaillée de l’événement historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.detail IS 'Détails supplémentaires ou données binaires associées à l’événement';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.date_enregistrement IS 'Date d’enregistrement de l’entrée historique dans la base de données';

-- Table: ma_moulinette.profiles

DROP TABLE ma_moulinette.profiles;
CREATE TABLE ma_moulinette.profiles
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  name character varying(128) NOT NULL,
  language_name character varying(64) NOT NULL,
  active_rule_count integer NOT NULL,
  rules_update_at TIMESTAMPTZ NOT NULL,
  referentiel_default boolean NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.profiles OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.profiles TO db_user;

COMMENT ON COLUMN ma_moulinette.profiles.id IS 'Identifiant unique pour chaque profil';
COMMENT ON COLUMN ma_moulinette.profiles.maven_key IS 'Clé unique du projet';
COMMENT ON COLUMN ma_moulinette.profiles.name IS 'Nom du profil';
COMMENT ON COLUMN ma_moulinette.profiles.language_name IS 'Nom du langage de programmation';
COMMENT ON COLUMN ma_moulinette.profiles.active_rule_count IS 'Nombre de règles actives associées au profil';
COMMENT ON COLUMN ma_moulinette.profiles.rules_update_at IS 'Date de la dernière mise à jour des règles';
COMMENT ON COLUMN ma_moulinette.profiles.referentiel_default IS 'Indique si le profil est le profil par défaut';
COMMENT ON COLUMN ma_moulinette.profiles.date_enregistrement IS 'Date d’enregistrement du profil';

-- Table: ma_moulinette.properties

DROP TABLE ma_moulinette.properties;
CREATE TABLE ma_moulinette.properties
(
  id SERIAL PRIMARY KEY,
  type character varying(255) NOT NULL,
  projet_bd integer NOT NULL,
  projet_sonar integer NOT NULL,
  profil_bd integer NOT NULL,
  profil_sonar integer NOT NULL,
  date_creation TIMESTAMPTZ NOT NULL,
  date_modification_projet TIMESTAMP DEFAULT NULL,
  date_modification_profil TIMESTAMP DEFAULT NULL
);

ALTER TABLE ma_moulinette.properties OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.properties TO db_user;

COMMENT ON COLUMN ma_moulinette.properties.id IS 'Identifiant unique pour chaque propriété';
COMMENT ON COLUMN ma_moulinette.properties.type IS 'Type de propriété';
COMMENT ON COLUMN ma_moulinette.properties.projet_bd IS 'Identifiant du projet dans la base de données';
COMMENT ON COLUMN ma_moulinette.properties.projet_sonar IS 'Identifiant du projet dans Sonar';
COMMENT ON COLUMN ma_moulinette.properties.profil_bd IS 'Identifiant du profil dans la base de données';
COMMENT ON COLUMN ma_moulinette.properties.profil_sonar IS 'Identifiant du profil dans Sonar';
COMMENT ON COLUMN ma_moulinette.properties.date_creation IS 'Date de création de la propriété';
COMMENT ON COLUMN ma_moulinette.properties.date_modification_projet IS 'Date de la dernière modification du projet';
COMMENT ON COLUMN ma_moulinette.properties.date_modification_profil IS 'Date de la dernière modification du profil';

-- Table: ma_moulinette.repartition

DROP TABLE ma_moulinette.repartition;
CREATE TABLE ma_moulinette.repartition
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  name character varying(128) NOT NULL,
  component text NOT NULL,
  type character varying(16) NOT NULL,
  severity character varying(8) NOT NULL,
  setup integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.repartition OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.repartition TO db_user;

COMMENT ON COLUMN ma_moulinette.repartition.id IS 'ID unique pour chaque répartition';
COMMENT ON COLUMN ma_moulinette.repartition.maven_key IS 'Clé identification la répartition';
COMMENT ON COLUMN ma_moulinette.repartition.name IS 'Nom de la répartition';
COMMENT ON COLUMN ma_moulinette.repartition.component IS 'Détails du composant concerné par la répartition';
COMMENT ON COLUMN ma_moulinette.repartition.type IS 'Type de la répartition';
COMMENT ON COLUMN ma_moulinette.repartition.severity IS 'Gravité de la répartition';
COMMENT ON COLUMN ma_moulinette.repartition.setup IS 'Paramètre de configuration pour la répartition';
COMMENT ON COLUMN ma_moulinette.repartition.date_enregistrement IS 'Date d’enregistrement de la répartition dans le système';

-- Table: ma_moulinette.todo

DROP TABLE ma_moulinette.todo;
CREATE TABLE ma_moulinette.todo
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  rule character varying(128) NOT NULL,
  component text NOT NULL,
  line integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.todo OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.todo TO db_user;

COMMENT ON COLUMN ma_moulinette.todo.id IS 'ID unique pour chaque Todo';
COMMENT ON COLUMN ma_moulinette.todo.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.todo.rule IS 'Règle appliquée au Todo';
COMMENT ON COLUMN ma_moulinette.todo.component IS 'Détails du composant concerné par le Todo';
COMMENT ON COLUMN ma_moulinette.todo.line IS 'Numéro de ligne du code associée au Todo';
COMMENT ON COLUMN ma_moulinette.todo.date_enregistrement IS 'Date d’enregistrement du Todo';

-- Table: ma_moulinette.utilisateur

DROP TABLE ma_moulinette.utilisateur;
CREATE TABLE ma_moulinette.utilisateur
(
  id SERIAL PRIMARY KEY,
  prenom character varying(32) NOT NULL,
  nom character varying(64) NOT NULL,
  avatar character varying(128) DEFAULT NULL::character varying,
  courriel character varying(320) NOT NULL,
  roles json,
  equipe json,
  password character varying(64) NOT NULL,
  actif boolean NOT NULL DEFAULT false,
  preference json NOT NULL,
  init integer NOT NULL,
  date_modification TIMESTAMP DEFAULT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.utilisateur OWNER to db_user;
GRANT ALL ON TABLE ma_moulinette.utilisateur TO db_user;

COMMENT ON COLUMN ma_moulinette.utilisateur.id IS 'clé unique de la table';
COMMENT ON COLUMN ma_moulinette.utilisateur.prenom IS 'Prénom de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.nom IS 'Nom de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.avatar IS 'Avatar de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.courriel IS 'Adresse de courriel, clé unique';
COMMENT ON COLUMN ma_moulinette.utilisateur.roles IS 'Liste des rôles';
COMMENT ON COLUMN ma_moulinette.utilisateur.equipe IS 'Liste des équipes';
COMMENT ON COLUMN ma_moulinette.utilisateur.password IS 'Mot de passe de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.actif IS 'L’utilisateur est désactivé';
COMMENT ON COLUMN ma_moulinette.utilisateur.preference IS 'Préférences de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.init IS 'Indicateur de réinitialisation du mot de passe';
COMMENT ON COLUMN ma_moulinette.utilisateur.date_modification IS 'Date de modification';
COMMENT ON COLUMN ma_moulinette.utilisateur.date_enregistrement IS 'Date de création';
