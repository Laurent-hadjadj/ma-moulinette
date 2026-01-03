/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.0.0 - 30/11/2025                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette db_user

-- activity_batch_report
CREATE INDEX IF NOT EXISTS idx_activity_batch_report_date_enr ON ma_moulinette.activity_batch_report (date_enregistrement ASC NULLS LAST);
CREATE INDEX IF NOT EXISTS idx_task_status ON ma_moulinette.activity_batch_report (task_count, task_done, page);
CREATE INDEX IF NOT EXISTS idx_last_error_error_code ON ma_moulinette.activity_batch_report ((last_error->>'errorCode'));

-- activity_historique
CREATE INDEX IF NOT EXISTS idx_activity_historique_year ON ma_moulinette.activity_historique (year);
CREATE INDEX IF NOT EXISTS idx_activity_historique_day ON ma_moulinette.activity_historique (day);
CREATE INDEX IF NOT EXISTS idx_activity_historique_analyse ON ma_moulinette.activity_historique ("analyse");
CREATE INDEX IF NOT EXISTS idx_activity_historique_success_rate ON ma_moulinette.activity_historique (success_rate);
CREATE INDEX IF NOT EXISTS idx_activity_historique_date_enregistrement ON ma_moulinette.activity_historique (date_enregistrement);

-- activity

CREATE INDEX IF NOT EXISTS idx_activity_maven_key ON ma_moulinette.activity (maven_key);
CREATE INDEX IF NOT EXISTS idx_activity_executed_at ON ma_moulinette.activity (executed_at);
CREATE INDEX IF NOT EXISTS idx_activity_mk_execat ON ma_moulinette.activity (maven_key, executed_at);
CREATE INDEX IF NOT EXISTS idx_activity_analyse_id ON ma_moulinette.activity (analyse_id);

-- actuator
CREATE INDEX IF NOT EXISTS idx_actuator_maven_key ON ma_moulinette.actuator (maven_key);
CREATE INDEX IF NOT EXISTS idx_actuator_url ON ma_moulinette.actuator (url);

-- actuator_info
CREATE INDEX IF NOT EXISTS idx_actuator_info_actuator_id ON ma_moulinette.actuator_info (actuator_id);

-- anomalie
CREATE INDEX IF NOT EXISTS idx_anomalie_maven_key ON ma_moulinette.anomalie (maven_key);
CREATE INDEX IF NOT EXISTS idx_anomalie_project_name ON ma_moulinette.anomalie (project_name);
CREATE INDEX IF NOT EXISTS idx_anomalie_date ON ma_moulinette.anomalie (date_enregistrement);

-- anomalie_details
CREATE INDEX IF NOT EXISTS idx_anomalie_details_maven_key ON ma_moulinette.anomalie_details (maven_key);
CREATE INDEX IF NOT EXISTS idx_anomalie_details_name ON ma_moulinette.anomalie_details (name);

-- batch_execution
CREATE INDEX IF NOT EXISTS idx_batch_execution_traitement_id ON ma_moulinette.batch_execution(traitement_id);
CREATE INDEX IF NOT EXISTS idx_batch_execution_execution_id ON ma_moulinette.batch_execution (execution_id);
CREATE INDEX IF NOT EXISTS idx_batch_execution_journal_job ON ma_moulinette.batch_execution_journal(job_id);
CREATE INDEX IF NOT EXISTS idx_batch_execution_date_enregistrement ON ma_moulinette.batch_execution(date_enregistrement);
CREATE INDEX IF NOT EXISTS idx_batch_execution_journal_date_execution ON ma_moulinette.batch_execution_journal(date_execution);

-- batch_execution_journal
CREATE INDEX IF NOT EXISTS idx_batch_exec_journal_job_id ON ma_moulinette.batch_execution_journal (job_id);
CREATE INDEX IF NOT EXISTS idx_batch_exec_journal_date ON ma_moulinette.batch_execution_journal (date_execution);
CREATE INDEX IF NOT EXISTS idx_batch_exec_journal_nom_projet ON ma_moulinette.batch_execution_journal (nom_projet);

-- batch_profiling
CREATE INDEX IF NOT EXISTS idx_batch_profiling_portefeuille ON ma_moulinette.batch_profiling (portefeuille);
CREATE INDEX IF NOT EXISTS idx_batch_profiling_date ON ma_moulinette.batch_profiling (date_execution DESC);
CREATE INDEX IF NOT EXISTS idx_batch_profiling_utilisateur ON ma_moulinette.batch_profiling (utilisateur);

-- batch_traitement
CREATE INDEX IF NOT EXISTS idx_batch_traitement_portefeuille ON ma_moulinette.batch_traitement(portefeuille);
CREATE INDEX IF NOT EXISTS idx_batch_traitement_traitement_id ON ma_moulinette.batch_traitement(traitement_id);
CREATE INDEX IF NOT EXISTS idx_batch_traitement_date ON ma_moulinette.batch_traitement(date_enregistrement);
CREATE INDEX IF NOT EXISTS idx_batch_traitement_in_progress ON ma_moulinette.batch_traitement(in_progress);

-- batch
CREATE INDEX IF NOT EXISTS idx_batch_portefeuille ON ma_moulinette.batch(portefeuille);
CREATE INDEX IF NOT EXISTS idx_batch_traitement_id ON ma_moulinette.batch(traitement_id);
CREATE INDEX IF NOT EXISTS idx_batch_date_enregistrement ON ma_moulinette.batch(date_enregistrement);
CREATE INDEX IF NOT EXISTS idx_batch_activated ON ma_moulinette.batch(activated);

-- groupe
CREATE INDEX IF NOT EXISTS idx_groupe_titre ON ma_moulinette.groupe (titre);
CREATE INDEX IF NOT EXISTS idx_groupe_date ON ma_moulinette.groupe (date_enregistrement);

-- historique
CREATE INDEX IF NOT EXISTS idx_historique_maven_key ON ma_moulinette.historique (maven_key);
CREATE INDEX IF NOT EXISTS idx_historique_date ON ma_moulinette.historique (date_enregistrement);

-- hotspot_details
CREATE INDEX IF NOT EXISTS idx_hotspot_details_maven_key ON ma_moulinette.hotspot_details (maven_key);
CREATE INDEX IF NOT EXISTS idx_hotspot_details_version ON ma_moulinette.hotspot_details (version);
CREATE INDEX IF NOT EXISTS idx_hotspot_details_date_enregistrement ON ma_moulinette.hotspot_details (date_enregistrement);

-- hotspot_owasp
CREATE INDEX IF NOT EXISTS idx_hotspot_owasp_maven_key ON ma_moulinette.hotspot_owasp (maven_key);
CREATE INDEX IF NOT EXISTS idx_hotspot_owasp_date_version ON ma_moulinette.hotspot_owasp (date_version);
CREATE INDEX IF NOT EXISTS idx_hotspot_owasp_date ON ma_moulinette.hotspot_owasp (date_enregistrement);
CREATE INDEX IF NOT EXISTS idx_hotspot_owasp_rule_key ON ma_moulinette.hotspot_owasp (rule_key);

-- hotspots

CREATE INDEX IF NOT EXISTS idx_hotspots_maven_key ON ma_moulinette.hotspots (maven_key);
CREATE INDEX IF NOT EXISTS idx_hotspots_date_version ON ma_moulinette.hotspots (date_version);
CREATE INDEX IF NOT EXISTS idx_hotspots_hotspot_key ON ma_moulinette.hotspots (hotspot_key);
CREATE INDEX IF NOT EXISTS idx_hotspots_date_enregistrement ON ma_moulinette.hotspots (date_enregistrement);

-- information_projet
CREATE INDEX IF NOT EXISTS idx_information_projet_maven_key ON ma_moulinette.information_projet (maven_key);
CREATE INDEX IF NOT EXISTS idx_information_projet_date ON ma_moulinette.information_projet (date);
CREATE INDEX IF NOT EXISTS idx_information_projet_enregistrement ON ma_moulinette.information_projet (date_enregistrement);

-- liste_projet
CREATE INDEX IF NOT EXISTS idx_liste_projet_maven_key ON ma_moulinette.liste_projet (maven_key);
CREATE INDEX IF NOT EXISTS idx_liste_projet_name ON ma_moulinette.liste_projet (name);
CREATE INDEX IF NOT EXISTS idx_liste_projet_date ON ma_moulinette.liste_projet (date_enregistrement);

-- logger
CREATE INDEX IF NOT EXISTS idx_logger_maven_key ON ma_moulinette.logger (maven_key);
CREATE INDEX IF NOT EXISTS idx_logger_date ON ma_moulinette.logger (date_enregistrement);

-- ma_moulinette
CREATE INDEX IF NOT EXISTS idx_ma_moulinette_version ON ma_moulinette.ma_moulinette (version);
CREATE INDEX IF NOT EXISTS idx_ma_moulinette_date_version ON ma_moulinette.ma_moulinette (date_version);
CREATE INDEX IF NOT EXISTS idx_ma_moulinette_date_enregistrement ON ma_moulinette.ma_moulinette (date_enregistrement);

-- mesures
CREATE INDEX IF NOT EXISTS idx_mesures_maven_key ON ma_moulinette.mesures (maven_key);
CREATE INDEX IF NOT EXISTS idx_mesures_project_name ON ma_moulinette.mesures (project_name);
CREATE INDEX IF NOT EXISTS idx_mesures_date ON ma_moulinette.mesures (date_enregistrement);

-- nosonar
CREATE INDEX IF NOT EXISTS idx_no_sonar_maven_key ON ma_moulinette.no_sonar (maven_key);
CREATE INDEX IF NOT EXISTS idx_no_sonar_date_enregistrement ON ma_moulinette.no_sonar (date_enregistrement);

-- notes
CREATE INDEX IF NOT EXISTS idx_notes_maven_key ON ma_moulinette.notes (maven_key);
CREATE INDEX IF NOT EXISTS idx_notes_type ON ma_moulinette.notes (type);
CREATE INDEX IF NOT EXISTS idx_notes_date ON ma_moulinette.notes (date_enregistrement);

-- owasp_top10
CREATE INDEX IF NOT EXISTS idx_owasp_top10_year ON ma_moulinette.owasp_top10 (year);
CREATE INDEX IF NOT EXISTS idx_owasp_top10_category ON ma_moulinette.owasp_top10 (category);

-- owasp
CREATE INDEX IF NOT EXISTS idx_owasp_maven_key ON ma_moulinette.owasp (maven_key);
CREATE INDEX IF NOT EXISTS idx_owasp_date_version ON ma_moulinette.owasp (date_version);
CREATE INDEX IF NOT EXISTS idx_owasp_key_ver_date ON ma_moulinette.owasp (maven_key, version, date_version);

-- portefeuille_historique
CREATE INDEX IF NOT EXISTS idx_portefeuille_historique_action ON ma_moulinette.portefeuille_historique (action);
CREATE INDEX IF NOT EXISTS idx_portefeuille_historique_date ON ma_moulinette.portefeuille_historique (date);

-- portefeuille
CREATE INDEX IF NOT EXISTS idx_portefeuille_groupe ON ma_moulinette.portefeuille (groupe);
CREATE INDEX IF NOT EXISTS idx_portefeuille_titre ON ma_moulinette.portefeuille (titre);
CREATE INDEX IF NOT EXISTS idx_portefeuille_liste_gin ON ma_moulinette.portefeuille
  USING gin (liste jsonb_path_ops);
CREATE INDEX IF NOT EXISTS idx_portefeuille_liste_gin ON ma_moulinette.portefeuille
  USING gin ((liste::jsonb));

-- profiles_historique
CREATE INDEX IF NOT EXISTS idx_profiles_hist_rule ON ma_moulinette.profiles_historique (rule);
CREATE INDEX IF NOT EXISTS idx_profiles_hist_date ON ma_moulinette.profiles_historique (date);
CREATE INDEX IF NOT EXISTS idx_profiles_hist_lang_date ON ma_moulinette.profiles_historique (language, date);

-- profiles
CREATE INDEX IF NOT EXISTS idx_profiles_language ON ma_moulinette.profiles (language_name);
CREATE INDEX IF NOT EXISTS idx_profiles_date ON ma_moulinette.profiles (date_enregistrement);

-- properties
CREATE INDEX IF NOT EXISTS idx_properties_type ON ma_moulinette.properties (type);

-- repartition
CREATE INDEX IF NOT EXISTS idx_repartition_maven_key ON ma_moulinette.repartition (maven_key);
CREATE INDEX IF NOT EXISTS idx_repartition_name ON ma_moulinette.repartition (name);
CREATE INDEX IF NOT EXISTS idx_repartition_key_name_setup ON ma_moulinette.repartition (maven_key, name, setup);

-- repartition_temp
CREATE INDEX IF NOT EXISTS idx_repartition_temp_maven_key ON ma_moulinette.repartition_temp (maven_key);
CREATE INDEX IF NOT EXISTS idx_repartition_temp_setup ON ma_moulinette.repartition_temp (setup);
CREATE INDEX IF NOT EXISTS idx_repartition_temp_type ON ma_moulinette.repartition_temp (type);

-- \todo
CREATE INDEX IF NOT EXISTS idx_todo_maven_key ON ma_moulinette.todo (maven_key);
CREATE INDEX IF NOT EXISTS idx_todo_rule ON ma_moulinette.todo (rule);
CREATE INDEX IF NOT EXISTS idx_todo_maven_rule ON ma_moulinette.todo (maven_key, rule);

-- utilisateur
CREATE INDEX IF NOT EXISTS idx_utilisateur_courriel ON ma_moulinette.utilisateur (courriel);
CREATE INDEX IF NOT EXISTS idx_utilisateur_nom_prenom ON ma_moulinette.utilisateur (nom, prenom);
CREATE INDEX IF NOT EXISTS idx_utilisateur_actif ON ma_moulinette.utilisateur (actif);

-- user_agent_analysis

CREATE INDEX IF NOT EXISTS idx_analysis_device_type ON ma_moulinette.user_agent_analysis(device_type);
CREATE INDEX IF NOT EXISTS idx_analysis_os_name ON ma_moulinette.user_agent_analysis(os_name);
CREATE INDEX IF NOT EXISTS idx_analysis_browser_name ON ma_moulinette.user_agent_analysis(browser_name);
CREATE INDEX IF NOT EXISTS idx_analysis_is_bot ON ma_moulinette.user_agent_analysis(is_bot);
CREATE INDEX IF NOT EXISTS idx_analysis_created_at ON ma_moulinette.user_agent_analysis(created_at);
CREATE INDEX IF NOT EXISTS idx_analysis_event_type ON ma_moulinette.user_agent_analysis(event_type);
CREATE INDEX IF NOT EXISTS idx_analysis_session_id ON ma_moulinette.user_agent_analysis(session_id);

--- user_agent_event

CREATE INDEX idx_processing_status ON ma_moulinette.user_agent_event(processing_status);
CREATE INDEX idx_event_type ON ma_moulinette.user_agent_event(event_type);
CREATE INDEX user_agent_event_created_at_idx ON ma_moulinette.user_agent_event(created_at);
CREATE INDEX idx_user_id ON ma_moulinette.user_agent_event(user_id);
CREATE INDEX idx_session_id ON ma_moulinette.user_agent_event(session_id);

-- ===============================================
-- INDEXES: ma_moulinette.batch_profiling
-- ===============================================

-- Index principal sur la date (accélère les regroupements temporels et les tris)
CREATE INDEX IF NOT EXISTS idx_batch_profiling_date_execution ON ma_moulinette.batch_profiling (date_execution DESC);

-- Index combiné portefeuille + utilisateur (optimise les vues de statistiques)
CREATE INDEX IF NOT EXISTS idx_batch_profiling_portefeuille_user ON ma_moulinette.batch_profiling (portefeuille, utilisateur);

-- Index portefeuille seul (optimise les agrégations globales)
CREATE INDEX IF NOT EXISTS idx_batch_profiling_portefeuille ON ma_moulinette.batch_profiling (portefeuille);

-- Index utilisateur seul (utile pour les dashboards par utilisateur)
CREATE INDEX IF NOT EXISTS idx_batch_profiling_utilisateur ON ma_moulinette.batch_profiling (utilisateur);

-- Index sur la référence d'exécution (ULID/UUID), utile en inspection directe
CREATE INDEX IF NOT EXISTS idx_batch_profiling_execution_ref ON ma_moulinette.batch_profiling (execution_reference);

-- \todo
--CREATE INDEX IF NOT EXISTS idx_batch_profiling_recent ON ma_moulinette.batch_profiling (date_execution) WHERE date_execution > NOW() - INTERVAL '90 days';
