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

-- actuator
ALTER TABLE actuator ADD CONSTRAINT uq_actuator_url UNIQUE (url);

-- actuator_info
ALTER TABLE ma_moulinette.actuator_info
ADD CONSTRAINT fk_actuator_info_actuator
    FOREIGN KEY (actuator_id)
    REFERENCES ma_moulinette.actuator(id)
    ON DELETE CASCADE;

-- batch_execution
ALTER TABLE ma_moulinette.batch_execution
ADD CONSTRAINT ck_mode_collecte
    CHECK (mode_collecte IN ('REBUILD','COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'));

-- batch_execution_journal
ALTER TABLE ma_moulinette.batch_execution_journal
ADD CONSTRAINT fk_batch_execution_journal_job
    FOREIGN KEY (job_id)
    REFERENCES ma_moulinette.batch_execution(id)
    ON DELETE CASCADE;

-- batch_profiling
ALTER TABLE ma_moulinette.batch_profiling
ADD CONSTRAINT ck_batch_profiling_nb_projets
  CHECK (nb_projets > 0);
ALTER TABLE ma_moulinette.batch_profiling
ADD CONSTRAINT ck_batch_profiling_temps_total
  CHECK (temps_total >= 0);
ALTER TABLE ma_moulinette.batch_profiling
ADD CONSTRAINT ck_batch_profiling_temps_moyen
  CHECK (temps_moyen >= 0);
ALTER TABLE ma_moulinette.batch_profiling
ADD CONSTRAINT ck_batch_profiling_memoire_peak
  CHECK (memoire_peak >= 0);
ALTER TABLE ma_moulinette.batch_profiling
ADD CONSTRAINT ck_batch_profiling_memoire_moyenne
  CHECK (memoire_moyenne >= 0);

-- batch_traitement
ALTER TABLE ma_moulinette.batch_traitement
ADD CONSTRAINT ck_batch_traitement_mode_collecte
  CHECK (mode_collecte IN ('COLLECTE','MANUEL','AUTO'));

-- batch
ALTER TABLE ma_moulinette.batch
ADD CONSTRAINT ck_batch_execution
  CHECK (execution IN ('ON','OFF'));

-- historique
ALTER TABLE ma_moulinette.historique
    ADD CONSTRAINT historique_pkey
        PRIMARY KEY (maven_key, version, date_version);

-- liste_projet
ALTER TABLE ma_moulinette.liste_projet
    ADD CONSTRAINT uq_liste_projet_maven_key
        UNIQUE (maven_key);

-- ma_moulinette
ALTER TABLE ma_moulinette.ma_moulinette
    ADD CONSTRAINT uq_ma_moulinette_version
        UNIQUE (version);

-- logger
ALTER TABLE ma_moulinette.logger
    ADD CONSTRAINT uq_logger_entry UNIQUE (maven_key, date_enregistrement);

-- owasp_top10
ALTER TABLE ma_moulinette.owasp_top10
    ADD CONSTRAINT year_check
    CHECK (year >= 2000 AND year <= EXTRACT(YEAR FROM CURRENT_DATE));

-- portefeuille
ALTER TABLE ma_moulinette.portefeuille
  ADD CONSTRAINT ck_portefeuille_liste_array
  CHECK (json_typeof(liste) = 'array');

ALTER TABLE ma_moulinette.portefeuille
  ADD CONSTRAINT ck_portefeuille_dates
  CHECK (date_modification IS NULL OR date_modification >= date_enregistrement);

-- profiles
ALTER TABLE ma_moulinette.profiles
  ADD CONSTRAINT ck_profiles_active_rule_count
  CHECK (active_rule_count >= 0);

-- properties
ALTER TABLE ma_moulinette.properties
  ADD CONSTRAINT ck_properties_positive
  CHECK (
      projet_bd >= 0
      AND projet_sonar >= 0
      AND profil_bd >= 0
      AND profil_sonar >= 0
  );

-- repartition
ALTER TABLE ma_moulinette.repartition
  ADD CONSTRAINT ck_repartition_control
  CHECK (control IN ('initial', 'update', 'merge'));

ALTER TABLE ma_moulinette.repartition
  ADD CONSTRAINT uq_repartition_unique
  UNIQUE (maven_key, name, setup);

-- repartition_temp
ALTER TABLE ma_moulinette.repartition_temp
    ADD CONSTRAINT ck_repartition_temp_type
    CHECK (type IN ('BUG', 'VULNERABILITY', 'CODE_SMELL'));

ALTER TABLE ma_moulinette.repartition_temp
    ADD CONSTRAINT ck_repartition_temp_severity
    CHECK (severity IN ('INFO','MINOR','MAJOR','CRITICAL','BLOCKER'));

ALTER TABLE ma_moulinette.repartition_temp
    ADD CONSTRAINT ck_repartition_temp_setup_positive
    CHECK (setup >= 0);

-- \todo
ALTER TABLE ma_moulinette.todo
  ADD CONSTRAINT ck_todo_line CHECK (line >= 0);

ALTER TABLE ma_moulinette.todo
  ADD CONSTRAINT ck_todo_maven_key CHECK (length(maven_key) > 0);

--utilisateur
ALTER TABLE ma_moulinette.utilisateur
  ADD CONSTRAINT ck_utilisateur_courriel CHECK (length(courriel) > 3);
ALTER TABLE ma_moulinette.utilisateur
  ADD CONSTRAINT uq_utilisateur_courriel UNIQUE (courriel);
ALTER TABLE ma_moulinette.utilisateur
  ADD CONSTRAINT ck_reset_password_count CHECK (reset_password_count >= 0);
