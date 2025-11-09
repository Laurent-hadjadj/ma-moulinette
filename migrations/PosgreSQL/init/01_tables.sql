/*
####################################################
##                                                ##
##         Creation des tables et des objets      ##
##               V2.17.1 - 09/11/2025             ##
##                                                ##
####################################################*/

/* #### Le script doit être lancé avec l'utilisateur propriétaire de la base, ici db_user #### */

-- SCHEMA: ma_moulinette

DROP SCHEMA IF EXISTS ma_moulinette CASCADE;

CREATE SCHEMA ma_moulinette AUTHORIZATION db_user;
COMMENT ON SCHEMA ma_moulinette IS 'Schéma de la base de données Ma-moulinette';

-- ===============================================
-- Création de la fonction sécurisé pg_stat_activity()
-- ===============================================

CREATE FUNCTION get_pg_stat_activity() RETURNS SETOF pg_stat_activity AS $$
BEGIN
    RETURN QUERY SELECT * FROM pg_stat_activity;
END;
$$ LANGUAGE plpgsql SECURITY DEFINER;

GRANT EXECUTE ON FUNCTION get_pg_stat_activity() TO db_user;

-- ===============================================
-- Table: ma_moulinette.activity
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.activity;
CREATE TABLE IF NOT EXISTS ma_moulinette.activity
(id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  project_name character varying(64) NOT NULL,
  analyse_id character varying(26) NOT NULL,
  status character varying(16) NOT NULL,
  submitter_login character varying(32) NOT NULL,
  submitted_at TIMESTAMPTZ NOT NULL,
  started_at TIMESTAMPTZ NOT NULL,
  executed_at TIMESTAMPTZ NOT NULL,
  execution_time integer NOT NULL
);

ALTER TABLE ma_moulinette.activity OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.activity TO db_user;

COMMENT ON COLUMN ma_moulinette.activity.id IS 'Identifiant unique de la table activité';
COMMENT ON COLUMN ma_moulinette.activity.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.activity.project_name IS 'Nom du projet associé à la clé maven';
COMMENT ON COLUMN ma_moulinette.activity.analyse_id IS 'Identifiant de l’analyse du projet';
COMMENT ON COLUMN ma_moulinette.activity.status IS 'Statut du traitement d’import';
COMMENT ON COLUMN ma_moulinette.activity.submitter_login IS 'Utilisateur soumettant l’import';
COMMENT ON COLUMN ma_moulinette.activity.submitted_at IS 'Date et heure de la soumission du traitement d’import des données';
COMMENT ON COLUMN ma_moulinette.activity.started_at IS 'Date et heure du debut du traitement d’import des données';
COMMENT ON COLUMN ma_moulinette.activity.executed_at IS ' Date et heure de fin du traitement d’import des données';
COMMENT ON COLUMN ma_moulinette.activity.execution_time IS 'Temps d’execution du traitement d’import des données';

-- ===============================================
-- Table: ma_moulinette.activity_historique
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.activity_historique;
CREATE TABLE IF NOT EXISTS ma_moulinette.activity_historique
(id SERIAL PRIMARY KEY,
  year INT NOT NULL,
  day INT NOT NULL,
  "analyse" INT NOT NULL,
  analyse_average FLOAT NOT NULL,
  success INT NOT NULL,
  failed INT NOT NULL,
  success_rate FLOAT NOT NULL,
  max_time INT NOT NULL,
  date_enregistrement timestamptz NOT NULL
);

ALTER TABLE ma_moulinette.activity_historique OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.activity_historique TO db_user;

COMMENT ON COLUMN ma_moulinette.activity_historique.id IS 'Identifiant unique de la table.';
COMMENT ON COLUMN ma_moulinette.activity_historique.year IS 'Année.';
COMMENT ON COLUMN ma_moulinette.activity_historique.day IS 'Nombre jour d’activité.';
COMMENT ON COLUMN ma_moulinette.activity_historique.analyse IS 'Nombre d’analyse.';
COMMENT ON COLUMN ma_moulinette.activity_historique.analyse_average IS 'Moyenne des analyses.';
COMMENT ON COLUMN ma_moulinette.activity_historique.success IS 'Nombre d’analyse réussi.';
COMMENT ON COLUMN ma_moulinette.activity_historique.failed IS 'Nombre d’analyse en échec.';
COMMENT ON COLUMN ma_moulinette.activity_historique.success_rate IS 'Taux de réussite.';
COMMENT ON COLUMN ma_moulinette.activity_historique.max_time IS 'Temps maximum d’une analyse en seconde.';
COMMENT ON COLUMN ma_moulinette.activity_historique.date_enregistrement IS 'Date de l’enregistrement';

-- ===============================================
-- Ajout des index
-- ===============================================

CREATE INDEX idx_year ON ma_moulinette.activity_historique (year);
CREATE INDEX idx_day ON ma_moulinette.activity_historique (day);
CREATE INDEX idx_analyse ON ma_moulinette.activity_historique ("analyse");
CREATE INDEX idx_success_rate ON ma_moulinette.activity_historique (success_rate);
CREATE INDEX idx_date_enregistrement ON ma_moulinette.activity_historique (date_enregistrement);

-- ===============================================
-- Table: ma_moulinette.activity_batch_report
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.activity_batch_report;
CREATE TABLE IF NOT EXISTS ma_moulinette.activity_batch_report (
    id SERIAL PRIMARY KEY,
    date_start TIMESTAMPTZ NOT NULL,
    date_end TIMESTAMPTZ NOT NULL,
    task_count INTEGER NOT NULL DEFAULT 0,
    task_done INTEGER NOT NULL DEFAULT 0,
    page INTEGER NOT NULL DEFAULT 0,
    last_error JSON DEFAULT '[]'::json,
    date_enregistrement TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);

ALTER TABLE IF EXISTS ma_moulinette.activity_batch_report OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.activity_batch_report TO db_user;

COMMENT ON COLUMN ma_moulinette.activity_batch_report.id IS 'Identifiant unique de la table.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.date_start IS 'Date de début de l’intervalle pour l’extraction des tâches.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.date_end IS 'Date de fin de l’intervalle pour l’extraction des tâches.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.task_count IS 'Nombre total de tâches récupérées dans le lot.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.task_done IS 'Nombre de tâches traitées dans le lot.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.page IS 'Numéro de la page traitée (utilisé pour la pagination).';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.last_error IS 'Liste des erreurs rencontrées durant le traitement des tâches.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.date_enregistrement IS 'Date et heure de l’enregistrement du rapport dans la base de données.';

-- ===============================================
-- Index pour améliorer les performances sur les requêtes basées sur executed_at
-- ===============================================

DROP INDEX IF EXISTS ma_moulinette.idx_date_enregistrement;
DROP INDEX IF EXISTS ma_moulinette.idx_task_status;
DROP INDEX IF EXISTS ma_moulinette.idx_last_error_error_code;
CREATE INDEX IF NOT EXISTS idx_executed_at ON ma_moulinette.activity_batch_report (date_enregistrement ASC NULLS LAST);
CREATE INDEX IF NOT EXISTS idx_task_status ON ma_moulinette.activity_batch_report (task_count, task_done, page);
CREATE INDEX IF NOT EXISTS idx_last_error_error_code ON ma_moulinette.activity_batch_report ((last_error->>'errorCode'));

-- ===============================================
-- Table: ma_moulinette.actuator
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.actuator;
CREATE TABLE IF NOT EXISTS ma_moulinette.actuator
(
    id SERIAL PRIMARY KEY,
    maven_key character varying(255) NOT NULL,
    nom_application character varying(128) NOT NULL,
    url character varying(255) NOT NULL UNIQUE,
    actuator_user character varying(128),
    actuator_password character varying(128),
    personne character varying(128) NOT NULL,
    date_modification TIMESTAMP DEFAULT NULL::timestamp without time zone,
    date_enregistrement TIMESTAMPTZ NOT NULL,
    CONSTRAINT uq_actuator_url UNIQUE (url)
);

ALTER TABLE IF EXISTS ma_moulinette.actuator OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.actuator TO db_user;

COMMENT ON COLUMN ma_moulinette.actuator.id IS 'Identifiant unique de la table';
COMMENT ON COLUMN ma_moulinette.actuator.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.actuator.nom_application IS 'Nom de l’application.';
COMMENT ON COLUMN ma_moulinette.actuator.url IS 'URL de base du serveur.';
COMMENT ON COLUMN ma_moulinette.actuator.actuator_user IS 'Nom de l’utilisateur Actuator';
COMMENT ON COLUMN ma_moulinette.actuator.actuator_password IS 'Mot de passe de l’utilisateur Actuator';
COMMENT ON COLUMN ma_moulinette.actuator.personne IS 'Prénom et nom de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.actuator.date_modification IS 'Date de la dernière modification.';
COMMENT ON COLUMN ma_moulinette.actuator.date_enregistrement IS 'Date d’enregistrement.';

-- ===============================================
-- Table: ma_moulinette.actuator_info
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.actuator_info;
CREATE TABLE IF NOT EXISTS ma_moulinette.actuator_info
(
    id SERIAL PRIMARY KEY,
    actuator_info_description character varying(255) DEFAULT NULL::character varying,
    actuator_info_value character varying(128) DEFAULT NULL::character varying,
    actuator_id INTEGER NOT NULL,
    -- autres colonnes
    CONSTRAINT fk_actuator_info_actuator FOREIGN KEY (actuator_id) REFERENCES ma_moulinette.actuator (id) ON DELETE CASCADE
);

ALTER TABLE IF EXISTS ma_moulinette.actuator_info OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.actuator_info TO db_user;

COMMENT ON COLUMN ma_moulinette.actuator_info.id IS 'Identifiant unique de la table';
COMMENT ON COLUMN ma_moulinette.actuator_info.actuator_info_description IS 'Description courte.';
COMMENT ON COLUMN ma_moulinette.actuator_info.actuator_info_value IS 'Valeur de la clé actuator.';

-- ===============================================
-- Table: ma_moulinette.anomalie
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.anomalie;
CREATE TABLE IF NOT EXISTS ma_moulinette.anomalie
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
  inconnu integer NOT NULL,
  blocker integer NOT NULL,
  critical integer NOT NULL,
  major integer NOT NULL,
  info integer NOT NULL,
  minor integer NOT NULL,
  bug integer NOT NULL,
  vulnerability integer NOT NULL,
  code_smell integer NOT NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.anomalie OWNER TO db_user;
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
COMMENT ON COLUMN ma_moulinette.anomalie.autre IS 'Autres problèmes';
COMMENT ON COLUMN ma_moulinette.anomalie.inconnu IS 'Problèmes inconnus';
COMMENT ON COLUMN ma_moulinette.anomalie.blocker IS 'Problèmes bloquants';
COMMENT ON COLUMN ma_moulinette.anomalie.critical IS 'Problèmes critiques';
COMMENT ON COLUMN ma_moulinette.anomalie.major IS 'Problèmes majeurs';
COMMENT ON COLUMN ma_moulinette.anomalie.info IS 'Informations sur les problèmes mineurs';
COMMENT ON COLUMN ma_moulinette.anomalie.minor IS 'Problèmes mineurs';
COMMENT ON COLUMN ma_moulinette.anomalie.bug IS 'Nombre total de bugs';
COMMENT ON COLUMN ma_moulinette.anomalie.vulnerability IS 'Nombre total de vulnérabilités';
COMMENT ON COLUMN ma_moulinette.anomalie.code_smell IS 'Nombre total de mauvaises pratiques';
COMMENT ON COLUMN ma_moulinette.anomalie.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.anomalie.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.anomalie.date_enregistrement IS 'Date d’enregistrement de l’anomalie';

-- ===============================================
-- Table: ma_moulinette.anomalie_details
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.anomalie_details;
CREATE TABLE IF NOT EXISTS ma_moulinette.anomalie_details
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
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.anomalie_details OWNER TO db_user;
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
COMMENT ON COLUMN ma_moulinette.anomalie_details.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.anomalie_details.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.anomalie_details.date_enregistrement IS 'Date d’enregistrement des détails de l’anomalie';

-- ===============================================
-- Table: ma_moulinette.batch
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.batch;
CREATE TABLE IF NOT EXISTS ma_moulinette.batch
(
  id SERIAL PRIMARY KEY,
  activated boolean NOT NULL,
  titre character varying(32) NOT NULL,
  description character varying(128) NOT NULL,
  responsable character varying(128) NOT NULL,
  responsable_short character varying(64) NOT NULL,
  portefeuille character varying(32) NOT NULL,
  nombre_projet integer NOT NULL,
  execution character varying(8) DEFAULT NULL::character varying,
  traitement_id VARCHAR(36) NOT NULL,
  date_modification TIMESTAMP DEFAULT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.batch OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.batch TO db_user;

COMMENT ON COLUMN ma_moulinette.batch.id IS 'Identifiant unique du traitement';
COMMENT ON COLUMN ma_moulinette.batch.activated IS 'Statut d’activité du traitement';
COMMENT ON COLUMN ma_moulinette.batch.titre IS 'Titre du batch, unique';
COMMENT ON COLUMN ma_moulinette.batch.description IS 'Description du traitement';
COMMENT ON COLUMN ma_moulinette.batch.responsable IS 'Identifiant de l’utilisateur responsable';
COMMENT ON COLUMN ma_moulinette.batch.responsable_short IS 'Identifiant court de l’utilisateur responsable du traitement';
COMMENT ON COLUMN ma_moulinette.batch.portefeuille IS 'Portefeuille de projet, unique';
COMMENT ON COLUMN ma_moulinette.batch.nombre_projet IS 'Nombre de projets dans le traitement';
COMMENT ON COLUMN ma_moulinette.batch.execution IS 'État d’exécution du traitement';
COMMENT ON COLUMN ma_moulinette.batch.traitement_id IS 'Identifiant unique du traitement';
COMMENT ON COLUMN ma_moulinette.batch.date_modification IS 'Date de la dernière modification du traitement';
COMMENT ON COLUMN ma_moulinette.batch.date_enregistrement IS 'Date d’enregistrement du traitement';

-- ===============================================
-- Table: ma_moulinette.batch_traitement
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.batch_traitement;
CREATE TABLE IF NOT EXISTS ma_moulinette.batch_traitement
(
  id SERIAL PRIMARY KEY,
  mode_collecte character varying(32) NOT NULL,
  activated boolean NOT NULL DEFAULT False,
  success boolean DEFAULT NULL,
  pending boolean DEFAULT NULL,
  in_progress boolean NOT NULL DEFAULT False,
  titre character varying(32) NOT NULL,
  portefeuille character varying(32) NOT NULL,
  nombre_projet integer NOT NULL,
  responsable character varying(128) NOT NULL,
  responsable_short character varying(64) NOT NULL,
  debut_traitement TIMESTAMPTZ DEFAULT NULL,
  fin_traitement TIMESTAMPTZ DEFAULT NULL,
  traitement_id VARCHAR(36) NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.batch_traitement OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.batch_traitement TO db_user;

COMMENT ON COLUMN ma_moulinette.batch_traitement.id IS 'Identifiant unique du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.mode_collecte IS 'Mode de collecte du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.activated IS 'Indique si le traitement est activé ou non';
COMMENT ON COLUMN ma_moulinette.batch_traitement.success IS 'Indique si le traitement a réussi ou échoué';
COMMENT ON COLUMN ma_moulinette.batch_traitement.pending IS 'Indique si le traitement est en attente de traitement.';
COMMENT ON COLUMN ma_moulinette.batch_traitement.in_progress IS 'Indique si le traitement est en cours.';
COMMENT ON COLUMN ma_moulinette.batch_traitement.titre IS 'Titre du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.portefeuille IS 'Nom du portefeuille de projets associé';
COMMENT ON COLUMN ma_moulinette.batch_traitement.nombre_projet IS 'Nombre de projets traités';
COMMENT ON COLUMN ma_moulinette.batch_traitement.responsable IS 'Responsable du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.responsable_short IS 'Identifiant court de l’utilisateur responsable du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.debut_traitement IS 'Date et heure de début du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.fin_traitement IS 'Date et heure de fin du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.traitement_id IS 'Identifiant unique du traitement';
COMMENT ON COLUMN ma_moulinette.batch_traitement.date_enregistrement IS 'Date d’enregistrement du traitement dans le système';

-- ===============================================
-- TABLE: batch_execution
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.batch_execution CASCADE;
CREATE TABLE ma_moulinette.batch_execution (
    id SERIAL PRIMARY KEY,
    nom_traitement VARCHAR(32) NOT NULL,
    execution_id VARCHAR(36) NOT NULL,
    traitement_id VARCHAR(36) NOT NULL,
    mode_collecte VARCHAR(32) NOT NULL,
    utilisateur_collecte VARCHAR(320) NULL,
    date_enregistrement TIMESTAMPTZ NOT NULL DEFAULT NOW(),

    -- Contraintes
    CONSTRAINT uq_batch_execution_traitement UNIQUE (id),
    CONSTRAINT ck_mode_collecte CHECK (mode_collecte IN ('COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'))
);

ALTER TABLE ma_moulinette.batch_execution OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.batch_execution TO db_user;

COMMENT ON TABLE ma_moulinette.batch_execution IS 'Journal des exécutions de traitements.';
COMMENT ON COLUMN ma_moulinette.batch_execution.id IS 'Identifiant unique de la table batch_execution.';
COMMENT ON COLUMN ma_moulinette.batch_execution.nom_traitement IS 'Nom du batch exécuté.';
COMMENT ON COLUMN ma_moulinette.batch_execution.execution_id IS 'Référence unique du journal.';
COMMENT ON COLUMN ma_moulinette.batch_execution.traitement_id IS 'Référence unique du traitement.';
COMMENT ON COLUMN ma_moulinette.batch_execution.mode_collecte IS 'Mode de collecte : COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE.';
COMMENT ON COLUMN ma_moulinette.batch_execution.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte.';
COMMENT ON COLUMN ma_moulinette.batch_execution.date_enregistrement IS 'Date d’enregistrement du journal de l’exécution du batch.';

-- ===============================================
-- TABLE: batch_execution_journal
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.batch_execution_journal;
CREATE TABLE ma_moulinette.batch_execution_journal (
    id SERIAL PRIMARY KEY,
    nom_projet character varying(128) NOT NULL,
    portefeuille character varying(32) NOT NULL,
    code INTEGER NOT NULL,
    compte_rendu BYTEA NOT NULL,
    date_execution TIMESTAMPTZ NOT NULL,
    job_id INTEGER NOT NULL,

    CONSTRAINT fk_batch_execution_journal_job
        FOREIGN KEY (job_id)
        REFERENCES ma_moulinette.batch_execution (id)
        ON DELETE CASCADE
);

ALTER TABLE ma_moulinette.batch_execution_journal OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.batch_execution_journal TO db_user;

-- INDEXES

CREATE INDEX idx_batch_execution_traitement_id ON ma_moulinette.batch_execution(traitement_id);
CREATE INDEX idx_batch_execution_journal_job ON ma_moulinette.batch_execution_journal(job_id);
CREATE INDEX idx_batch_execution_date_enregistrement ON ma_moulinette.batch_execution(date_enregistrement);
CREATE INDEX idx_batch_execution_journal_date_execution ON ma_moulinette.batch_execution_journal(date_execution);

COMMENT ON TABLE ma_moulinette.batch_execution_journal IS 'Journal détaillé des collectes/exécutions associées à un batch.';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.nom_projet IS 'Nom du projet traité';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.portefeuille IS 'Nom du portefeuille de projets associé';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.id IS 'Identifiant unique de la table batch_execution_journal';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.code IS 'Code de statut du traitement (200 = OK, 500 = Erreur, etc.)';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.compte_rendu IS 'Compte rendu HTML compresssé du traitement.';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.date_execution IS 'Date d’exécution de la collecte.';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.job_id IS 'Clé étrangère vers batch_execution.id';


-- ===============================================
-- TABLE: batch_profiling
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.batch_profiling;
CREATE TABLE ma_moulinette.batch_profiling (
    id SERIAL PRIMARY KEY,
    portefeuille VARCHAR(64) NOT NULL,
    execution_reference VARCHAR(255),
    nb_projets INT NOT NULL CHECK (nb_projets > 0),
    temps_total DOUBLE PRECISION NOT NULL CHECK (temps_total >= 0),
    temps_moyen DOUBLE PRECISION NOT NULL CHECK (temps_moyen >= 0),
    memoire_peak DOUBLE PRECISION NOT NULL CHECK (memoire_peak >= 0),
    memoire_moyenne DOUBLE PRECISION NOT NULL CHECK (memoire_moyenne >= 0),
    utilisateur VARCHAR(128) NOT NULL,
    date_execution TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

ALTER TABLE ma_moulinette.batch_profiling OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.batch_profiling TO db_user;

-- INDEXES
CREATE INDEX idx_batch_profiling_portefeuille ON ma_moulinette.batch_profiling (portefeuille);
CREATE INDEX idx_batch_profiling_date ON ma_moulinette.batch_profiling (date_execution DESC);
CREATE INDEX idx_batch_profiling_utilisateur ON ma_moulinette.batch_profiling (utilisateur);

COMMENT ON TABLE ma_moulinette.batch_profiling IS 'Table des statistiques de performances pour les traitements manuels ou automatiques.';
COMMENT ON COLUMN ma_moulinette.batch_profiling.id IS 'Clé primaire auto-incrémentée.';
COMMENT ON COLUMN ma_moulinette.batch_profiling.portefeuille IS 'Nom du portefeuille traité (ex: LOT-3, JAVA, etc.).';
COMMENT ON COLUMN ma_moulinette.batch_profiling.execution_reference IS 'Référence ULID ou UUID du traitement principal (BatchExecution.executionId).';
COMMENT ON COLUMN ma_moulinette.batch_profiling.nb_projets IS 'Nombre de projets analysés durant l’exécution.';
COMMENT ON COLUMN ma_moulinette.batch_profiling.temps_total IS 'Temps total d’exécution du batch en secondes (float).';
COMMENT ON COLUMN ma_moulinette.batch_profiling.temps_moyen IS 'Temps moyen par projet en secondes.';
COMMENT ON COLUMN ma_moulinette.batch_profiling.memoire_peak IS 'Mémoire maximale utilisée durant le traitement (en Mo).';
COMMENT ON COLUMN ma_moulinette.batch_profiling.memoire_moyenne IS 'Mémoire moyenne utilisée par projet (en Mo).';
COMMENT ON COLUMN ma_moulinette.batch_profiling.utilisateur IS 'Utilisateur ayant déclenché le traitement.';
COMMENT ON COLUMN ma_moulinette.batch_profiling.date_execution IS 'Horodatage de fin d’exécution du traitement.';

-- ===============================================
-- Table: ma_moulinette.groupe
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.groupe;
CREATE TABLE IF NOT EXISTS ma_moulinette.groupe
(
  id SERIAL PRIMARY KEY,
  titre character varying(32) NOT NULL,
  description character varying(128) NOT NULL,
  date_modification TIMESTAMP DEFAULT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.groupe OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.groupe TO db_user;

COMMENT ON TABLE ma_moulinette.groupe IS 'Table des groupes utilisateurs';
COMMENT ON COLUMN ma_moulinette.groupe.id IS 'Identifiant unique de l’équipe';
COMMENT ON COLUMN ma_moulinette.groupe.titre IS 'Titre de l’équipe, unique';
COMMENT ON COLUMN ma_moulinette.groupe.description IS 'Description de l’équipe';
COMMENT ON COLUMN ma_moulinette.groupe.date_modification IS 'Date de la dernière modification de l’équipe';
COMMENT ON COLUMN ma_moulinette.groupe.date_enregistrement IS 'Date d’enregistrement de l’équipe';

-- ===============================================
-- Table: ma_moulinette.historique
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.historique;
CREATE TABLE IF NOT EXISTS ma_moulinette.historique
(
  maven_key character varying(255) NOT NULL,
  analyse_key character varying(32) NOT NULL,
  version character varying(32) NOT NULL,
  date_version character varying(128) NOT NULL,
  nom_projet character varying(128) NOT NULL,
  version_release integer NOT NULL,
  version_snapshot integer NOT NULL,
  version_autre integer NOT NULL,
  suppress_warning integer NOT NULL,
  no_sonar integer NOT NULL,
  todo integer NOT NULL,
  nombre_ligne integer NOT NULL,
  nombre_ligne_code integer NOT NULL,
  nombre_classes integer NOT NULL,
  nombre_functions integer NOT NULL,
  nombre_files integer NOT NULL,
  coverage double precision NOT NULL,
  duplicated_lines_density double precision NOT NULL,
  tests integer NOT NULL,
  violations integer NOT NULL,
  nombre_bug integer NOT NULL,
  nombre_vulnerability integer NOT NULL,
  nombre_code_smell integer NOT NULL,
  frontend integer NOT NULL,
  backend integer NOT NULL,
  autre integer NOT NULL,
  inconnu integer NOT NULL,
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
  menace_potentielle_to_review_high integer NOT NULL,
  menace_potentielle_to_review_medium integer NOT NULL,
  menace_potentielle_to_review_low integer NOT NULL,
  menace_potentielle_reviewed_high integer NOT NULL,
  menace_potentielle_reviewed_medium integer NOT NULL,
  menace_potentielle_reviewed_low integer NOT NULL,
  menace_potentielle_totale integer NOT NULL,
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
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  actuator_info json DEFAULT null,
  logger_info integer NOT NULL,
  logger_warn integer NOT NULL,
  logger_error integer NOT NULL,
  logger_debug integer NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL,
  CONSTRAINT historique_pkey PRIMARY KEY (maven_key, version, date_version)
);

ALTER TABLE ma_moulinette.historique OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.historique TO db_user;

COMMENT ON COLUMN ma_moulinette.historique.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.historique.version IS 'Version du projet dans l’historique';
COMMENT ON COLUMN ma_moulinette.historique.date_version IS 'Date de la version du projet';
COMMENT ON COLUMN ma_moulinette.historique.nom_projet IS 'Nom du projet associé à cette version';
COMMENT ON COLUMN ma_moulinette.historique.analyse_key IS 'Clé d’analyse du projet';
COMMENT ON COLUMN ma_moulinette.historique.version_release IS 'Indicateur de release pour la version spécifique';
COMMENT ON COLUMN ma_moulinette.historique.version_snapshot IS 'Indicateur de snapshot pour la version spécifique';
COMMENT ON COLUMN ma_moulinette.historique.version_autre IS 'Indicateur pour les autres types de versions';
COMMENT ON COLUMN ma_moulinette.historique.suppress_warning IS 'Compteur des suppressions d’avertissements';
COMMENT ON COLUMN ma_moulinette.historique.no_sonar IS 'Compteur de l’utilisation de NoSonar';
COMMENT ON COLUMN ma_moulinette.historique.todo IS 'Compteur de l’utilisation de todo';
COMMENT ON COLUMN ma_moulinette.historique.nombre_ligne IS 'Nombre total de lignes dans le projet';
COMMENT ON COLUMN ma_moulinette.historique.nombre_ligne_code IS 'Nombre total de lignes de code dans le projet';
COMMENT ON COLUMN ma_moulinette.historique.classes IS 'Nombre de classes dans le projet';
COMMENT ON COLUMN ma_moulinette.historique.functions IS 'Nombre de méthode/function dans le projet';
COMMENT ON COLUMN ma_moulinette.historique.files IS 'Nombre de fichier dans le projet';
COMMENT ON COLUMN ma_moulinette.historique.coverage IS 'Pourcentage de couverture de code par les tests';
COMMENT ON COLUMN ma_moulinette.historique.duplicated_lines_density IS 'Pourcentage de duplication dans le code';
COMMENT ON COLUMN ma_moulinette.historique.tests IS 'Nombre de tests unitaires exécutés';
COMMENT ON COLUMN ma_moulinette.historique.violations IS 'Nombre total de défauts détectés';
COMMENT ON COLUMN ma_moulinette.historique.nombre_bug IS 'Nombre total de bugs détectés';
COMMENT ON COLUMN ma_moulinette.historique.nombre_vulnerability IS 'Nombre total de vulnérabilités détectées';
COMMENT ON COLUMN ma_moulinette.historique.nombre_code_smell IS 'Nombre total de mauvaises pratiques détectés';
COMMENT ON COLUMN ma_moulinette.historique.frontend IS 'Développements spécifiques frontend';
COMMENT ON COLUMN ma_moulinette.historique.backend IS 'Développements spécifiques backend';
COMMENT ON COLUMN ma_moulinette.historique.autre IS 'Développements spécifiques';
COMMENT ON COLUMN ma_moulinette.historique.inconnu IS 'Développements indéterminés';
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
COMMENT ON COLUMN ma_moulinette.historique.note_hotspot IS 'Note pour les menaces potentielles de sécurité';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_to_review_high IS 'Nombre de menaces potentielles de sécurité de niveau élevé à vérifier';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_to_review_medium IS 'Nombre de menaces potentielles de sécurité de niveau moyen à vérifier';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_to_review_low IS 'Nombre de menaces potentielles de sécurité de niveau faible à vérifier';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_reviewed_high IS 'Nombre de menaces potentielles de sécurité de niveau élevé vérifié';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_reviewed_medium IS 'Nombre de menaces potentielles de sécurité de niveau moyen vérifié';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_reviewed_low IS 'Nombre de menaces potentielles de sécurité de niveau faible vérifié';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_totale IS 'Nombre total de menaces potentielles de sécurité vérifié';
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
COMMENT ON COLUMN ma_moulinette.historique.logger_info IS 'Nombre de méthode Info invoqué';
COMMENT ON COLUMN ma_moulinette.historique.logger_warn IS 'Nombre de méthode Info invoqué';
COMMENT ON COLUMN ma_moulinette.historique.logger_error IS 'Nombre de méthode Info invoqué';
COMMENT ON COLUMN ma_moulinette.historique.logger_debug IS 'Nombre de méthode Info invoqué';
COMMENT ON COLUMN ma_moulinette.historique.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.historique.utilisateur_collecte IS 'Cpmpte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.historique.actuator_info IS 'Information Actuator du projet';
COMMENT ON COLUMN ma_moulinette.historique.date_enregistrement IS 'Date d’enregistrement de l’historique';

-- ===============================================
-- Table: ma_moulinette.hotspot_details
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.hotspot_details;
CREATE TABLE IF NOT EXISTS ma_moulinette.hotspot_details
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  version character varying(32) NOT NULL,
  date_version TIMESTAMPTZ NOT NULL,
  security_category character varying(64) NOT NULL,
  rule_key character varying(128) NOT NULL,
  rule_name character varying(255) NOT NULL,
  severity character varying(8) NOT NULL,
  status character varying(16) NOT NULL,
  resolution character varying(16),
  niveau integer NOT NULL,
  frontend integer NOT NULL,
  backend integer NOT NULL,
  autre integer NOT NULL,
  file_name character varying(128) NOT NULL,
  file_path character varying(255) NOT NULL,
  line integer NOT NULL,
  message character varying(255) NOT NULL,
  hotspot_key character varying(32) NOT NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.hotspot_details OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.hotspot_details TO db_user;

COMMENT ON COLUMN ma_moulinette.hotspot_details.id IS 'Identifiant unique pour la table hotspot owasp details';
COMMENT ON COLUMN ma_moulinette.hotspot_details.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.hotspot_details.version IS 'Version du projet';
COMMENT ON COLUMN ma_moulinette.hotspot_details.date_version IS 'Date de la publication du projet';
COMMENT ON COLUMN ma_moulinette.hotspot_details.security_category IS 'Défini la catégorie de sécurité du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.rule_key IS 'Règle SonarQube associée au hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.rule_name IS 'Nom de la règle SonarQube';
COMMENT ON COLUMN ma_moulinette.hotspot_details.severity IS 'Sévérité du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.status IS 'Statut du hotspot TO_REVIEW, REVIEWED';
COMMENT ON COLUMN ma_moulinette.hotspot_details.resolution IS 'Donne pour un hotspot au statut REVIEWED son état : FIXED, SAFE, ACKNOWLEDGED';
COMMENT ON COLUMN ma_moulinette.hotspot_details.niveau IS 'Niveau de risque du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.frontend IS 'Présent dans le frontend';
COMMENT ON COLUMN ma_moulinette.hotspot_details.backend IS 'Présent dans le backend';
COMMENT ON COLUMN ma_moulinette.hotspot_details.autre IS 'Présent dans les Autres modules';
COMMENT ON COLUMN ma_moulinette.hotspot_details.file_name IS 'Nom du Fichier associé au hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.file_path IS 'Chemin du Fichier associé au hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.line IS 'Ligne du fichier où se situe le hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.message IS 'Message descriptif du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.hotspot_key IS 'Clé unique du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.hotspot_details.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.hotspot_details.date_enregistrement IS 'Date d’enregistrement du détail de hotspot';

-- ===============================================
-- Table: ma_moulinette.hotspot_owasp
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.hotspot_owasp;
CREATE TABLE IF NOT EXISTS ma_moulinette.hotspot_owasp
(
  id SERIAL PRIMARY KEY,
  referential_owasp integer DEFAULT 2017 NOT NULL,
  maven_key character varying(255) NOT NULL,
  version character varying(32) NOT NULL,
  date_version TIMESTAMPTZ NOT NULL,
  menace character varying(8) NOT NULL,
  security_category character varying(64) NOT NULL,
  rule_key character varying(255) NOT NULL,
  probability character varying(8) NOT NULL,
  status character varying(16) NOT NULL,
  resolution character varying(16),
  niveau integer NOT NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.hotspot_owasp OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.hotspot_owasp TO db_user;

COMMENT ON COLUMN ma_moulinette.hotspot_owasp.id IS 'Identifiant unique pour chaque hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.referential_owasp IS 'Référentiel OWASP 2017, 2021';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.version IS 'Version du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.date_version IS 'Date de la version du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.menace IS 'Menace évaluée du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.security_category IS 'Défini la catégorie de sécurité du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.rule_key IS 'Règle SonarQube';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.probability IS 'Probabilité du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.status IS 'Statut du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.resolution IS 'Donne pour un hotspot au statut REVIEWED son état : FIXED, SAFE, ACKNOWLEDGED';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.niveau IS 'Niveau de risque du hotspot OWASP';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.date_enregistrement IS 'Date d’enregistrement du hotspot OWASP';

-- ===============================================
-- Table: ma_moulinette.hotspots
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.hotspots;
CREATE TABLE IF NOT EXISTS ma_moulinette.hotspots
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  version character varying(32) NOT NULL,
  date_version TIMESTAMPTZ NOT NULL,
  hotspot_key character varying(32) NOT NULL,
  security_category character varying(64) NOT NULL,
  rule_key character varying(128) NOT NULL,
  probability character varying(8) NOT NULL,
  status character varying(16) NOT NULL,
  resolution character varying(16),
  niveau integer NOT NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.hotspots OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.hotspots TO db_user;

COMMENT ON COLUMN ma_moulinette.hotspots.id IS 'Identifiant unique pour chaque hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.hotspots.version IS 'Version du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.date_version IS 'Date de la version du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.hotspot_key IS 'Clé unique du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.security_category IS 'Défini la catégorie de sécurité du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.rule_key IS 'Clé de la règle SonarQube';
COMMENT ON COLUMN ma_moulinette.hotspots.probability IS 'Probabilité de risque du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.status IS 'Statut du hotspot : TO_REVIEW, REVIEWED';
COMMENT ON COLUMN ma_moulinette.hotspots.resolution IS 'Donne pour un hotspot au statut REVIEWED son état : FIXED, SAFE, ACKNOWLEDGED';
COMMENT ON COLUMN ma_moulinette.hotspots.niveau IS 'Niveau de risque du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.hotspots.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.hotspots.date_enregistrement IS 'Date d’enregistrement du hotspot';

-- ===============================================
-- Table: ma_moulinette.information_projet
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.information_projet;
CREATE TABLE IF NOT EXISTS ma_moulinette.information_projet
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  analyse_key character varying(32) NOT NULL,
  date TIMESTAMPTZ NOT NULL,
  project_version character varying(32) NOT NULL,
  type character varying(32) NOT NULL,
  version_sonar INT DEFAULT 0 NOT NULL,
  version_release_sonar INT  DEFAULT 0 NOT NULL,
  version_snapshot_sonar INT  DEFAULT 0 NOT NULL,
  version_autre_sonar INT DEFAULT 0 NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.information_projet OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.information_projet TO db_user;

COMMENT ON COLUMN ma_moulinette.information_projet.id IS 'Identifiant unique pour chaque instance de InformationProjet';
COMMENT ON COLUMN ma_moulinette.information_projet.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.information_projet.analyse_key IS 'Clé d’analyse du projet';
COMMENT ON COLUMN ma_moulinette.information_projet.date IS 'Date de l’analyse du projet';
COMMENT ON COLUMN ma_moulinette.information_projet.project_version IS 'Version du projet lors de l’analyse';
COMMENT ON COLUMN ma_moulinette.information_projet.type IS 'Type d’analyse effectuée';
COMMENT ON COLUMN ma_moulinette.information_projet.version_sonar IS 'Nombre total de version sur le serveur SonarQube.';
COMMENT ON COLUMN ma_moulinette.information_projet.version_release_sonar IS 'Nombre de version Release sur le serveur SonarQube.';
COMMENT ON COLUMN ma_moulinette.information_projet.version_snapshot_sonar IS 'Nombre de version Snapshot sur le serveur SonarQube.';
COMMENT ON COLUMN ma_moulinette.information_projet.version_autre_sonar IS 'Nombre de version Autre sur le serveur SonarQube.';
COMMENT ON COLUMN ma_moulinette.information_projet.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.information_projet.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.information_projet.date_enregistrement IS 'Date d’enregistrement de l’information du projet';

-- ===============================================
-- Table: ma_moulinette.liste_projet
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.liste_projet;
CREATE TABLE IF NOT EXISTS ma_moulinette.liste_projet
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  name character varying(128) NOT NULL,
  tags json DEFAULT '[]'::json,
  visibility character varying(8) NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.liste_projet OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.liste_projet TO db_user;

COMMENT ON COLUMN ma_moulinette.liste_projet.id IS 'Identifiant unique pour chaque instance de ListeProjet';
COMMENT ON COLUMN ma_moulinette.liste_projet.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.liste_projet.name IS 'Nom du projet';
COMMENT ON COLUMN ma_moulinette.liste_projet.tags IS 'Tags associés au projet sous forme de tableau JSON';
COMMENT ON COLUMN ma_moulinette.liste_projet.visibility IS 'Visibilité du projet';
COMMENT ON COLUMN ma_moulinette.liste_projet.date_enregistrement IS 'Date d’enregistrement du projet';

-- ===============================================
-- Table: ma_moulinette.logger
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.logger;
CREATE TABLE IF NOT EXISTS ma_moulinette.logger
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  logger_info integer NOT NULL,
  logger_warn integer NOT NULL,
  logger_error integer NOT NULL,
  logger_debug integer NOT NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.logger OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.logger TO db_user;

COMMENT ON COLUMN ma_moulinette.logger.id IS 'Identifiant unique pour chaque instance de ListeProjet';
COMMENT ON COLUMN ma_moulinette.logger.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.logger.logger_info IS 'Nombre de méthode Info invoqué';
COMMENT ON COLUMN ma_moulinette.logger.logger_warn IS 'Nombre de méthode Info invoqué';
COMMENT ON COLUMN ma_moulinette.logger.logger_error IS 'Nombre de méthode Info invoqué';
COMMENT ON COLUMN ma_moulinette.logger.logger_debug IS 'Nombre de méthode Info invoqué';
COMMENT ON COLUMN ma_moulinette.logger.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.logger.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.logger.date_enregistrement IS 'Date d’enregistrement du projet';

-- ===============================================
-- Table: ma_moulinette.ma_moulinette
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.ma_moulinette;
CREATE TABLE IF NOT EXISTS ma_moulinette.ma_moulinette
(
  id SERIAL PRIMARY KEY,
  version character varying(16) NOT NULL,
  date_version TIMESTAMPTZ NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.ma_moulinette OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.ma_moulinette TO db_user;

COMMENT ON COLUMN ma_moulinette.ma_moulinette.id IS 'Unique identifier for each MaMoulinette instance';
COMMENT ON COLUMN ma_moulinette.ma_moulinette.version IS 'Numéro de version de Ma-Moulinette';
COMMENT ON COLUMN ma_moulinette.ma_moulinette.date_version IS 'Date de publication de la version';
COMMENT ON COLUMN ma_moulinette.ma_moulinette.date_enregistrement IS 'Date d’enregistrement';

-- ===============================================
-- Table: ma_moulinette.mesures
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.mesures;
CREATE TABLE IF NOT EXISTS ma_moulinette.mesures
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  project_name character varying(128) NOT NULL,
  lines integer NOT NULL,
  ncloc integer NOT NULL,
  language_distribution JSON NOT NULL,
  coverage double precision NOT NULL,
  files integer NOT NULL,
  classes integer NOT NULL,
  functions integer NOT NULL,
  sqale_debt_ratio double precision NOT NULL,
  duplicated_lines_density double precision NOT NULL,
  tests integer NOT NULL,
  issues integer NOT NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.mesures OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.mesures TO db_user;

COMMENT ON COLUMN ma_moulinette.mesures.id IS 'Identifiant unique pour chaque mesure';
COMMENT ON COLUMN ma_moulinette.mesures.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.mesures.project_name IS 'Nom du projet';
COMMENT ON COLUMN ma_moulinette.mesures.lines IS 'Nombre total de lignes du projet';
COMMENT ON COLUMN ma_moulinette.mesures.ncloc IS 'Lignes de code non commentées';
COMMENT ON COLUMN ma_moulinette.mesures.language_distribution IS 'Distribution des langages de programmation';
COMMENT ON COLUMN ma_moulinette.mesures.files IS 'Nombre total de fichiers';
COMMENT ON COLUMN ma_moulinette.mesures.classes IS 'Nombre total de classes';
COMMENT ON COLUMN ma_moulinette.mesures.functions IS 'Nombre total de fonctions';
COMMENT ON COLUMN ma_moulinette.mesures.coverage IS 'Pourcentage de couverture par les tests';
COMMENT ON COLUMN ma_moulinette.mesures.sqale_debt_ratio IS 'Ratio de dette technique (SQALE)';
COMMENT ON COLUMN ma_moulinette.mesures.duplicated_lines_density IS 'Densité de duplication du code';
COMMENT ON COLUMN ma_moulinette.mesures.tests IS 'Nombre total de tests';
COMMENT ON COLUMN ma_moulinette.mesures.issues IS 'Nombre total de problèmes identifiés';
COMMENT ON COLUMN ma_moulinette.mesures.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.mesures.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.mesures.date_enregistrement IS 'Date d’enregistrement de la mesure';

-- ===============================================
-- Table: ma_moulinette.no_sonar
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.no_sonar;
CREATE TABLE IF NOT EXISTS ma_moulinette.no_sonar
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  rule character varying(128) NOT NULL,
  component text NOT NULL,
  line integer NOT NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.no_sonar OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.no_sonar TO db_user;

COMMENT ON COLUMN ma_moulinette.no_sonar.id IS 'Identifiant unique pour chaque entrée NoSonar';
COMMENT ON COLUMN ma_moulinette.no_sonar.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.no_sonar.rule IS 'Règle NoSonar appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.component IS 'Composant auquel la règle est appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.line IS 'Ligne où la règle NoSonar est appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.no_sonar.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.no_sonar.date_enregistrement IS 'Date d’enregistrement de l’entrée NoSonar';

-- ===============================================
-- Table: ma_moulinette.notes
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.notes;
CREATE TABLE IF NOT EXISTS ma_moulinette.notes
(
  id SERIAL PRIMARY KEY,
  maven_key varchar(255) NOT NULL,
  type varchar(16) NOT NULL,
  value INTEGER NOT NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.notes OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.notes TO db_user;

COMMENT ON COLUMN ma_moulinette.notes.maven_key IS 'Clé Maven unique identifiant la note';
COMMENT ON COLUMN ma_moulinette.notes.type IS 'Type de la note';
COMMENT ON COLUMN ma_moulinette.notes.value IS 'Valeur de la note';
COMMENT ON COLUMN ma_moulinette.notes.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.notes.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.notes.date_enregistrement IS 'Date d’enregistrement de la note';

-- ===============================================
-- Table: ma_moulinette.owasp
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.owasp;
CREATE TABLE IF NOT EXISTS ma_moulinette.owasp
(
  id SERIAL PRIMARY KEY,
  referential_owasp INTEGER DEFAULT 2017 NOT NULL,
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
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.owasp OWNER TO db_user;
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
COMMENT ON COLUMN ma_moulinette.owasp.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.owasp.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.owasp.date_enregistrement IS 'Date d’enregistrement des données';

-- ===============================================
-- Création de la table owasp_top10
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.owasp_top10;
CREATE TABLE IF NOT EXISTS ma_moulinette.owasp_top10 (
    id SERIAL PRIMARY KEY,
    year INTEGER NOT NULL,
    category character varying(255) NOT NULL,
    description TEXT NOT NULL,
    lien character varying(128) NOT NULL,
    date_enregistrement TIMESTAMPTZ DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT year_check CHECK (year >= 2000 AND year <= EXTRACT(YEAR FROM CURRENT_DATE))
);

ALTER TABLE ma_moulinette.owasp_top10 OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.owasp_top10 TO db_user;

-- Ajout de commentaires aux colonnes
COMMENT ON TABLE owasp_top10 IS 'Table des catégories OWASP Top 10 avec descriptions et années de publication';
COMMENT ON COLUMN owasp_top10.id IS 'Identifiant unique pour chaque enregistrement';
COMMENT ON COLUMN owasp_top10.year IS 'Année associée à la catégorie OWASP Top 10';
COMMENT ON COLUMN owasp_top10.category IS 'Catégorie du Top 10 OWASP, par exemple, A1 - Attaques d’injection';
COMMENT ON COLUMN owasp_top10.description IS 'Description détaillée des vulnérabilités ou attaques associées à la catégorie';
COMMENT ON COLUMN owasp_top10.lien IS 'Lien vers la page de détails.';
COMMENT ON COLUMN owasp_top10.date_enregistrement IS 'Date et heure d’enregistrement de l’entrée dans la table';

-- Création d’index pour améliorer les performances des requêtes sur la colonne year
CREATE INDEX idx_owasp_year ON owasp_top10(year);

-- ===============================================
-- Table: ma_moulinette.portefeuille
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.portefeuille;
CREATE TABLE IF NOT EXISTS ma_moulinette.portefeuille
(
  id SERIAL PRIMARY KEY,
  titre character varying(32) NOT NULL,
  groupe character varying(32) NOT NULL,
  liste json NOT NULL,
  date_modification TIMESTAMP DEFAULT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.portefeuille OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.portefeuille TO db_user;

COMMENT ON COLUMN ma_moulinette.portefeuille.id IS 'Identifiant unique pour chaque portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.titre IS 'Titre unique du portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.groupe IS 'Nom de l’équipe associée au portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.liste IS 'Liste des éléments ou des activités du portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.date_modification IS 'Date de la dernière modification du portefeuille';
COMMENT ON COLUMN ma_moulinette.portefeuille.date_enregistrement IS 'Date d’enregistrement du portefeuille';

-- ===============================================
-- Table: ma_moulinette.portefeuille_historique
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.portefeuille_historique;
CREATE TABLE IF NOT EXISTS ma_moulinette.portefeuille_historique
(
  id SERIAL PRIMARY KEY,
  date_courte TIMESTAMPTZ NOT NULL,
  language character varying(16) NOT NULL,
  date TIMESTAMPTZ NOT NULL,
  action character varying(16) NOT NULL,
  auteur character varying(64) NOT NULL,
  rule character varying(128) NOT NULL,
  description text NOT NULL,
  detail bytea NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.portefeuille_historique OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.portefeuille_historique TO db_user;

COMMENT ON COLUMN ma_moulinette.portefeuille_historique.id IS 'Identifiant unique pour chaque historique de profil';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.date_courte IS 'Date courte associée à l’historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.language IS 'language de programmation associé';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.date IS 'Date complète de l’événement de l’historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.action IS 'Action réalisée, par exemple modification ou création';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.auteur IS 'Auteur de l’action dans l’historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.rule IS 'Règle ou norme concernée par l’historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.description IS 'Description détaillée de l’événement historique';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.detail IS 'Détails supplémentaires ou données binaires associées à l’événement';
COMMENT ON COLUMN ma_moulinette.portefeuille_historique.date_enregistrement IS 'Date d’enregistrement de l’entrée historique dans la base de données';

-- ===============================================
-- Table: ma_moulinette.profiles
-- Mise à jour SonarQube de key (32-->56) en version 25.10
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.profiles;
CREATE TABLE IF NOT EXISTS ma_moulinette.profiles
(
  id SERIAL PRIMARY KEY,
  key character varying(56) NOT NULL,
  name character varying(128) NOT NULL,
  language_name character varying(64) NOT NULL,
  active_rule_count integer NOT NULL,
  rules_updated_at TIMESTAMPTZ NOT NULL,
  referential_default boolean NOT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.profiles OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.profiles TO db_user;

COMMENT ON COLUMN ma_moulinette.profiles.id IS 'Identifiant unique pour chaque profil';
COMMENT ON COLUMN ma_moulinette.profiles.key IS 'Clé unique du profil';
COMMENT ON COLUMN ma_moulinette.profiles.name IS 'Nom du profil';
COMMENT ON COLUMN ma_moulinette.profiles.language_name IS 'Nom du langage de programmation';
COMMENT ON COLUMN ma_moulinette.profiles.active_rule_count IS 'Nombre de règles actives associées au profil';
COMMENT ON COLUMN ma_moulinette.profiles.rules_updated_at IS 'Date de la dernière mise à jour des règles';
COMMENT ON COLUMN ma_moulinette.profiles.referential_default IS 'Indique si le profil est le profil par défaut';
COMMENT ON COLUMN ma_moulinette.profiles.date_enregistrement IS 'Date d’enregistrement du profil';

-- ===============================================
-- Table: ma_moulinette.profiles_historique
-- ===============================================

CREATE TABLE IF NOT EXISTS ma_moulinette.profiles_historique
(
    id SERIAL PRIMARY KEY,
    date_courte TIMESTAMPTZ NOT NULL,
    language character varying(16) NOT NULL,
    date timestamp(0) with time zone NOT NULL,
    action character varying(16) NOT NULL,
    auteur character varying(64) NOT NULL,
    rule character varying(128) NOT NULL,
    description text NOT NULL,
    detail bytea NOT NULL,
    date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.profiles_historique OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.profiles_historique TO db_user;

COMMENT ON COLUMN ma_moulinette.profiles_historique.id IS 'Identifiant unique pour chaque historique de profil';
COMMENT ON COLUMN ma_moulinette.profiles_historique.date_courte IS 'Date courte associée à l’historique';
COMMENT ON COLUMN ma_moulinette.profiles_historique.language IS 'language de programmation associé';
COMMENT ON COLUMN ma_moulinette.profiles_historique.date IS 'Date complète de l’événement de l’historique';
COMMENT ON COLUMN ma_moulinette.profiles_historique.action IS 'Action réalisée, par exemple modification ou création';
COMMENT ON COLUMN ma_moulinette.profiles_historique.auteur IS 'Auteur de l’action dans l’historique';
COMMENT ON COLUMN ma_moulinette.profiles_historique.rule IS 'Règle ou norme concernée par l’historique';
COMMENT ON COLUMN ma_moulinette.profiles_historique.description IS 'Description détaillée de l’événement historique';
COMMENT ON COLUMN ma_moulinette.profiles_historique.detail IS 'Détails supplémentaires ou données binaires associées à l’événement';
COMMENT ON COLUMN ma_moulinette.profiles_historique.date_enregistrement IS 'Date d’enregistrement de l’entrée historique';

-- ===============================================
-- Table: ma_moulinette.properties
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.properties;
CREATE TABLE IF NOT EXISTS ma_moulinette.properties
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

ALTER TABLE ma_moulinette.properties OWNER TO db_user;
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

-- ===============================================
-- Table: ma_moulinette.repartition_temp
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.repartition_temp;
CREATE UNLOGGED TABLE IF NOT EXISTS ma_moulinette.repartition_temp (
    id SERIAL PRIMARY KEY,
    maven_key character varying(255) NOT NULL,
    component text NOT NULL,
    type character varying(16) NOT NULL,
    severity character varying(8) NOT NULL,
    setup bigint NOT NULL
);

ALTER TABLE ma_moulinette.repartition_temp OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.repartition_temp TO db_user;

COMMENT ON COLUMN ma_moulinette.repartition_temp.id IS 'Identifiant unique pour chaque propriété';
COMMENT ON COLUMN ma_moulinette.repartition_temp.maven_key IS 'Clé identification du projet';
COMMENT ON COLUMN ma_moulinette.repartition_temp.type IS 'Catégorie : BUG, VULNERABILITY ou CODE_SMELL';
COMMENT ON COLUMN ma_moulinette.repartition_temp.severity IS 'Niveau de sévérité de l’anomalie';
COMMENT ON COLUMN ma_moulinette.repartition_temp.setup IS 'Timestamp en milliseconde unique pour chaque analyse';

-- ===============================================
-- Table: ma_moulinette.repartition
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.repartition;
CREATE TABLE IF NOT EXISTS ma_moulinette.repartition
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  name character varying(128) NOT NULL,
  bug_blocker int DEFAULT 0,
  bug_critical int DEFAULT 0,
  bug_major int DEFAULT 0,
  bug_minor int DEFAULT 0,
  bug_info int DEFAULT 0,
  vulnerability_blocker int DEFAULT 0,
  vulnerability_critical int DEFAULT 0,
  vulnerability_major int DEFAULT 0,
  vulnerability_minor int DEFAULT 0,
  vulnerability_info int DEFAULT 0,
  code_smell_blocker int DEFAULT 0,
  code_smell_critical int DEFAULT 0,
  code_smell_major int DEFAULT 0,
  code_smell_minor int DEFAULT 0,
  code_smell_info int DEFAULT 0,
  frontend int DEFAULT 0,
  frontend_bug_blocker int DEFAULT 0,
  frontend_bug_critical int DEFAULT 0,
  frontend_bug_major int DEFAULT 0,
  frontend_bug_minor int DEFAULT 0,
  frontend_bug_info int DEFAULT 0,
  frontend_vulnerability_blocker int DEFAULT 0,
  frontend_vulnerability_critical int DEFAULT 0,
  frontend_vulnerability_major int DEFAULT 0,
  frontend_vulnerability_minor int DEFAULT 0,
  frontend_vulnerability_info int DEFAULT 0,
  frontend_code_smell_blocker int DEFAULT 0,
  frontend_code_smell_critical int DEFAULT 0,
  frontend_code_smell_major int DEFAULT 0,
  frontend_code_smell_minor int DEFAULT 0,
  frontend_code_smell_info int DEFAULT 0,
  backend int DEFAULT 0,
  backend_bug_blocker int DEFAULT 0,
  backend_bug_critical int DEFAULT 0,
  backend_bug_major int DEFAULT 0,
  backend_bug_minor int DEFAULT 0,
  backend_bug_info int DEFAULT 0,
  backend_vulnerability_blocker int DEFAULT 0,
  backend_vulnerability_critical int DEFAULT 0,
  backend_vulnerability_major int DEFAULT 0,
  backend_vulnerability_minor int DEFAULT 0,
  backend_vulnerability_info int DEFAULT 0,
  backend_code_smell_blocker int DEFAULT 0,
  backend_code_smell_critical int DEFAULT 0,
  backend_code_smell_major int DEFAULT 0,
  backend_code_smell_minor int DEFAULT 0,
  backend_code_smell_info int DEFAULT 0,
  autre int DEFAULT 0,
  autre_bug_blocker int DEFAULT 0,
  autre_bug_critical int DEFAULT 0,
  autre_bug_major int DEFAULT 0,
  autre_bug_minor int DEFAULT 0,
  autre_bug_info int DEFAULT 0,
  autre_vulnerability_blocker int DEFAULT 0,
  autre_vulnerability_critical int DEFAULT 0,
  autre_vulnerability_major int DEFAULT 0,
  autre_vulnerability_minor int DEFAULT 0,
  autre_vulnerability_info int DEFAULT 0,
  autre_code_smell_blocker int DEFAULT 0,
  autre_code_smell_critical int DEFAULT 0,
  autre_code_smell_major int DEFAULT 0,
  autre_code_smell_minor int DEFAULT 0,
  autre_code_smell_info int DEFAULT 0,
  inconnu int DEFAULT 0,
  inconnu_bug_blocker int DEFAULT 0,
  inconnu_bug_critical int DEFAULT 0,
  inconnu_bug_major int DEFAULT 0,
  inconnu_bug_minor int DEFAULT 0,
  inconnu_bug_info int DEFAULT 0,
  inconnu_vulnerability_blocker int DEFAULT 0,
  inconnu_vulnerability_critical int DEFAULT 0,
  inconnu_vulnerability_major int DEFAULT 0,
  inconnu_vulnerability_minor int DEFAULT 0,
  inconnu_vulnerability_info int DEFAULT 0,
  inconnu_code_smell_blocker int DEFAULT 0,
  inconnu_code_smell_critical int DEFAULT 0,
  inconnu_code_smell_major int DEFAULT 0,
  inconnu_code_smell_minor int DEFAULT 0,
  inconnu_code_smell_info int DEFAULT 0,
  setup bigint NOT NULL,
  control character varying(32) NOT NULL DEFAULT 'initial',
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.repartition OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.repartition TO db_user;

COMMENT ON COLUMN ma_moulinette.repartition.id IS 'ID unique pour chaque répartition';
COMMENT ON COLUMN ma_moulinette.repartition.maven_key IS 'Clé identification du projet';
COMMENT ON COLUMN ma_moulinette.repartition.name IS 'Nom de l’application';
COMMENT ON COLUMN ma_moulinette.repartition.bug_blocker IS 'Nombre de bug bloquant';
COMMENT ON COLUMN ma_moulinette.repartition.bug_critical IS 'Nombre de bug critique';
COMMENT ON COLUMN ma_moulinette.repartition.bug_major IS 'Nombre de bug majeur';
COMMENT ON COLUMN ma_moulinette.repartition.bug_minor IS 'Nombre de bug mineur';
COMMENT ON COLUMN ma_moulinette.repartition.bug_info IS 'Nombre de bug info';

COMMENT ON COLUMN ma_moulinette.repartition.vulnerability_blocker IS 'Nombre de vulnérabilité bloquante';
COMMENT ON COLUMN ma_moulinette.repartition.vulnerability_critical IS 'Nombre de vulnérabilité critique';
COMMENT ON COLUMN ma_moulinette.repartition.vulnerability_major IS 'Nombre de vulnérabilité majeure';
COMMENT ON COLUMN ma_moulinette.repartition.vulnerability_minor IS 'Nombre de vulnérabilité mineure';
COMMENT ON COLUMN ma_moulinette.repartition.vulnerability_info IS 'Nombre de vulnérabilité en info';

COMMENT ON COLUMN ma_moulinette.repartition.code_smell_blocker IS 'Nombre de mauvaise pratique bloquante';
COMMENT ON COLUMN ma_moulinette.repartition.code_smell_critical IS 'Nombre de mauvaise pratique critique';
COMMENT ON COLUMN ma_moulinette.repartition.code_smell_major IS 'Nombre de mauvaise pratique majeure';
COMMENT ON COLUMN ma_moulinette.repartition.code_smell_minor IS 'Nombre de mauvaise pratique mineure';
COMMENT ON COLUMN ma_moulinette.repartition.code_smell_info IS 'Nombre de mauvaise pratique en info';

COMMENT ON COLUMN ma_moulinette.repartition.frontend IS 'Répartition des anomalies de l’application frontend';
COMMENT ON COLUMN ma_moulinette.repartition.backend IS 'Répartition des anomalies de l’application backend';
COMMENT ON COLUMN ma_moulinette.repartition.autre IS 'Répartition des anomalies de l’application autres';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu IS 'Répartition des anomalies de l’application non définies';

COMMENT ON COLUMN ma_moulinette.repartition.frontend_bug_blocker IS 'Nombre de bug bloquant (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_bug_critical IS 'Nombre de bug critique (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_bug_major IS 'Nombre de bug majeur (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_bug_minor IS 'Nombre de bug mineur (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_bug_info IS 'Nombre de bug informatif (frontend)';

COMMENT ON COLUMN ma_moulinette.repartition.frontend_vulnerability_blocker IS 'Nombre de vulnérabilité bloquante (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_vulnerability_critical IS 'Nombre de vulnérabilité critique (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_vulnerability_major IS 'Nombre de vulnérabilité majeure (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_vulnerability_minor IS 'Nombre de vulnérabilité mineure (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_vulnerability_info IS 'Nombre de vulnérabilité informative (frontend)';

COMMENT ON COLUMN ma_moulinette.repartition.frontend_code_smell_blocker IS 'Nombre de code smell bloquant (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_code_smell_critical IS 'Nombre de code smell critique (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_code_smell_major IS 'Nombre de code smell majeur (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_code_smell_minor IS 'Nombre de code smell mineur (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.frontend_code_smell_info IS 'Nombre de code smell informatif (frontend)';

COMMENT ON COLUMN ma_moulinette.repartition.backend_bug_blocker IS 'Nombre de bug bloquant (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_bug_critical IS 'Nombre de bug critique (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_bug_major IS 'Nombre de bug majeur (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_bug_minor IS 'Nombre de bug mineur (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_bug_info IS 'Nombre de bug informatif (backend)';

COMMENT ON COLUMN ma_moulinette.repartition.backend_vulnerability_blocker IS 'Nombre de vulnérabilité bloquante (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_vulnerability_critical IS 'Nombre de vulnérabilité critique (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_vulnerability_major IS 'Nombre de vulnérabilité majeure (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_vulnerability_minor IS 'Nombre de vulnérabilité mineure (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_vulnerability_info IS 'Nombre de vulnérabilité informative (backend)';

COMMENT ON COLUMN ma_moulinette.repartition.backend_code_smell_blocker IS 'Nombre de code smell bloquant (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_code_smell_critical IS 'Nombre de code smell critique (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_code_smell_major IS 'Nombre de code smell majeur (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_code_smell_minor IS 'Nombre de code smell mineur (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend_code_smell_info IS 'Nombre de code smell informatif (backend)';

COMMENT ON COLUMN ma_moulinette.repartition.autre_bug_blocker IS 'Nombre de bug bloquant (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_bug_critical IS 'Nombre de bug critique (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_bug_major IS 'Nombre de bug majeur (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_bug_minor IS 'Nombre de bug mineur (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_bug_info IS 'Nombre de bug informatif (autre)';

COMMENT ON COLUMN ma_moulinette.repartition.autre_vulnerability_blocker IS 'Nombre de vulnérabilité bloquante (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_vulnerability_critical IS 'Nombre de vulnérabilité critique (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_vulnerability_major IS 'Nombre de vulnérabilité majeure (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_vulnerability_minor IS 'Nombre de vulnérabilité mineure (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_vulnerability_info IS 'Nombre de vulnérabilité informative (autre)';

COMMENT ON COLUMN ma_moulinette.repartition.autre_code_smell_blocker IS 'Nombre de code smell bloquant (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_code_smell_critical IS 'Nombre de code smell critique (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_code_smell_major IS 'Nombre de code smell majeur (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_code_smell_minor IS 'Nombre de code smell mineur (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.autre_code_smell_info IS 'Nombre de code smell informatif (autre)';

COMMENT ON COLUMN ma_moulinette.repartition.inconnu_bug_blocker IS 'Nombre de bug bloquant (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_bug_critical IS 'Nombre de bug critique (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_bug_major IS 'Nombre de bug majeur (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_bug_minor IS 'Nombre de bug mineur (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_bug_info IS 'Nombre de bug informatif (inconnu)';

COMMENT ON COLUMN ma_moulinette.repartition.inconnu_vulnerability_blocker IS 'Nombre de vulnérabilité bloquante (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_vulnerability_critical IS 'Nombre de vulnérabilité critique (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_vulnerability_major IS 'Nombre de vulnérabilité majeure (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_vulnerability_minor IS 'Nombre de vulnérabilité mineure (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_vulnerability_info IS 'Nombre de vulnérabilité informative (inconnu)';

COMMENT ON COLUMN ma_moulinette.repartition.inconnu_code_smell_blocker IS 'Nombre de code smell bloquant (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_code_smell_critical IS 'Nombre de code smell critique (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_code_smell_major IS 'Nombre de code smell majeur (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_code_smell_minor IS 'Nombre de code smell mineur (inconnu)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu_code_smell_info IS 'Nombre de code smell informatif (inconnu)';

COMMENT ON COLUMN ma_moulinette.repartition.setup IS 'Timestamp en milliseconde unique pour chaque analyse';
COMMENT ON COLUMN ma_moulinette.repartition.control IS 'Indique l’état d’avancement du process';

COMMENT ON COLUMN ma_moulinette.repartition.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.repartition.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.repartition.date_enregistrement IS 'Date d’enregistrement de la répartition dans le système';

-- ===============================================
-- Table: ma_moulinette.todo
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.todo;
CREATE TABLE IF NOT EXISTS ma_moulinette.todo
(
  id SERIAL PRIMARY KEY,
  maven_key character varying(255) NOT NULL,
  rule character varying(128) NOT NULL,
  component text NOT NULL,
  line integer NOT NULL,
  mode_collecte character varying(32),
  utilisateur_collecte character varying(320),
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.todo OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.todo TO db_user;

COMMENT ON COLUMN ma_moulinette.todo.id IS 'ID unique pour chaque Todo';
COMMENT ON COLUMN ma_moulinette.todo.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.todo.rule IS 'Règle appliquée au Todo';
COMMENT ON COLUMN ma_moulinette.todo.component IS 'Détails du composant concerné par le Todo';
COMMENT ON COLUMN ma_moulinette.todo.line IS 'Numéro de ligne du code associée au Todo';
COMMENT ON COLUMN ma_moulinette.todo.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.todo.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.todo.date_enregistrement IS 'Date d’enregistrement du Todo';

-- ===============================================
-- Table: ma_moulinette.utilisateur
-- ===============================================

DROP TABLE IF EXISTS ma_moulinette.utilisateur;
CREATE TABLE IF NOT EXISTS ma_moulinette.utilisateur
(
  id SERIAL PRIMARY KEY,
  prenom character varying(32) NOT NULL,
  nom character varying(64) NOT NULL,
  avatar character varying(128) DEFAULT NULL::character varying,
  courriel character varying(320) NOT NULL,
  roles json,
  groupe json,
  password character varying(64) NOT NULL,
  actif boolean NOT NULL DEFAULT false,
  preference json NOT NULL,
  reset_password BOOLEAN DEFAULT TRUE NOT NULL,
  reset_password_count SMALLINT DEFAULT 1 NOT NULL,
  date_modification TIMESTAMP DEFAULT NULL,
  date_enregistrement TIMESTAMPTZ NOT NULL
);

ALTER TABLE ma_moulinette.utilisateur OWNER TO db_user;
GRANT ALL ON TABLE ma_moulinette.utilisateur TO db_user;

COMMENT ON COLUMN ma_moulinette.utilisateur.id IS 'clé unique de la table';
COMMENT ON COLUMN ma_moulinette.utilisateur.prenom IS 'Prénom de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.nom IS 'Nom de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.avatar IS 'Avatar de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.courriel IS 'Adresse de courriel, clé unique';
COMMENT ON COLUMN ma_moulinette.utilisateur.roles IS 'Liste des rôles';
COMMENT ON COLUMN ma_moulinette.utilisateur.groupe IS 'Liste des équipes';
COMMENT ON COLUMN ma_moulinette.utilisateur.password IS 'Mot de passe de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.actif IS 'L’utilisateur est désactivé';
COMMENT ON COLUMN ma_moulinette.utilisateur.preference IS 'Préférences de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.utilisateur.reset_password IS 'Indicateur de réinitialisation du mot de passe';
COMMENT ON COLUMN ma_moulinette.utilisateur.reset_password_count IS 'Nombre de tentative avant blocage';
COMMENT ON COLUMN ma_moulinette.utilisateur.date_modification IS 'Date de modification';
COMMENT ON COLUMN ma_moulinette.utilisateur.date_enregistrement IS 'Date de création';

-- ===============================================
-- Ajout des indexes sur toutes tables ayant un attribut maven_key
-- ===============================================

DO
$$
DECLARE
  tbl_name text;
  column_exists boolean;
BEGIN
  FOR tbl_name IN (SELECT table_name FROM information_schema.tables WHERE table_schema = 'ma_moulinette')
  LOOP
      EXECUTE format('SELECT COUNT(*) > 0 FROM information_schema.columns WHERE table_name = %L AND column_name = %L', tbl_name, 'maven_key') INTO column_exists;
      IF column_exists THEN
        EXECUTE format('CREATE INDEX idx_%I_maven_key ON %I.%I (maven_key)', tbl_name, 'ma_moulinette', tbl_name);
      END IF;
  END LOOP;
END
$$;

--------------------------------------------------------------------
-----                                                        -------
-----                Historique des changements              -------
-----                                                        -------
--------------------------------------------------------------------

-- 22/05/2024 : Laurent HADJADJ - Surpression des ", modification de la table notes (ajout du cle primaire unique et suppression de l'attribut date.
-- 28/05/2024 : Laurent HADJADJ - Mise à jour du script - réécriture complet
-- 29/05/2024 : Laurent HADJADJ - Mise à jour de la table Activite (Quentin)
-- 30/05/2024 : Laurent HADJADJ - Ajout des attributs resolution et securityCategory pour la table hotspot
-- 02/06/2024 : Laurent HADJADJ - Ajout de l'attribut mode_collecte dans toutes les tables de collecte pour insérer le type de collecte : [COLLECTE], [TRAITEMENT MANUEL], [TRAITEMENT AUTOMATIQUE]
-- 02/06/2024 : Laurent HADJADJ - Ajout de l'attribut utilisateur_collecte dans toutes les tables de collecte pour insérer l'identifiant de l'utilisateur : [batch] ou [prenom.nom]
-- 03/06/2024 : Laurent HADJADJ - Renommage duplication en duplication_density dans la table historique
-- 04/06/2024 : Laurent HADJADJ - Ajout de l'attribut todo dans la table historique.
-- 06/06/2024 : Laurent HADJADJ - Ajout de la mesure du temps d'execution des requêtes.
-- 21/06/2024 : Laurent HADJADJ - Ajout des tables actuator et actuator_info.
-- 23/06/2024 : Laurent HADJADJ - Correction du code pour la création des tables actuator et actuator_info (ajout des contraintes et de la clé étrangère).
-- 23/06/2024 : Laurent HADJADJ - Ajout d'un indexe sur l'attribut maven_key.
-- 27/06/2024 : Laurent HADJADJ - Ajout de l'attribut analyse_key dans la table historique.
-- 10/07/2024 : Laurent HADJADJ - Ajout de la table logger.
-- 14/07/2024 : Laurent HADJADJ - Ajout de la colonne  language_distribution dans la table mesure ;
-- 16/07/2024 : Laurent HADJADJ - Correction du type JSON[] en JSON pour l'attribut language_distribution ;
-- 29/07/2024 : Laurent HADJADJ - Correction du commentaire de l'attribut modeCollecte et utilisateurCollecte, correction de la longueur du champ pour utilisateurCollecte ;
-- 31/07/2024 : Laurent HADJADJ - Ajout de la table activite_historique (Quentin) ;
-- 31/07/2024 : Laurent HADJADJ - Correction du commentaire moyenne_analyse.
-- 31/07/2024 : Laurent HADJADJ - Correction Taux_reussite en FLOAT
-- 04/08/2024 : Laurent HADJADJ - Correction du nom de la table activite_historique ;
-- 04/08/2024 : Laurent HADJADJ - Ajout de la table profiles_historique ;
-- 07/11/2024 : Laurent HADJADJ - Mise à jour de la la table profiles ;
-- 19/11/2024 : Laurent HADJADJ - Ajout de la table Owasp_Top10 ;
-- 20/11/2024 : Laurent HADJADJ - Ajout de la colonne referential_owasp dans la table owasp ;
-- 22/11/2024 : Laurent HADJADJ - Ajout de la colonne lien dans la table owasp_top10 ;
-- 22/12/2024 : Laurent HADJADJ - Renommage des table activite et activite_historique ;
-- 23/12/2024 : Laurent HADJADJ - Correction "analyse" et ajout des indexes ;
-- 24/12/2024 : Laurent HADJADJ - Renommage fail en failed ;
-- 24/12/2024 : Laurent HADJADJ - Correction de la création de la table activity_historique ;
-- 26/12/2024 : Laurent HADJADJ - Ajout de la table activity_batch_report ;
-- 27/12/2024 : Laurent HADJADJ - Réorganisation de la table ;
-- 28/12/2024 : Laurent HADJADJ - Le type de l'attribut erreur de la table activity_batch_report est un json ;
-- 31/12/2024 : Laurent HADJADJ - Correction de la table activity_batch_report ;
-- 03/01/2025 : Laurent HADJADJ - Bonne année 2025. Renommage de l'attribut start en mode_collecte  pour la table batch_traitement ;
-- 20/01/2025 : Laurent HADJADJ - Ajout des colones classes, functions et files à la table historique ;
-- 27/01/2025 : Laurent HADJADJ - Ajout de la clé inconnu à la table historique, pour dénombrer la répartition des valeurs inconnus ;
-- 06/02/2025 : Laurent HADJADJ - Ajout des attributs : version_sonar, version_release_sonar, version_snapshot_sonar, version_autre_sonar à la table information_version ;
-- 11/02/2025 : Laurent HADJADJ - Modification du type pour l'attribut setup et ajout de mode_collecte et utilisateur_collecte à la table repartition ;
-- 14/02/2025 : Laurent HADJADJ - Réorganisation de la table repartition en repartition_temp et repartition
-- 18/02/2025 : Laurent HADJADJ - Mise à jour de la valeur par défaut de la table repartition.
-- 06/07/2025 : Laurent HADJADJ - Ajout de la colonne id et suppression de contrainte d'unicité maven_key+setup pour la table repartition_temp.
-- 07/07/2025 : Laurent HADJADJ - Suppression de la colonne init, ajout de reset_password et reset_password_count pour la table utilisateur.
-- 16/07/2025 : Laurent HADJADJ - Ajout dans la relation mesures, files, classes et functions.
-- 22/07/2025 : Laurent HADJADJ - Correction de plusieurs erreurs de syntaxe SQL.
-- 22/07/2025 : Laurent HADJADJ - Correction de la colonne rules_update_at en rules_updated_at de la table profiles.
-- 22/07/2025 : Laurent HADJADJ - Renommage des hotspot_(high, medium, low) en menace_potentielle_(to_review, reviewed)_(high, medium, low) dans la table historique
-- 22/07/2025 : Laurent HADJADJ - Ajout de l'attribut inconnu dans la relation anomalie + renommage de l'attribut inconnue en inconnu.
-- 22/07/2025 : Laurent HADJADJ - Augmentation de la taille de l'attribut Key de la table profil (32 -> 56). Changement SonarQube en version 25.10.
-- 24/10/2025 : Laurent HADJADJ - Renommage des colonnes files, classes et functions en nombre_files, nombre_classes et nombre_functions.
-- 26/10/2025 : Laurent HADJADJ - Ajout des tables batch_execution et batch_execution_journal.
-- 26/10/2025 : Laurent HADJADJ - Renommage de la colonne result en success dans la table batch_traitement.
-- 27/10/2025 : Laurent HADJADJ - Ajout de la colonne in_progress à la table batch_traitement.
-- 27/10/2025 : Laurent HADJADJ - Ajout de la colonne pending à la table batch_traitement.
-- 27/10/2025 : Laurent HADJADJ - Ajout de la colonne responsable_short à la table batch et batch_traitement.
-- 27/10/2025 : Laurent HADJADJ - Correction syntaxe ';' (responsable_short) sur la table batch et batch_traitement.
-- 28/10/2025 : Laurent HADJADJ - Ajout de reference_unique à la table batch_traitement.
-- 28/10/2025 : Laurent HADJADJ - Ajout de  nom_projet et portefeuille à la table batch_execution_journal.
-- 28/10/2025 : Laurent HADJADJ - Renommage de nom en nom_traitement pour la table batch_execution.
-- 28/10/2025 : Laurent HADJADJ - Conversion du type UUID en STRINg pour garder la génération ULID côté PHP.
-- 29/10/2025 : Laurent HADJADJ - Ajout de l'attribut traitement_id dans les tables batch et batch_traitement.
-- 29/10/2025 : Laurent HADJADJ - L'objet ulid a une taille par défaut de 36 sauf si on passe en toBase32 (26).
-- 30/10/2025 : Laurent HADJADJ - Suppression ou remplacement de reference_unique par traitement_id.
-- 31/10/2025 : Laurent HADJADJ - Ajout de l'attribut execution_id pour la table batch_execution
-- 02/11/2025 : Laurent HADJADJ - Modification du type pour le champs compte_rendu de TEXT en BYTEA
-- 03/11/2025 : Laurent HADJADJ - Externalisation des vues et des proc_stock dans un script dédiée. Code-Clean.
-- 06/11/2025 : Laurent HADJADJ - Renommage de equipe en groupe.
-- 09/11/2025 : Laurent HADJADJ - Renommage de statut en activated pour la table batch, ajout de activated pour la table batch_traitement
