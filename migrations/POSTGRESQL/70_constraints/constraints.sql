/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.2.0 - 09/07/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-05-05 : Ajout UNIQUE sur groupe_utilisateur(groupe_utilisateur, groupe_id)
--- 2026-05-08 : Ajout des contraintes pour les tables dc_cve, dc_dependency, dc_finding, dc_processing_queue, dc_scan
--- 2026-05-11 : Corrections contraintes dc_dependency et dc_processing_queue
--- 2026-07-19 : Ajout de la contraintes d'unicité sur la table profil_historique.

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette postgres;

-- actuator
ALTER TABLE ma_moulinette.actuator ADD CONSTRAINT uq_actuator_url UNIQUE (url);

-- actuator_info
ALTER TABLE ma_moulinette.actuator_info
ADD CONSTRAINT fk_actuator_info_actuator
    FOREIGN KEY (actuator_id)
    REFERENCES ma_moulinette.actuator(id)
    ON DELETE CASCADE;

-- batch_execution
ALTER TABLE ma_moulinette.batch_execution
ADD CONSTRAINT ck_mode_collecte
    CHECK (mode_collecte IN ('COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'));

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
-- Aligne avec les valeurs utilisées par le code PHP (BatchTraitementRepository,
-- Entity BatchTraitement::modeCollecte = 'TRAITEMENT MANUEL' par défaut).
ALTER TABLE ma_moulinette.batch_traitement
ADD CONSTRAINT ck_batch_traitement_mode_collecte
  CHECK (mode_collecte IN ('COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT AUTOMATIQUE'));

-- batch
ALTER TABLE ma_moulinette.batch
ADD CONSTRAINT ck_batch_execution
  CHECK (execution IN ('ON','OFF'));

/* MODIF 2026-05-05 : alignement DDL <-> entity Doctrine.
 * GroupeUtilisateur::$groupeUtilisateur (#[ORM\Column(unique: true)]) et
 * GroupeUtilisateur::$groupeId (#[ORM\Column(unique: true)]) annotes UNIQUE cote ORM,
 * mais aucune contrainte UNIQUE n'existait au niveau DDL. */
-- groupe_utilisateur
ALTER TABLE ma_moulinette.groupe_utilisateur
  ADD CONSTRAINT uq_groupe_utilisateur_nom UNIQUE (groupe_utilisateur);
ALTER TABLE ma_moulinette.groupe_utilisateur
  ADD CONSTRAINT uq_groupe_utilisateur_groupe_id UNIQUE (groupe_id);

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

-- profiles_historique
ALTER TABLE ma_moulinette.profiles_historique
  ADD CONSTRAINT uniq_profiles_historique_event UNIQUE (language, date, rule, action);

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
  CHECK (control IN ('initial', 'complet (100%)', 'partiel (66%)', 'partiel (33%)'));

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
  ADD CONSTRAINT ck_todo_line CHECK (length(line::text) > 0);

ALTER TABLE ma_moulinette.todo
  ADD CONSTRAINT ck_todo_maven_key CHECK (length(maven_key) > 0);

-- utilisateur
ALTER TABLE ma_moulinette.utilisateur
  ADD CONSTRAINT ck_utilisateur_courriel CHECK (length(courriel) > 3);
ALTER TABLE ma_moulinette.utilisateur
  ADD CONSTRAINT uq_utilisateur_courriel UNIQUE (courriel);
ALTER TABLE ma_moulinette.utilisateur
  ADD CONSTRAINT ck_reset_password_count CHECK (reset_password_count >= 0);

-- dc_cve
ALTER TABLE ma_moulinette.dc_cve
  ADD CONSTRAINT uq_dc_cve_id UNIQUE (cve_id);

-- dc_dependency
ALTER TABLE ma_moulinette.dc_dependency
  ADD CONSTRAINT uq_dc_dependency_sha1 UNIQUE (sha1);

-- dc_finding
-- Pas de doublon : meme triplet = meme finding
-- FK : si on supprime un scan, on supprime ses findings (CASCADE).
-- Les CVE et dependencies sont conservées (garde-fou : RESTRICT).
ALTER TABLE ma_moulinette.dc_finding
  ADD CONSTRAINT uq_dc_finding_triplet UNIQUE (scan_id, dependency_id, cve_id),
  ADD CONSTRAINT fk_dc_finding_scan FOREIGN KEY (scan_id)
    REFERENCES ma_moulinette.dc_scan(id) ON DELETE CASCADE,
  ADD CONSTRAINT fk_dc_finding_dependency FOREIGN KEY (dependency_id)
    REFERENCES ma_moulinette.dc_dependency(id) ON DELETE RESTRICT,
  ADD CONSTRAINT fk_dc_finding_cve FOREIGN KEY (cve_id)
    REFERENCES ma_moulinette.dc_cve(id) ON DELETE RESTRICT;

-- dc_processing_queue : Idempotence native : meme payload poste 2x = conflit gère par l'app
ALTER TABLE ma_moulinette.dc_processing_queue
  ADD CONSTRAINT uq_dc_payload_sha256 UNIQUE (payload_sha256);

-- dc_scan :
-- 1 scan unique par (projet, version, date) - eviter doublons
-- FK optionnelle vers la queue. ON DELETE SET NULL : si on purge la queue,
-- on garde le scan en BDD metier.
ALTER TABLE ma_moulinette.dc_scan
  ADD CONSTRAINT uq_dc_scan_project_version_date UNIQUE (project_group, project_artifact, project_version, scan_date),
  ADD CONSTRAINT fk_dc_scan_queue FOREIGN KEY (queue_id)
    REFERENCES ma_moulinette.dc_processing_queue(id)
    ON DELETE SET NULL;
