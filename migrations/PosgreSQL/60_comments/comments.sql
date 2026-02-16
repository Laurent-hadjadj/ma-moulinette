/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.1.0 - 05/02/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-02-05 : Ajout du commentaire pour visitor_id de la table user_agent_event.
--- 2026-02-05 : Ajout de l'index pour visitor_id et user_id de la table user_agent_analysis

-- ⚠️ Le script doit être lancé avec l'utilisateur propriétaire du schema
\c ma_moulinette db_user

-- ============================================
-- SCHEMA ma_moulinette
-- ============================================
COMMENT ON SCHEMA ma_moulinette IS 'Schéma de la base de données Ma-moulinette';
-- ============================================
-- TABLE activity
-- ============================================
COMMENT ON COLUMN ma_moulinette.activity.id IS 'Identifiant unique de la table activité';
COMMENT ON COLUMN ma_moulinette.activity.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.activity.project_name IS 'Nom du projet associé à la clé maven';
COMMENT ON COLUMN ma_moulinette.activity.analyse_id IS 'Identifiant de l’analyse du projet';
COMMENT ON COLUMN ma_moulinette.activity.status IS 'Statut du traitement d’import';
COMMENT ON COLUMN ma_moulinette.activity.submitter_login IS 'Utilisateur soumettant l’import';
COMMENT ON COLUMN ma_moulinette.activity.submitted_at IS 'Date et heure de la soumission du traitement d’import des données';
COMMENT ON COLUMN ma_moulinette.activity.started_at IS 'Date et heure du debut du traitement d’import des données';
COMMENT ON COLUMN ma_moulinette.activity.executed_at IS 'Date et heure de fin du traitement d’import des données';
COMMENT ON COLUMN ma_moulinette.activity.execution_time IS 'Temps d’execution du traitement d’import des données';
-- ============================================
-- TABLE activity_historique
-- ============================================
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
-- ============================================
-- TABLE activity_batch_report
-- ============================================
COMMENT ON COLUMN ma_moulinette.activity_batch_report.id IS 'Identifiant unique de la table.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.date_start IS 'Date de début de l’intervalle pour l’extraction des tâches.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.date_end IS 'Date de fin de l’intervalle pour l’extraction des tâches.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.task_count IS 'Nombre total de tâches récupérées dans le lot.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.task_done IS 'Nombre de tâches traitées dans le lot.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.page IS 'Numéro de la page traitée (utilisé pour la pagination).';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.last_error IS 'Liste des erreurs rencontrées durant le traitement des tâches.';
COMMENT ON COLUMN ma_moulinette.activity_batch_report.date_enregistrement IS 'Date et heure de l’enregistrement du rapport dans la base de données.';
-- ============================================
-- TABLE actuator
-- ============================================
COMMENT ON COLUMN ma_moulinette.actuator.id IS 'Identifiant unique de la table';
COMMENT ON COLUMN ma_moulinette.actuator.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.actuator.nom_application IS 'Nom de l’application.';
COMMENT ON COLUMN ma_moulinette.actuator.url IS 'URL de base du serveur.';
COMMENT ON COLUMN ma_moulinette.actuator.actuator_user IS 'Nom de l’utilisateur Actuator';
COMMENT ON COLUMN ma_moulinette.actuator.actuator_password IS 'Mot de passe de l’utilisateur Actuator';
COMMENT ON COLUMN ma_moulinette.actuator.personne IS 'Prénom et nom de l’utilisateur';
COMMENT ON COLUMN ma_moulinette.actuator.date_modification IS 'Date de la dernière modification.';
COMMENT ON COLUMN ma_moulinette.actuator.date_enregistrement IS 'Date d’enregistrement.';
-- ============================================
-- TABLE actuator_info
-- ============================================
COMMENT ON COLUMN ma_moulinette.actuator_info.id IS 'Identifiant unique de la table';
COMMENT ON COLUMN ma_moulinette.actuator_info.actuator_info_description IS 'Description courte.';
COMMENT ON COLUMN ma_moulinette.actuator_info.actuator_info_value IS 'Valeur de la clé actuator.';
-- ============================================
-- TABLE anomalie
-- ============================================
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
-- ============================================
-- TABLE anomalie_details
-- ============================================
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
-- ============================================
-- TABLE batch
-- ============================================
COMMENT ON COLUMN ma_moulinette.batch.id IS 'Identifiant unique du traitement';
COMMENT ON COLUMN ma_moulinette.batch.activated IS 'Statut d’activité du traitement';
COMMENT ON COLUMN ma_moulinette.batch.automatique IS 'Mode manuel ou automatqiue';
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
-- ============================================
-- TABLE batch_traitement
-- ============================================
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
-- ============================================
-- TABLE batch_execution
-- ============================================
COMMENT ON TABLE ma_moulinette.batch_execution IS 'Journal des exécutions de traitements.';
COMMENT ON COLUMN ma_moulinette.batch_execution.id IS 'Identifiant unique de la table batch_execution.';
COMMENT ON COLUMN ma_moulinette.batch_execution.nom_traitement IS 'Nom du batch exécuté.';
COMMENT ON COLUMN ma_moulinette.batch_execution.execution_id IS 'Référence unique du journal.';
COMMENT ON COLUMN ma_moulinette.batch_execution.traitement_id IS 'Référence unique du traitement.';
COMMENT ON COLUMN ma_moulinette.batch_execution.mode_collecte IS 'Mode de collecte : COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE.';
COMMENT ON COLUMN ma_moulinette.batch_execution.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte.';
COMMENT ON COLUMN ma_moulinette.batch_execution.date_enregistrement IS 'Date d’enregistrement du journal de l’exécution du batch.';
-- ============================================
-- TABLE batch_execution_journal
-- ============================================
COMMENT ON TABLE ma_moulinette.batch_execution_journal IS 'Journal détaillé des collectes/exécutions associées à un batch.';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.nom_projet IS 'Nom du projet traité';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.portefeuille IS 'Nom du portefeuille de projets associé';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.id IS 'Identifiant unique de la table batch_execution_journal';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.code IS 'Code de statut du traitement (200 = OK, 500 = Erreur, etc.)';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.compte_rendu IS 'Compte rendu HTML compresssé du traitement.';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.date_execution IS 'Date d’exécution de la collecte.';
COMMENT ON COLUMN ma_moulinette.batch_execution_journal.job_id IS 'Clé étrangère vers batch_execution.id';
-- ============================================
-- TABLE batch_profiling
-- ============================================
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
COMMENT ON COLUMN ma_moulinette.batch_profiling.date_execution IS 'Horodatage de fin d’exécution du traitement';
-- ============================================
-- TABLE groupe
-- ============================================
COMMENT ON TABLE ma_moulinette.groupe IS 'Table des groupes utilisateurs';
COMMENT ON COLUMN ma_moulinette.groupe.id IS 'Identifiant unique de l’équipe';
COMMENT ON COLUMN ma_moulinette.groupe.titre IS 'Titre de l’équipe, unique';
COMMENT ON COLUMN ma_moulinette.groupe.description IS 'Description de l’équipe';
COMMENT ON COLUMN ma_moulinette.groupe.date_modification IS 'Date de la dernière modification de l’équipe';
COMMENT ON COLUMN ma_moulinette.groupe.date_enregistrement IS 'Date d’enregistrement de l’équipe';
-- ============================================
-- TABLE historique
-- ============================================
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
COMMENT ON COLUMN ma_moulinette.historique.nombre_classes IS 'Nombre de classes dans le projet';
COMMENT ON COLUMN ma_moulinette.historique.nombre_functions IS 'Nombre de méthode/fonction dans le projet';
COMMENT ON COLUMN ma_moulinette.historique.nombre_files IS 'Nombre de fichiers dans le projet';
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
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_to_review_medium IS 'Nombre de menaces potentielles moyennes à vérifier';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_to_review_low IS 'Nombre de menaces potentielles faibles à vérifier';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_reviewed_high IS 'Nombre de menaces potentielle élevées vérifiées';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_reviewed_medium IS 'Nombre de menaces potentielle moyennes vérifiées';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_reviewed_low IS 'Nombre de menaces potentielle faibles vérifiées';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_totale IS 'Nombre total de menaces potentielles vérifiées';
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
COMMENT ON COLUMN ma_moulinette.historique.logger_warn IS 'Nombre de méthode Warn invoqué';
COMMENT ON COLUMN ma_moulinette.historique.logger_error IS 'Nombre de méthode Error invoqué';
COMMENT ON COLUMN ma_moulinette.historique.logger_debug IS 'Nombre de méthode Debug invoqué';
COMMENT ON COLUMN ma_moulinette.historique.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.historique.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.historique.actuator_info IS 'Information Actuator du projet';
COMMENT ON COLUMN ma_moulinette.historique.date_enregistrement IS 'Date d’enregistrement de l’historique';
-- ============================================
-- TABLE hotspot_details
-- ============================================
COMMENT ON COLUMN ma_moulinette.hotspot_details.id IS 'Identifiant unique pour la table hotspot owasp details';
COMMENT ON COLUMN ma_moulinette.hotspot_details.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.hotspot_details.version IS 'Version du projet';
COMMENT ON COLUMN ma_moulinette.hotspot_details.date_version IS 'Date de la publication du projet';
COMMENT ON COLUMN ma_moulinette.hotspot_details.security_category IS 'Défini la catégorie de sécurité du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.rule_key IS 'Règle SonarQube associée au hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.rule_name IS 'Nom de la règle SonarQube';
COMMENT ON COLUMN ma_moulinette.hotspot_details.severity IS 'Sévérité du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_details.status IS 'Statut du hotspot TO_REVIEW, REVIEWED';
COMMENT ON COLUMN ma_moulinette.hotspot_details.resolution IS 'État du hotspot : FIXED, SAFE, ACKNOWLEDGED';
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
-- ============================================
-- TABLE hotspot_owasp
-- ============================================
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
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.resolution IS 'État du hotspot : FIXED, SAFE, ACKNOWLEDGED';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.niveau IS 'Niveau de risque du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.hotspot_owasp.date_enregistrement IS 'Date d’enregistrement du hotspot OWASP';
-- ============================================
-- TABLE hotspots
-- ============================================
COMMENT ON COLUMN ma_moulinette.hotspots.id IS 'Identifiant unique pour chaque hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.hotspots.version IS 'Version du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.date_version IS 'Date de la version du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.hotspot_key IS 'Clé unique du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.security_category IS 'Défini la catégorie de sécurité du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.rule_key IS 'Clé de la règle SonarQube';
COMMENT ON COLUMN ma_moulinette.hotspots.probability IS 'Probabilité de risque du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.status IS 'Statut du hotspot : TO_REVIEW, REVIEWED';
COMMENT ON COLUMN ma_moulinette.hotspots.resolution IS 'État du hotspot : FIXED, SAFE, ACKNOWLEDGED';
COMMENT ON COLUMN ma_moulinette.hotspots.niveau IS 'Niveau de risque du hotspot';
COMMENT ON COLUMN ma_moulinette.hotspots.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.hotspots.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.hotspots.date_enregistrement IS 'Date d’enregistrement du hotspot';
-- ============================================
-- TABLE information_projet
-- ============================================
COMMENT ON COLUMN ma_moulinette.information_projet.id IS 'Identifiant unique pour chaque instance de InformationProjet';
COMMENT ON COLUMN ma_moulinette.information_projet.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.information_projet.analyse_key IS 'Clé d’analyse du projet';
COMMENT ON COLUMN ma_moulinette.information_projet.date IS 'Date de l’analyse du projet';
COMMENT ON COLUMN ma_moulinette.information_projet.project_version IS 'Version du projet lors de l’analyse';
COMMENT ON COLUMN ma_moulinette.information_projet.type IS 'Type d’analyse effectuée';
COMMENT ON COLUMN ma_moulinette.information_projet.version_sonar IS 'Nombre total de version sur le serveur SonarQube';
COMMENT ON COLUMN ma_moulinette.information_projet.version_release_sonar IS 'Nombre de version Release sur le serveur SonarQube';
COMMENT ON COLUMN ma_moulinette.information_projet.version_snapshot_sonar IS 'Nombre de version Snapshot sur le serveur SonarQube';
COMMENT ON COLUMN ma_moulinette.information_projet.version_autre_sonar IS 'Nombre de version Autre sur le serveur SonarQube';
COMMENT ON COLUMN ma_moulinette.information_projet.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.information_projet.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.information_projet.date_enregistrement IS 'Date d’enregistrement de l’information du projet';
-- ============================================
-- TABLE liste_projet
-- ============================================
COMMENT ON COLUMN ma_moulinette.liste_projet.id IS 'Identifiant unique pour chaque instance de ListeProjet';
COMMENT ON COLUMN ma_moulinette.liste_projet.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.liste_projet.name IS 'Nom du projet';
COMMENT ON COLUMN ma_moulinette.liste_projet.tags IS 'Tags associés au projet sous forme de tableau JSON';
COMMENT ON COLUMN ma_moulinette.liste_projet.visibility IS 'Visibilité du projet';
COMMENT ON COLUMN ma_moulinette.liste_projet.date_enregistrement IS 'Date d’enregistrement du projet';
-- ============================================
-- TABLE logger
-- ============================================
COMMENT ON COLUMN ma_moulinette.logger.id IS 'Identifiant unique pour chaque instance de ListeProjet';
COMMENT ON COLUMN ma_moulinette.logger.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.logger.logger_info IS 'Nombre d’appels de log niveau INFO';
COMMENT ON COLUMN ma_moulinette.logger.logger_warn IS 'Nombre d’appels de log niveau WARN';
COMMENT ON COLUMN ma_moulinette.logger.logger_error IS 'Nombre d’appels de log niveau ERROR';
COMMENT ON COLUMN ma_moulinette.logger.logger_debug IS 'Nombre d’appels de log niveau DEBUG';
COMMENT ON COLUMN ma_moulinette.logger.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.logger.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.logger.date_enregistrement IS 'Date d’enregistrement du projet';
-- ============================================
-- TABLE ma_moulinette
-- ============================================
COMMENT ON COLUMN ma_moulinette.ma_moulinette.id IS 'Unique identifier for each MaMoulinette instance';
COMMENT ON COLUMN ma_moulinette.ma_moulinette.version IS 'Numéro de version de Ma-Moulinette';
COMMENT ON COLUMN ma_moulinette.ma_moulinette.date_version IS 'Date de publication de la version';
COMMENT ON COLUMN ma_moulinette.ma_moulinette.date_enregistrement IS 'Date d’enregistrement';
-- ============================================
-- TABLE mesures
-- ============================================
COMMENT ON COLUMN ma_moulinette.mesures.id IS 'Identifiant unique pour chaque mesure';
COMMENT ON COLUMN ma_moulinette.mesures.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.mesures.project_name IS 'Nom du projet';
COMMENT ON COLUMN ma_moulinette.mesures.lines IS 'Nombre total de lignes du projet';
COMMENT ON COLUMN ma_moulinette.mesures.ncloc IS 'Lignes de code non commentées';
COMMENT ON COLUMN ma_moulinette.mesures.language_distribution IS 'Distribution des langages de programmation';
COMMENT ON COLUMN ma_moulinette.mesures.coverage IS 'Pourcentage de couverture par les tests';
COMMENT ON COLUMN ma_moulinette.mesures.files IS 'Nombre total de fichiers';
COMMENT ON COLUMN ma_moulinette.mesures.classes IS 'Nombre total de classes';
COMMENT ON COLUMN ma_moulinette.mesures.functions IS 'Nombre total de fonctions';
COMMENT ON COLUMN ma_moulinette.mesures.sqale_debt_ratio IS 'Ratio de dette technique (SQALE)';
COMMENT ON COLUMN ma_moulinette.mesures.duplicated_lines_density IS 'Densité de duplication du code';
COMMENT ON COLUMN ma_moulinette.mesures.tests IS 'Nombre total de tests';
COMMENT ON COLUMN ma_moulinette.mesures.issues IS 'Nombre total de problèmes identifiés';
COMMENT ON COLUMN ma_moulinette.mesures.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.mesures.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.mesures.date_enregistrement IS 'Date d’enregistrement de la mesure';
-- ============================================
-- TABLE no_sonar
-- ============================================
COMMENT ON COLUMN ma_moulinette.no_sonar.id IS 'Identifiant unique pour chaque entrée NoSonar';
COMMENT ON COLUMN ma_moulinette.no_sonar.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.no_sonar.rule IS 'Règle NoSonar appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.component IS 'Composant auquel la règle est appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.line IS 'Ligne où la règle NoSonar est appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.no_sonar.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.no_sonar.date_enregistrement IS 'Date d’enregistrement de l’entrée NoSonar';
-- ============================================
-- TABLE notes
-- ============================================
COMMENT ON COLUMN ma_moulinette.notes.maven_key IS 'Clé Maven unique identifiant la note';
COMMENT ON COLUMN ma_moulinette.notes.type IS 'Type de la note';
COMMENT ON COLUMN ma_moulinette.notes.value IS 'Valeur de la note';
COMMENT ON COLUMN ma_moulinette.notes.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.notes.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.notes.date_enregistrement IS 'Date d’enregistrement de la note';
-- ============================================
-- TABLE owasp
-- ============================================
COMMENT ON COLUMN ma_moulinette.owasp.id IS 'Clé unique pour les enregistrements de la table';
COMMENT ON COLUMN ma_moulinette.owasp.maven_key IS 'Clé unique du projet';
COMMENT ON COLUMN ma_moulinette.owasp.version IS 'Version du projet';
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
-- ============================================
-- TABLE repartition_temp
-- ============================================
COMMENT ON COLUMN ma_moulinette.repartition_temp.id IS 'Identifiant unique pour chaque propriété';
COMMENT ON COLUMN ma_moulinette.repartition_temp.maven_key IS 'Clé identification du projet';
COMMENT ON COLUMN ma_moulinette.repartition_temp.type IS 'Catégorie : BUG, VULNERABILITY ou CODE_SMELL';
COMMENT ON COLUMN ma_moulinette.repartition_temp.severity IS 'Niveau de sévérité de l’anomalie';
COMMENT ON COLUMN ma_moulinette.repartition_temp.setup IS 'Timestamp en milliseconde unique pour chaque analyse';
-- ============================================
-- TABLE repartition
-- ============================================
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
COMMENT ON COLUMN ma_moulinette.repartition.frontend IS 'Répartition des anomalies (frontend)';
COMMENT ON COLUMN ma_moulinette.repartition.backend IS 'Répartition des anomalies (backend)';
COMMENT ON COLUMN ma_moulinette.repartition.autre IS 'Répartition des anomalies (autre)';
COMMENT ON COLUMN ma_moulinette.repartition.inconnu IS 'Répartition des anomalies (inconnu)';
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
-- ============================================
-- TABLE todo
-- ============================================
COMMENT ON COLUMN ma_moulinette.todo.id IS 'ID unique pour chaque Todo';
COMMENT ON COLUMN ma_moulinette.todo.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.todo.rule IS 'Règle appliquée au Todo';
COMMENT ON COLUMN ma_moulinette.todo.component IS 'Détails du composant concerné par le Todo';
COMMENT ON COLUMN ma_moulinette.todo.line IS 'Numéro de ligne du code associée au Todo';
COMMENT ON COLUMN ma_moulinette.todo.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.todo.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.todo.date_enregistrement IS 'Date d’enregistrement du Todo';
-- ============================================
-- TABLE utilisateur
-- ============================================
COMMENT ON COLUMN ma_moulinette.utilisateur.id IS 'Clé unique de la table';
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

-- ============================================
-- TABLE user_agent_analysis
-- ============================================

COMMENT ON TABLE ma_moulinette.user_agent_analysis IS 'Table persistante contenant les résultats consolidés de l’analyse des User-Agent pour les statistiques.';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.id IS 'Identifiant unique de l’analyse User-Agent';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.device_type IS 'Type d’appareil détecté (desktop, mobile, tablet…)';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.os_name IS 'Nom du système d’exploitation';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.os_version IS 'Version du système d’exploitation';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.browser_name IS 'Nom du navigateur';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.browser_version IS 'Version du navigateur';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.is_bot IS 'Indique si le client est identifié comme bot';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.detector_version IS 'Version du moteur Matomo DeviceDetector';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.event_type IS 'Type fonctionnel de l’événement déclencheur (PROMPT, STATS, LOGOUT, etc.)';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.url IS 'URL ou chemin déclencheur de l’événement';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.session_id IS 'Identifiant de session PHP associé à l’événement';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.created_at IS 'Date de création de l’analyse';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.visitor_id IS 'Identifiant visiteur analytics';
COMMENT ON COLUMN ma_moulinette.user_agent_analysis.user_id IS 'Identifiant de l’utilisateur authentifié';

-- ============================================
-- TABLE user_agent_event
-- ============================================

COMMENT ON TABLE ma_moulinette.user_agent_event IS 'Table de collecte des User-Agent.';
COMMENT ON COLUMN ma_moulinette.user_agent_event.id IS 'Identifiant unique de l’événement User-Agent';
COMMENT ON COLUMN ma_moulinette.user_agent_event.event_type IS 'Type fonctionnel de l’événement déclencheur';
COMMENT ON COLUMN ma_moulinette.user_agent_event.url IS 'URL ou path déclencheur de l’événement';
COMMENT ON COLUMN ma_moulinette.user_agent_event.user_agent IS 'User-Agent HTTP brut fourni par le client';
COMMENT ON COLUMN ma_moulinette.user_agent_event.session_id IS 'Identifiant de session PHP si existant';
COMMENT ON COLUMN ma_moulinette.user_agent_event.user_id IS 'Identifiant de l’utilisateur authentifié';
COMMENT ON COLUMN ma_moulinette.user_agent_event.visitor_id IS 'Identifiant visiteur analytics long terme';
COMMENT ON COLUMN ma_moulinette.user_agent_event.auth_state IS 'État d’authentification lors de l’événement';
COMMENT ON COLUMN ma_moulinette.user_agent_event.processing_status IS 'Statut de traitement par le batch d’analyse';
COMMENT ON COLUMN ma_moulinette.user_agent_event.ip_hash IS 'Hash SHA-256 de l’adresse IP client';
COMMENT ON COLUMN ma_moulinette.user_agent_event.created_at IS 'Date de création de l’événement';
COMMENT ON COLUMN ma_moulinette.user_agent_event.processed_at IS 'Date de traitement de l’événement';

-- ============================================
-- VIEW vw_batch_profiling_stats
-- ============================================
COMMENT ON VIEW ma_moulinette.vw_batch_profiling_stats IS
'Vue d’analyse consolidée des statistiques de performance des traitements.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.portefeuille IS 'Nom du portefeuille.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.utilisateur IS 'Utilisateur ayant déclenché le traitement.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.nb_executions IS 'Nombre total d’exécutions.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.total_projets IS 'Nombre total de projets analysés.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.temps_total_moyen_s IS 'Temps moyen total par exécution (sec).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.temps_moyen_projet_s IS 'Temps moyen par projet (sec).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.memoire_peak_moyenne_mo IS 'Pic mémoire moyen (Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.memoire_moyenne_mo IS 'Mémoire moyenne (Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.memoire_peak_max_mo IS 'Pic mémoire maximum (Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_stats.derniere_execution IS 'Date de dernière exécution.';

-- ============================================
-- VIEW vw_batch_profiling_weekly
-- ============================================
COMMENT ON VIEW ma_moulinette.vw_batch_profiling_weekly IS 'Vue de synthèse hebdomadaire des performances des batchs (temps moyen, mémoire, nombre d’exécutions).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.portefeuille IS 'Nom du portefeuille traité.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.utilisateur IS 'Utilisateur à l’origine des traitements.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.semaine IS 'Date de début de semaine (lundi).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.nb_executions IS 'Nombre total d’exécutions durant la semaine.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.total_projets IS 'Nombre total de projets analysés durant la semaine.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.temps_total_moyen_s IS 'Temps moyen total d’exécution pour la semaine, en secondes.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.temps_moyen_projet_s IS 'Temps moyen par projet durant la semaine.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.memoire_peak_moyenne_mo IS 'Pic mémoire moyen hebdomadaire (Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.memoire_moyenne_mo IS 'Mémoire moyenne utilisée par projet durant la semaine (Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_weekly.memoire_peak_max_mo IS 'Pic mémoire maximal observé durant la semaine.';

-- ============================================
-- VIEW vw_batch_profiling_monthly
-- ============================================
COMMENT ON VIEW ma_moulinette.vw_batch_profiling_monthly IS 'Vue mensuelle consolidée des performances des batchs (temps moyen, mémoire, nombre d’exécutions).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.portefeuille IS 'Nom du portefeuille traité.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.utilisateur IS 'Utilisateur ayant déclenché les traitements.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.mois IS 'Mois et année du regroupement (format YYYY-MM).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.nb_executions IS 'Nombre total d’exécutions sur le mois.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.total_projets IS 'Nombre total de projets analysés durant le mois.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.temps_total_moyen_s IS 'Temps moyen total d’exécution sur le mois (secondes).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.temps_moyen_projet_s IS 'Temps moyen par projet sur le mois.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.memoire_peak_moyenne_mo IS 'Pic mémoire moyen mensuel (Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.memoire_moyenne_mo IS 'Mémoire moyenne utilisée par projet durant le mois (Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.memoire_peak_max_mo IS 'Pic mémoire maximal observé sur le mois.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_monthly.derniere_execution IS 'Dernière exécution du mois enregistrée.';

-- ============================================
-- VIEW vw_batch_profiling_global
-- ============================================
COMMENT ON VIEW ma_moulinette.vw_batch_profiling_global IS 'Vue de synthèse globale des performances (moyennes, pics mémoire, historique complet).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.portefeuille IS 'Nom du portefeuille traité.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.utilisateur IS 'Utilisateur à l’origine des traitements.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.nb_executions_total IS 'Nombre total d’exécutions enregistrées pour ce couple portefeuille/utilisateur.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.total_projets IS 'Nombre total de projets analysés sur l’historique complet.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.temps_total_moyen_s IS 'Temps moyen global d’exécution pour le portefeuille.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.temps_moyen_projet_s IS 'Temps moyen par projet sur l’historique.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.memoire_peak_moyenne_mo IS 'Pic mémoire moyen global (en Mo).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.memoire_moyenne_mo IS 'Mémoire moyenne utilisée par projet sur l’historique.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.memoire_peak_max_mo IS 'Pic mémoire maximum observé sur l’historique.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.premiere_execution IS 'Date de la première exécution enregistrée.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_global.derniere_execution IS 'Date de la dernière exécution enregistrée.';

-- ============================================
-- VIEW vw_batch_profiling_summary
-- ============================================
COMMENT ON VIEW ma_moulinette.vw_batch_profiling_summary IS 'Vue unifiée regroupant les statistiques hebdomadaires, mensuelles et globales des batchs.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.granularite IS 'Type d’agrégation : Hebdomadaire | Mensuel | Global.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.portefeuille IS 'Nom du portefeuille analysé.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.utilisateur IS 'Utilisateur ayant exécuté les traitements.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.periode IS 'Période représentée : semaine (YYYY-Sxx), mois (YYYY-MM) ou Historique complet.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.nb_exec IS 'Nombre d’exécutions comptabilisées pour la période.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.nb_projets IS 'Total des projets traités sur la période.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.temps_total_moyen_s IS 'Durée moyenne des traitements, en secondes.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.temps_moyen_projet_s IS 'Temps moyen par projet traité, en secondes.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.memoire_peak_moyenne_mo IS 'Pic mémoire moyen observé durant la période.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.memoire_moyenne_mo IS 'Usage mémoire moyen par projet durant la période.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.memoire_peak_max_mo IS 'Plus haut pic mémoire observé dans la période.';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.premiere_execution IS 'Date de la première exécution observée (uniquement niveau global).';
COMMENT ON COLUMN ma_moulinette.vw_batch_profiling_summary.derniere_execution IS 'Date de la dernière exécution observée.';

-- ===============================================
-- COMMENTAIRES DES INDEX
-- ===============================================
COMMENT ON INDEX ma_moulinette.idx_batch_profiling_date_execution IS 'Index sur la colonne date_execution pour optimiser les regroupements temporels (weekly, monthly, global).';
COMMENT ON INDEX ma_moulinette.idx_batch_profiling_portefeuille_user IS 'Index composite portefeuille + utilisateur, essentiel pour les vues de statistiques filtrées.';
COMMENT ON INDEX ma_moulinette.idx_batch_profiling_portefeuille IS 'Index sur portefeuille utilisé pour les agrégations globales par zone fonctionnelle.';
COMMENT ON INDEX ma_moulinette.idx_batch_profiling_utilisateur IS 'Index pour optimiser les requêtes par utilisateur (audit, statistiques).';
COMMENT ON INDEX ma_moulinette.idx_batch_profiling_execution_ref IS 'Index sur la référence ULID/UUID du batch, utilisé lors des recherches ciblées.';

-- ===============================================
-- FUNCTION: ma_moulinette.purge_batch_profiling
-- ===============================================
COMMENT ON FUNCTION ma_moulinette.purge_batch_profiling IS
'Supprime les entrées de profiling plus anciennes que X jours et retourne le nombre de lignes supprimées.';
