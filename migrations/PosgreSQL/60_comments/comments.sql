/*
####################################################
##                                                ##
##           Create TABLES                        ##
##           V2.3.0 - 29/03/2026                  ##
##                                                ##
####################################################*/

--- 2025-11-30 : Migration postGreSql 18
--- 2026-02-05 : Ajout du commentaire pour visitor_id de la table user_agent_event.
--- 2026-02-05 : Ajout de l'index pour visitor_id et user_id de la table user_agent_analysis
--- 2026-03-08 : Ajout de la colonne last_activity_at à la table utilisateur
--- 2026-03-29 : Ajout des attributs SonarQube 10 et 2024, alignement des noms sur la version CORE (8 & 9).

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
COMMENT ON COLUMN ma_moulinette.historique.maven_key IS '[CORE] Clé unique du projet, souvent project_key
(fr.monapplication:ma-moulinette).';
COMMENT ON COLUMN ma_moulinette.historique.version IS '[CORE] Version du projet lors de l''analyse. Permet de suivre l''évolution du projet dans le temps.';
COMMENT ON COLUMN ma_moulinette.historique.date_version IS '[CORE] Date de l''analyse du projet';
COMMENT ON COLUMN ma_moulinette.historique.project_name IS '[CORE] Nom du projet i.e. extrait de la maven_key.';
COMMENT ON COLUMN ma_moulinette.historique.analyse_key IS '[CORE] Identifiant unique d’une analyse SonarQube. Permet de tracer un scan précis.';
COMMENT ON COLUMN ma_moulinette.historique.version_release IS '[MA-MOULINETTE] Nombre de version de type RELEASE.';
COMMENT ON COLUMN ma_moulinette.historique.version_snapshot IS '[MA-MOULINETTE] Nombre de version de type SNAPSHOT.';
COMMENT ON COLUMN ma_moulinette.historique.version_autre IS '[MA-MOULINETTE] Nombre de version de type RC, ALPHA, BETA,...';
COMMENT ON COLUMN ma_moulinette.historique.repartition_frontend IS '[MA-MOULINETTE] Nombre de signalement FRONTEND.';
COMMENT ON COLUMN ma_moulinette.historique.repartition_backend IS '[MA-MOULINETTE] Nombre de signalement BACKEND.';
COMMENT ON COLUMN ma_moulinette.historique.repartition_autre IS '[MA-MOULINETTE] Nombre de signalement AUTRE (autre modules).';
COMMENT ON COLUMN ma_moulinette.historique.repartition_inconnu IS '[MA-MOULINETTE] Nombre de signalement INCONNU.';
COMMENT ON COLUMN ma_moulinette.historique.java_no_sonar IS '[CORE] Nombre de noSonar pour JAVA (java:S1309).';
COMMENT ON COLUMN ma_moulinette.historique.python_no_sonar IS '[CORE] Nombre de noSonar pour PYTHON (python:S1309).';
COMMENT ON COLUMN ma_moulinette.historique.php_no_sonar IS '[CORE] Nombre de noSonar pour PHP (php:S1309).';
COMMENT ON COLUMN ma_moulinette.historique.suppress_warning IS '[CORE] Nombre de suppressWarning pour JAVA (java:S1309).';
COMMENT ON COLUMN ma_moulinette.historique.no_pmd IS '[CORE] Nombre de noPMD pour JAVA (java:S1310).';
COMMENT ON COLUMN ma_moulinette.historique.check_style IS '[CORE] Nombre de checkstyle:off pour JAVA (java:S1315).';
COMMENT ON COLUMN ma_moulinette.historique.java_todo IS '[CORE] Nombre de todo pour java (java:S1135).';
COMMENT ON COLUMN ma_moulinette.historique.python_todo IS '[CORE] Nombre de todo pour python (python:S1135).';
COMMENT ON COLUMN ma_moulinette.historique.php_todo IS '[CORE] Nombre de todo pour php (php:S1135).';
COMMENT ON COLUMN ma_moulinette.historique.xml_todo IS '[CORE] Nombre de todo pour xml (xml:S1135).';
COMMENT ON COLUMN ma_moulinette.historique.web_todo IS '[CORE] Nombre de todo pour web (web:S1135).';
COMMENT ON COLUMN ma_moulinette.historique.javascript_todo IS '[CORE] Nombre de todo pour javascript (javascript:S1135).';
COMMENT ON COLUMN ma_moulinette.historique.typescript_todo IS '[CORE] Nombre de todo pour typescript (typescript:S1135).';
COMMENT ON COLUMN ma_moulinette.historique.ruby_todo IS '[CORE] Nombre de todo pour ruby (ruby:S1135).';
COMMENT ON COLUMN ma_moulinette.historique.alert_status IS '[CORE] Statut du Quality Gate. Indique si les critères qualité sont respectés. Les valeurs possibles sont : OK, WARN, ERROR, NONE. Permet de suivre l’évolution de la qualité du projet dans le temps.';
COMMENT ON COLUMN ma_moulinette.historique.lines IS '[CORE] Nombre total de lignes du projet, incluant code, commentaires et lignes vides. Cette métrique donne une vision globale de la taille du code source. Elle permet de suivre l’évolution volumétrique du projet dans le temps.';
COMMENT ON COLUMN ma_moulinette.historique.ncloc IS '[CORE] Nombre de lignes de code effectives, excluant commentaires et lignes vides. Elle représente la quantité réelle de logique métier. C’est une métrique clé pour évaluer la taille fonctionnelle du projet et suivre sa croissance ou sa réduction au fil du temps.';
COMMENT ON COLUMN ma_moulinette.historique.ncloc_language_distribution IS '[CORE] Répartition des lignes de code par langage de programmation. Permet d’identifier les technologies dominantes du projet. Utile pour adapter les stratégies de développement et de maintenance en fonction des langages utilisés.';
COMMENT ON COLUMN ma_moulinette.historique.files IS '[CORE] Nombre total de fichiers analysés dans le projet. Reflète la structure et la granularité du code. Peut être utilisé pour évaluer la complexité organisationnelle.';
COMMENT ON COLUMN ma_moulinette.historique.classes IS '[CORE] Nombre de classes définies dans le code. Indique le niveau d’abstraction et de modularité. Une augmentation peut traduire une architecture plus structurée ou plus complexe.';
COMMENT ON COLUMN ma_moulinette.historique.functions IS '[CORE] Nombre de fonctions ou méthodes présentes dans le code. Permet
d’évaluer la granularité des traitements. Une forte densité peut indiquer un bon découpage ou une complexité excessive.';
COMMENT ON COLUMN ma_moulinette.historique.statements IS '[CORE] Nombre total d’instructions exécutables dans le code. Représente la quantité réelle de logique exécutée. Sert notamment au calcul de la couverture de tests.';
COMMENT ON COLUMN ma_moulinette.historique.comment_lines IS '[CORE] Nombre de lignes contenant des commentaires. Les commentaires facilitent la compréhension du code. Un bon équilibre est essentiel pour la maintenabilité.';
COMMENT ON COLUMN ma_moulinette.historique.comment_lines_density IS '[CORE] Pourcentage de commentaires par rapport au code total. Permet d’évaluer la qualité de la documentation interne. Un taux trop bas peut indiquer un manque de clarté, tandis qu’un taux trop élevé peut suggérer un code difficile à comprendre.';
COMMENT ON COLUMN ma_moulinette.historique.comment_lines_rating IS '[MA-MOULINETTE] Note commentaire (A-E). Permet d’évaluer la qualité des commentaires. Une note élevée indique des commentaires pertinents et bien rédigés, tandis qu’une note basse peut signaler des commentaires insuffisants ou de mauvaise qualité.';
COMMENT ON COLUMN ma_moulinette.historique.coverage IS '[CORE] Pourcentage global de couverture du code par les tests. Mesure la part de code exécutée pendant les tests. Un taux élevé améliore la confiance dans la qualité du code.';
COMMENT ON COLUMN ma_moulinette.historique.branch_coverage IS '[CORE] Couverture des conditions logiques du code (if, switch). Vérifie que toutes les branches sont testées. Réduit les risques de comportements inattendus.';
COMMENT ON COLUMN ma_moulinette.historique.line_coverage IS '[CORE] Pourcentage de lignes exécutées par les tests. Donne une vision simple de la couverture. Complémentaire à la couverture des branches.';
COMMENT ON COLUMN ma_moulinette.historique.lines_to_cover IS '[CORE] Nombre de lignes de code devant être couvertes par les tests. Sert de base au calcul de la couverture. Permet d’identifier les zones critiques.';
COMMENT ON COLUMN ma_moulinette.historique.conditions_to_cover IS '[CORE] Nombre total de conditions à tester. Inclut les branches logiques du code. Plus ce nombre est élevé, plus les tests doivent être complets.';
COMMENT ON COLUMN ma_moulinette.historique.uncovered_conditions IS '[CORE] Nombre de conditions non couvertes par les tests. Indique les zones de risque. Doit être réduit pour améliorer la qualité.';
COMMENT ON COLUMN ma_moulinette.historique.tests IS '[CORE] Nombre total de tests unitaires exécutés. Reflète la stratégie de test du projet. Plus il est élevé, meilleure est la couverture potentielle.';
COMMENT ON COLUMN ma_moulinette.historique.test_execution_time IS '[CORE] Durée totale d’exécution des tests unitaires. Permet d’évaluer leur performance. Un temps trop long peut ralentir les cycles de développement.';
COMMENT ON COLUMN ma_moulinette.historique.test_errors IS '[CORE] Nombre d’erreurs techniques lors de l’exécution des tests. Ces erreurs empêchent leur bon déroulement. Elles doivent être corrigées rapidement.';
COMMENT ON COLUMN ma_moulinette.historique.test_failures IS '[CORE] Nombre de tests ayant échoué. Indique des anomalies fonctionnelles ou régressions. Doit être proche de zéro pour garantir la qualité.';
COMMENT ON COLUMN ma_moulinette.historique.skipped_tests IS '[CORE] Nombre de tests ignorés lors de l’exécution. Ces tests ne contribuent pas à la couverture. Un nombre élevé peut masquer des risques.';
COMMENT ON COLUMN ma_moulinette.historique.test_success_density IS '[CORE] Pourcentage de tests réussis. Mesure la stabilité de  la suite de tests. Un taux élevé indique un code fiable.';
COMMENT ON COLUMN ma_moulinette.historique.duplicated_files IS '[CORE] Nombre de fichiers contenant des duplications. Permet d’identifier les zones redondantes. Impacte la maintenabilité.';
COMMENT ON COLUMN ma_moulinette.historique.duplicated_blocks IS '[CORE] Nombre de blocs de code dupliqués. Indique la présence de copier-coller. La duplication augmente la dette technique.';
COMMENT ON COLUMN ma_moulinette.historique.duplicated_lines IS '[CORE] Nombre de lignes dupliquées dans le code. Représente le volume de duplication. Plus il est élevé, plus le risque de maintenance est important.';
COMMENT ON COLUMN ma_moulinette.historique.duplicated_lines_density IS '[CORE] Pourcentage de duplication dans le code. Mesure la proportion de code copié. Une valeur élevée dégrade la qualité globale.';
COMMENT ON COLUMN ma_moulinette.historique.complexity IS '[CORE] Complexité cyclomatique du code. Mesure le nombre de chemins d’exécution. Une valeur élevée rend le code difficile à tester.';
COMMENT ON COLUMN ma_moulinette.historique.complexity_rating IS '[MA-MOULINETTE] Note de complexité cyclomatique du code. Permet d’évaluer la qualité de la structure du code. Une note élevée indique un code plus complexe et potentiellement plus difficile à maintenir.';
COMMENT ON COLUMN ma_moulinette.historique.cognitive_complexity IS '[CORE] Complexité cognitive du code. Évalue la difficulté de compréhension humaine. Une valeur élevée indique un code difficile à lire.'
COMMENT ON COLUMN ma_moulinette.historique.cognitive_complexity_rating IS '[MA-MOULINETTE] Note de complexité cognitive du code. Permet d’évaluer la lisibilité du code. Une note élevée indique un code potentiellement difficile à comprendre et à maintenir. Il est recommandé de viser une note basse pour améliorer la maintenabilité.';
COMMENT ON COLUMN ma_moulinette.historique.complexity_ratio IS '[MA-MOULINETTE] Ratio de complexité cyclomatique exprimé en pourcentage par rapport à une base de référence (par exemple le nombre de lignes de code ou de fonctions). Cette métrique permet d’évaluer le niveau global de complexité logique du projet. Un ratio élevé indique un code comportant de nombreux chemins d’exécution, donc plus difficile à tester, maintenir et sécuriser.';
COMMENT ON COLUMN ma_moulinette.historique.cognitive_complexity_ratio IS '[MA-MOULINETTE] Ratio de complexité cognitive exprimé en pourcentage par rapport à une base de référence (par exemple le nombre de lignes de code ou de fonctions). Cette métrique permet d’évaluer la difficulté globale de compréhension du code à l’échelle du projet. Un ratio élevé indique un code potentiellement difficile à lire, maintenir et faire évoluer.';
COMMENT ON COLUMN ma_moulinette.historique.open_issues IS '[CORE] Nombre d’issues ouvertes. Représente les problèmes en attente de correction. Permet de suivre la dette en cours.';
COMMENT ON COLUMN ma_moulinette.historique.reopened_issues IS '[CORE] Nombre d’issues réouvertes après correction. Indique des corrections insuffisantes. Peut révéler un manque de qualité dans les fix.';
COMMENT ON COLUMN ma_moulinette.historique.confirmed_issues IS '[CORE] Nombre d’issues confirmées comme valides. Exclut les faux positifs. Représente les problèmes réels.';
COMMENT ON COLUMN ma_moulinette.historique.false_positive_issues IS '[CORE] Nombre d’issues faussement positives.';
COMMENT ON COLUMN ma_moulinette.historique.accepted_issues IS '[10] Nombre d’issues acceptées sans correction. Ces problèmes sont connus mais jugés non prioritaires. Ils restent dans le code.';
COMMENT ON COLUMN ma_moulinette.historique.high_impact_accepted_issues IS '[10] Nombre d’issues acceptées avec un impact élevé (blocker ou high). Représente un risque assumé. Doit être surveillé attentivement.';
COMMENT ON COLUMN ma_moulinette.historique.violations IS '[CORE] Nombre total d’issues détectées. Inclut bugs, vulnérabilités et code smells. Donne une vision globale de la qualité.';
COMMENT ON COLUMN ma_moulinette.historique.blocker_violations IS '[CORE] Nombre d’issues de sévérité bloquante détectées dans le code. Ces problèmes représentent des risques critiques pouvant empêcher le bon fonctionnement ou la mise en production. Ils doivent être corrigés en priorité absolue.';
COMMENT ON COLUMN ma_moulinette.historique.critical_violations IS '[CORE] Nombre d’issues de sévérité critique identifiées. Ces anomalies peuvent entraîner des dysfonctionnements importants ou des comportements incorrects. Leur correction est fortement recommandée avant toute mise en production.';
COMMENT ON COLUMN ma_moulinette.historique.major_violations IS '[CORE] Nombre d’issues de sévérité majeure détectées. Elles impactent la qualité du code et sa maintenabilité sans être bloquantes. Leur traitement permet d’améliorer la robustesse globale du projet.';
COMMENT ON COLUMN ma_moulinette.historique.minor_violations IS '[CORE] Nombre d’issues de sévérité mineure présentes dans le code. Ces problèmes ont un impact limité et concernent souvent des améliorations de style ou de bonnes pratiques. Leur correction reste recommandée pour maintenir un code propre.';
COMMENT ON COLUMN ma_moulinette.historique.info_violations IS '[CORE] Nombre d’issues informatives détectées. Elles n’impactent pas directement la qualité ou le fonctionnement du code. Elles servent principalement à signaler des optimisations ou des bonnes pratiques à suivre.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_blocker_issues IS '[2024] Nombre d’issues de sévérité bloquante selon le modèle de qualité logiciel récent. Ces problèmes représentent des risques critiques pouvant empêcher le fonctionnement correct ou la mise en production. Ils doivent être corrigés immédiatement.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_high_issues IS '[2024] Nombre d’issues de sévérité élevée détectées dans le code. Elles peuvent avoir un impact important sur la qualité, la fiabilité ou la sécurité. Leur correction est fortement prioritaire.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_medium_issues IS '[2024] Nombre d’issues de sévérité moyenne identifiées. Ces problèmes ont un impact modéré sur la qualité du code ou sa maintenabilité. Ils doivent être traités pour améliorer progressivement la qualité globale.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_low_issues IS '[2024] Nombre d’issues de sévérité faible détectées. Elles concernent généralement des optimisations ou des améliorations mineures. Leur correction contribue à maintenir un code propre et cohérent.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_info_issues IS '[2024] Nombre d’issues informatives dans le modèle de qualité logiciel. Elles n’ont pas d’impact direct sur le fonctionnement ou la qualité. Elles servent principalement à signaler des bonnes pratiques ou des améliorations possibles.';
COMMENT ON COLUMN ma_moulinette.historique.code_smells IS '[CORE] Nombre de mauvaises pratiques détectées. Impacte la maintenabilité sans bloquer l’exécution. Contribue à la dette technique.';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_blocker IS '[CORE] Nombre de mauvaises pratiques bloquants. Ces problèmes représentent des risques critiques pour la maintenabilité du code. Ils doivent être corrigés en priorité pour éviter une dette technique importante.';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_critical IS '[CORE] Nombre de mauvaises pratiques critiques. Ces problèmes ont un impact significatif sur la maintenabilité du code. Leur correction est fortement recommandée pour améliorer la qualité globale du projet.';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_major IS '[CORE] Nombre de mauvaises pratiques majeurs. Ces problèmes affectent la qualité du code et sa maintenabilité. Leur traitement permet d’améliorer la robustesse et la lisibilité du projet.';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_minor IS '[CORE] Nombre de mauvaises pratiques mineurs. Ces problèmes ont un impact limité sur la qualité du code. Leur correction reste recommandée pour maintenir un code propre et cohérent.';
COMMENT ON COLUMN ma_moulinette.historique.code_smell_info IS '[CORE] Nombre de mauvaises pratiques d’information. Ces problèmes n’impactent pas directement la maintenabilité du code. Ils servent principalement à signaler des optimisations ou des bonnes pratiques à suivre.';
COMMENT ON COLUMN ma_moulinette.historique.maintainability_issues IS '[10] Ensemble des issues liées à la maintenabilité du code. Elles regroupent principalement les code smells impactant la lisibilité et l’évolution du projet. Ces problèmes augmentent la dette technique et rendent les modifications futures plus coûteuses.';
COMMENT ON COLUMN ma_moulinette.historique.sqale_index IS '[CORE] Dette technique estimée en minutes. Représente l’effort de correction total. Permet de prioriser les actions.';
COMMENT ON COLUMN ma_moulinette.historique.sqale_debt_ratio IS '[CORE] Ratio de dette technique par rapport au coût de développement. Exprimé en pourcentage. Plus il est faible, meilleure est la qualité.';
COMMENT ON COLUMN ma_moulinette.historique.sqale_rating IS '[CORE] Note du niveau de maintenabilité. Permet d’évaluer la qualité de maintenabilité du code. Une note élevée indique une dette technique faible.';
COMMENT ON COLUMN ma_moulinette.historique.effort_to_reach_maintainability_rating_a IS '[10] Effort estimé nécessaire pour atteindre la note A en maintenabilité. Exprimé généralement en temps (minutes ou jours). Permet d’évaluer le coût d’amélioration du code.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_maintainability_issues IS '[2024] Nombre d’issues liées à la maintenabilité selon le modèle de qualité logiciel récent. Inclut les problèmes impactant la lisibilité et l’évolution du code. Une valeur élevée indique un besoin de refactoring.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_maintainability_rating IS '[10] Note de maintenabilité pour la qualité logicielle.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_maintainability_debt_ratio IS '[10] Ratio de dette technique lié à la maintenabilité, exprimé en pourcentage. Il compare l’effort de correction au coût estimé de développement du code. Plus ce ratio est faible, meilleure est la qualité de maintenabilité.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_maintainability_remediation_effort IS '[10] Effort total requis pour corriger les problèmes de maintenabilité. Représente la dette technique associée aux code smells. Sert à prioriser les actions de refactoring.';
COMMENT ON COLUMN ma_moulinette.historique.effort_to_reach_software_quality_maintainability_rating_a IS '[2024] Effort estimé pour atteindre la note A en maintenabilité dans le modèle software quality. Représente le coût de correction des problèmes identifiés. Permet d’anticiper les travaux d’amélioration.';
COMMENT ON COLUMN ma_moulinette.historique.bugs IS '[CORE] Nombre de bugs détectés. Représente les défauts pouvant provoquer des erreurs. Impact direct sur la fiabilité.';
COMMENT ON COLUMN ma_moulinette.historique.bug_blocker IS '[CORE] Nombre de bugs bloquants. Ces problèmes représentent des risques critiques pouvant empêcher le bon fonctionnement ou la mise en production. Ils doivent être corrigés en priorité absolue.';
COMMENT ON COLUMN ma_moulinette.historique.bug_critical IS '[CORE] Nombre de bugs critiques. Ces anomalies peuvent entraîner des dysfonctionnements importants ou des comportements incorrects. Leur correction est fortement recommandée avant toute mise en production.';
COMMENT ON COLUMN ma_moulinette.historique.bug_major IS '[CORE] Nombre de bugs majeurs. Ces problèmes impactent la qualité du code et sa maintenabilité sans être bloquants. Leur traitement permet d’améliorer la robustesse globale du projet.';
COMMENT ON COLUMN ma_moulinette.historique.bug_minor IS '[CORE] Nombre de bugs mineurs.';
COMMENT ON COLUMN ma_moulinette.historique.bug_info IS '[CORE] Nombre de bugs d’information.';
COMMENT ON COLUMN ma_moulinette.historique.reliability_issues IS '[10] Nombre d’issues liées à la fiabilité du code, principalement les bugs. Ces problèmes peuvent provoquer des erreurs à l’exécution ou des comportements inattendus. Leur correction est essentielle pour garantir la stabilité de l’application.';
COMMENT ON COLUMN ma_moulinette.historique.reliability_rating IS '[CORE] Note de fiabilité de A à E. Basée sur les bugs détectés. Permet d’évaluer le niveau de risque.';
COMMENT ON COLUMN ma_moulinette.historique.reliability_remediation_effort IS '[CORE] Effort estimé nécessaire pour corriger les problèmes de fiabilité du code, généralement exprimé en temps. Cette métrique correspond au coût de résolution des bugs identifiés. Elle permet de prioriser les actions visant à améliorer la stabilité du logiciel.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_reliability_issues IS '[2024] Nombre d’issues de fiabilité selon le modèle de qualité logiciel récent. Inclut les bugs impactant le comportement du système. Une valeur élevée indique un risque accru d’erreurs en production.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_reliability_rating IS '[10] Note de fiabilité selon le modèle de qualité logiciel, allant de A à E. Elle est calculée en fonction du nombre et de la sévérité des bugs détectés. Une note élevée indique un faible risque de défaillance en production.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_reliability_remediation_effort IS '[10] Effort total requis pour corriger les problèmes de fiabilité identifiés. Représente le coût de résolution des bugs dans le modèle software quality. Permet d’anticiper les travaux nécessaires pour améliorer la stabilité.';
COMMENT ON COLUMN ma_moulinette.historique.vulnerabilities IS '[CORE] Nombre de vulnérabilités de sécurité détectées. Ces failles peuvent être exploitées. Doivent être corrigées rapidement.';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_blocker IS '[CORE] Nombre de vulnérabilités bloquantes. Ces failles représentent des risques critiques pour la sécurité du système. Elles doivent être corrigées en priorité absolue pour éviter des attaques potentielles.';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_critical IS '[CORE] Nombre de vulnérabilités critiques. Ces failles peuvent entraîner des compromissions importantes du système. Leur correction est fortement recommandée avant toute mise en production.';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_major IS '[CORE] Nombre de vulnérabilités majeures. Ces failles impactent la sécurité du système sans être bloquantes. Leur traitement permet d’améliorer la robustesse globale du projet.';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_minor IS '[CORE] Nombre de vulnérabilités mineures. Ces failles ont un impact limité sur la sécurité du système. Leur correction reste recommandée pour maintenir un niveau de sécurité adéquat.';
COMMENT ON COLUMN ma_moulinette.historique.vulnerability_info IS '[CORE] Nombre de vulnérabilités d’information. Ces failles n’impactent pas directement la sécurité du système. Elles servent principalement à signaler des optimisations ou des bonnes pratiques à suivre pour renforcer la sécurité.';
COMMENT ON COLUMN ma_moulinette.historique.security_issues IS '[10] Ensemble des problèmes de sécurité détectés dans le code, incluant les vulnérabilités. Ces issues peuvent exposer l’application à des attaques ou des failles exploitables. Leur analyse et correction sont essentielles pour protéger le système.';
COMMENT ON COLUMN ma_moulinette.historique.security_rating IS '[CORE] Note de sécurité de A à E. Basée sur les vulnérabilités. Indique le niveau de risque global.';
COMMENT ON COLUMN ma_moulinette.historique.security_remediation_effort IS '[CORE] Effort estimé nécessaire pour corriger les vulnérabilités de sécurité détectées dans le code. Exprimé généralement en temps, il représente le coût de mise en conformité. Cette métrique aide à prioriser les actions de sécurisation du projet.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_security_issues IS '[2024] Nombre d’issues de sécurité identifiées dans le modèle de qualité logiciel récent. Inclut les vulnérabilités pouvant compromettre le système. Une valeur élevée indique un niveau de risque important.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_security_rating IS '[10] Note de sécurité du projet selon le modèle software quality, allant de A à E. Elle dépend du nombre et de la sévérité des vulnérabilités identifiées. Une note élevée indique un faible niveau de risque en matière de sécurité.';
COMMENT ON COLUMN ma_moulinette.historique.software_quality_security_remediation_effort IS '[10] Effort total requis pour corriger les problèmes de sécurité selon le modèle software quality. Représente le coût global de remédiation des vulnérabilités. Permet d’évaluer l’investissement nécessaire pour sécuriser l’application.';
COMMENT ON COLUMN ma_moulinette.historique.security_hotspots IS '[CORE] Nombre de Security Hotspots identifiés, c’est-à-dire des zones de code sensibles nécessitant une revue manuelle. Ces éléments ne sont pas forcément des vulnérabilités mais peuvent le devenir selon le contexte. Ils doivent être analysés par un développeur ou un expert sécurité.';
COMMENT ON COLUMN ma_moulinette.historique.security_review_rating IS '[CORE] Note de revue des Security Hotspots, allant de A à E. Elle reflète la qualité et le niveau de complétude des analyses effectuées sur les zones sensibles. Une bonne note indique un processus de revue sécurité maîtrisé.';
COMMENT ON COLUMN ma_moulinette.historique.security_hotspots_reviewed IS '[CORE] Pourcentage de Security Hotspots ayant été examinés et validés. Indique le niveau de couverture des revues de sécurité manuelles. Un taux élevé signifie que les zones sensibles ont été correctement analysées.';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_to_review_high IS '[CORE] Nombre de menaces potentielles de sécurité de niveau élevé à vérifier.';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_to_review_medium IS '[CORE] Nombre de menaces potentielles moyennes à vérifier.';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_to_review_low IS '[CORE] Nombre de menaces potentielles faibles à vérifier.';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_reviewed_high IS '[CORE] Nombre de menaces potentielle élevées vérifiées.';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_reviewed_medium IS '[CORE] Nombre de menaces potentielle moyennes vérifiées.';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_reviewed_low IS '[CORE] Nombre de menaces potentielle faibles vérifiées.';
COMMENT ON COLUMN ma_moulinette.historique.menace_potentielle_totale IS '[MA-MOULINETTE] Nombre total de menaces potentielles vérifiées.';
COMMENT ON COLUMN ma_moulinette.historique.actuator_info IS '[MA-MOULINETTE] Information Actuator du projet.';
COMMENT ON COLUMN ma_moulinette.historique.logger_info IS '[MA-MOULINETTE] Nombre de logger de type INFO.';
COMMENT ON COLUMN ma_moulinette.historique.logger_warn IS '[MA-MOULINETTE] Nombre de logger de type WARN.';
COMMENT ON COLUMN ma_moulinette.historique.logger_error IS '[MA-MOULINETTE] Nombre de logger de type ERROR.';
COMMENT ON COLUMN ma_moulinette.historique.logger_debug IS '[MA-MOULINETTE] Nombre de logger de type DEBUG.';
COMMENT ON COLUMN ma_moulinette.historique.initial IS '[MA-MOULINETTE] Indique si c’est la version de référence. Permet de suivre l’évolution du projet à partir d’un point de départ.';
COMMENT ON COLUMN ma_moulinette.historique.mode_collecte IS '[MA-MOULINETTE] Type de collecte : REBUILD | COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE.';
COMMENT ON COLUMN ma_moulinette.historique.utilisateur_collecte IS '[MA-MOULINETTE] Auteur de la collecte de données.';
COMMENT ON COLUMN ma_moulinette.historique.date_enregistrement IS '[MA-MOULINETTE] date de l’événement.';
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
COMMENT ON COLUMN ma_moulinette.mesures.maven_key IS '[CORE] Clé unique du projet, souvent project_key (fr.monapplication:ma-moulinette).';
COMMENT ON COLUMN ma_moulinette.mesures.project_name IS '[CORE] Nom du projet i.e. extrait de la maven_key.';

COMMENT ON COLUMN ma_moulinette.mesures.alert_status IS '[CORE] Statut du Quality Gate. Indique si les critères qualité sont respectés. Les valeurs possibles sont : OK, WARN, ERROR, NONE. Permet de suivre l’évolution de la qualité du projet dans le temps.';
COMMENT ON COLUMN ma_moulinette.mesures.lines IS '[CORE] Nombre total de lignes du projet, incluant code, commentaires et lignes vides. Cette métrique donne une vision globale de la taille du code source. Elle permet de suivre l’évolution volumétrique du projet dans le temps.';
COMMENT ON COLUMN ma_moulinette.mesures.ncloc IS '[CORE] Nombre de lignes de code effectives, excluant commentaires et lignes vides. Elle représente la quantité réelle de logique métier. C’est une métrique clé pour évaluer la taille fonctionnelle du projet et suivre sa croissance ou sa réduction au fil du temps.';
COMMENT ON COLUMN ma_moulinette.mesures.ncloc_language_distribution IS '[CORE] Répartition des lignes de code par langage de programmation. Permet d’identifier les technologies dominantes du projet. Utile pour adapter les stratégies de développement et de maintenance en fonction des langages utilisés.';
COMMENT ON COLUMN ma_moulinette.mesures.files IS '[CORE] Nombre total de fichiers analysés dans le projet. Reflète la structure et la granularité du code. Peut être utilisé pour évaluer la complexité organisationnelle.';
COMMENT ON COLUMN ma_moulinette.mesures.classes IS '[CORE] Nombre de classes définies dans le code. Indique le niveau d’abstraction et de modularité. Une augmentation peut traduire une architecture plus structurée ou plus complexe.';
COMMENT ON COLUMN ma_moulinette.mesures.functions IS '[CORE] Nombre de fonctions ou méthodes présentes dans le code. Permet
d’évaluer la granularité des traitements. Une forte densité peut indiquer un bon découpage ou une complexité excessive.';
COMMENT ON COLUMN ma_moulinette.mesures.statements IS '[CORE] Nombre total d’instructions exécutables dans le code. Représente la quantité réelle de logique exécutée. Sert notamment au calcul de la couverture de tests.';
COMMENT ON COLUMN ma_moulinette.mesures.comment_lines IS '[CORE] Nombre de lignes contenant des commentaires. Les commentaires facilitent la compréhension du code. Un bon équilibre est essentiel pour la maintenabilité.';
COMMENT ON COLUMN ma_moulinette.mesures.comment_lines_density IS '[CORE] Pourcentage de commentaires par rapport au code total. Permet d’évaluer la qualité de la documentation interne. Un taux trop bas peut indiquer un manque de clarté, tandis qu’un taux trop élevé peut suggérer un code difficile à comprendre.';
COMMENT ON COLUMN ma_moulinette.mesures.comment_lines_rating IS '[MA-MOULINETTE] Note commentaire (A-E). Permet d’évaluer la qualité des commentaires. Une note élevée indique des commentaires pertinents et bien rédigés, tandis qu’une note basse peut signaler des commentaires insuffisants ou de mauvaise qualité.';

COMMENT ON COLUMN ma_moulinette.mesures.coverage IS '[CORE] Pourcentage global de couverture du code par les tests. Mesure la part de code exécutée pendant les tests. Un taux élevé améliore la confiance dans la qualité du code.';
COMMENT ON COLUMN ma_moulinette.mesures.branch_coverage IS '[CORE] Couverture des conditions logiques du code (if, switch). Vérifie que toutes les branches sont testées. Réduit les risques de comportements inattendus.';
COMMENT ON COLUMN ma_moulinette.mesures.line_coverage IS '[CORE] Pourcentage de lignes exécutées par les tests. Donne une vision simple de la couverture. Complémentaire à la couverture des branches.';
COMMENT ON COLUMN ma_moulinette.mesures.lines_to_cover IS '[CORE] Nombre de lignes de code devant être couvertes par les tests. Sert de base au calcul de la couverture. Permet d’identifier les zones critiques.';
COMMENT ON COLUMN ma_moulinette.mesures.conditions_to_cover IS '[CORE] Nombre total de conditions à tester. Inclut les branches logiques du code. Plus ce nombre est élevé, plus les tests doivent être complets.';
COMMENT ON COLUMN ma_moulinette.mesures.uncovered_conditions IS '[CORE] Nombre de conditions non couvertes par les tests. Indique les zones de risque. Doit être réduit pour améliorer la qualité.';
COMMENT ON COLUMN ma_moulinette.mesures.tests IS '[CORE] Nombre total de tests unitaires exécutés. Reflète la stratégie de test du projet. Plus il est élevé, meilleure est la couverture potentielle.';
COMMENT ON COLUMN ma_moulinette.mesures.test_execution_time IS '[CORE] Durée totale d’exécution des tests unitaires. Permet d’évaluer leur performance. Un temps trop long peut ralentir les cycles de développement.';
COMMENT ON COLUMN ma_moulinette.mesures.test_errors IS '[CORE] Nombre d’erreurs techniques lors de l’exécution des tests. Ces erreurs empêchent leur bon déroulement. Elles doivent être corrigées rapidement.';
COMMENT ON COLUMN ma_moulinette.mesures.test_failures IS '[CORE] Nombre de tests ayant échoué. Indique des anomalies fonctionnelles ou régressions. Doit être proche de zéro pour garantir la qualité.';
COMMENT ON COLUMN ma_moulinette.mesures.skipped_tests IS '[CORE] Nombre de tests ignorés lors de l’exécution. Ces tests ne contribuent pas à la couverture. Un nombre élevé peut masquer des risques.';

COMMENT ON COLUMN ma_moulinette.mesures.test_success_density IS '[CORE] Pourcentage de tests réussis. Mesure la stabilité de  la suite de tests. Un taux élevé indique un code fiable.';
COMMENT ON COLUMN ma_moulinette.mesures.duplicated_files IS '[CORE] Nombre de fichiers contenant des duplications. Permet d’identifier les zones redondantes. Impacte la maintenabilité.';
COMMENT ON COLUMN ma_moulinette.mesures.duplicated_blocks IS '[CORE] Nombre de blocs de code dupliqués. Indique la présence de copier-coller. La duplication augmente la dette technique.';
COMMENT ON COLUMN ma_moulinette.mesures.duplicated_lines IS '[CORE] Nombre de lignes dupliquées dans le code. Représente le volume de duplication. Plus il est élevé, plus le risque de maintenance est important.';
COMMENT ON COLUMN ma_moulinette.mesures.duplicated_lines_density IS '[CORE] Pourcentage de duplication dans le code. Mesure la proportion de code copié. Une valeur élevée dégrade la qualité globale.';

COMMENT ON COLUMN ma_moulinette.mesures.complexity IS '[CORE] Complexité cyclomatique du code. Mesure le nombre de chemins d’exécution. Une valeur élevée rend le code difficile à tester.';
COMMENT ON COLUMN ma_moulinette.mesures.complexity_rating IS '[MA-MOULINETTE] Note de complexité cyclomatique du code. Permet d’évaluer la qualité de la structure du code. Une note élevée indique un code plus complexe et potentiellement plus difficile à maintenir.';
COMMENT ON COLUMN ma_moulinette.mesures.cognitive_complexity IS '[CORE] Complexité cognitive du code. Évalue la difficulté de compréhension humaine. Une valeur élevée indique un code difficile à lire.'
COMMENT ON COLUMN ma_moulinette.mesures.cognitive_complexity_rating IS '[MA-MOULINETTE] Note de complexité cognitive du code. Permet d’évaluer la lisibilité du code. Une note élevée indique un code potentiellement difficile à comprendre et à maintenir. Il est recommandé de viser une note basse pour améliorer la maintenabilité.';
COMMENT ON COLUMN ma_moulinette.mesures.complexity_ratio IS '[MA-MOULINETTE] Ratio de complexité cyclomatique exprimé en pourcentage par rapport à une base de référence (par exemple le nombre de lignes de code ou de fonctions). Cette métrique permet d’évaluer le niveau global de complexité logique du projet. Un ratio élevé indique un code comportant de nombreux chemins d’exécution, donc plus difficile à tester, maintenir et sécuriser.';
COMMENT ON COLUMN ma_moulinette.mesures.cognitive_complexity_ratio IS '[MA-MOULINETTE] Ratio de complexité cognitive exprimé en pourcentage par rapport à une base de référence (par exemple le nombre de lignes de code ou de fonctions). Cette métrique permet d’évaluer la difficulté globale de compréhension du code à l’échelle du projet. Un ratio élevé indique un code potentiellement difficile à lire, maintenir et faire évoluer.';

COMMENT ON COLUMN ma_moulinette.mesures.open_issues IS '[CORE] Nombre d’issues ouvertes. Représente les problèmes en attente de correction. Permet de suivre la dette en cours.';
COMMENT ON COLUMN ma_moulinette.mesures.reopened_issues IS '[CORE] Nombre d’issues réouvertes après correction. Indique des corrections insuffisantes. Peut révéler un manque de qualité dans les fix.';
COMMENT ON COLUMN ma_moulinette.mesures.confirmed_issues IS '[CORE] Nombre d’issues confirmées comme valides. Exclut les faux positifs. Représente les problèmes réels.';
COMMENT ON COLUMN ma_moulinette.mesures.false_positive_issues IS '[CORE] Nombre d’issues faussement positives.';
COMMENT ON COLUMN ma_moulinette.mesures.accepted_issues IS '[10] Nombre d’issues acceptées sans correction. Ces problèmes sont connus mais jugés non prioritaires. Ils restent dans le code.';
COMMENT ON COLUMN ma_moulinette.mesures.high_impact_accepted_issues IS '[10] Nombre d’issues acceptées avec un impact élevé (blocker ou high). Représente un risque assumé. Doit être surveillé attentivement.';
COMMENT ON COLUMN ma_moulinette.mesures.violations IS '[CORE] Nombre total d’issues détectées. Inclut bugs, vulnérabilités et code smells. Donne une vision globale de la qualité.';
COMMENT ON COLUMN ma_moulinette.mesures.blocker_violations IS '[CORE] Nombre d’issues de sévérité bloquante détectées dans le code. Ces problèmes représentent des risques critiques pouvant empêcher le bon fonctionnement ou la mise en production. Ils doivent être corrigés en priorité absolue.';
COMMENT ON COLUMN ma_moulinette.mesures.critical_violations IS '[CORE] Nombre d’issues de sévérité critique identifiées. Ces anomalies peuvent entraîner des dysfonctionnements importants ou des comportements incorrects. Leur correction est fortement recommandée avant toute mise en production.';
COMMENT ON COLUMN ma_moulinette.mesures.major_violations IS '[CORE] Nombre d’issues de sévérité majeure détectées. Elles impactent la qualité du code et sa maintenabilité sans être bloquantes. Leur traitement permet d’améliorer la robustesse globale du projet.';
COMMENT ON COLUMN ma_moulinette.mesures.minor_violations IS '[CORE] Nombre d’issues de sévérité mineure présentes dans le code. Ces problèmes ont un impact limité et concernent souvent des améliorations de style ou de bonnes pratiques. Leur correction reste recommandée pour maintenir un code propre.';
COMMENT ON COLUMN ma_moulinette.mesures.info_violations IS '[CORE] Nombre d’issues informatives détectées. Elles n’impactent pas directement la qualité ou le fonctionnement du code. Elles servent principalement à signaler des optimisations ou des bonnes pratiques à suivre.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_blocker_issues IS '[2024] Nombre d’issues de sévérité bloquante selon le modèle de qualité logiciel récent. Ces problèmes représentent des risques critiques pouvant empêcher le fonctionnement correct ou la mise en production. Ils doivent être corrigés immédiatement.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_high_issues IS '[2024] Nombre d’issues de sévérité élevée détectées dans le code. Elles peuvent avoir un impact important sur la qualité, la fiabilité ou la sécurité. Leur correction est fortement prioritaire.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_medium_issues IS '[2024] Nombre d’issues de sévérité moyenne identifiées. Ces problèmes ont un impact modéré sur la qualité du code ou sa maintenabilité. Ils doivent être traités pour améliorer progressivement la qualité globale.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_low_issues IS '[2024] Nombre d’issues de sévérité faible détectées. Elles concernent généralement des optimisations ou des améliorations mineures. Leur correction contribue à maintenir un code propre et cohérent.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_info_issues IS '[2024] Nombre d’issues informatives dans le modèle de qualité logiciel. Elles n’ont pas d’impact direct sur le fonctionnement ou la qualité. Elles servent principalement à signaler des bonnes pratiques ou des améliorations possibles.';

COMMENT ON COLUMN ma_moulinette.mesures.code_smells IS '[CORE] Nombre de mauvaises pratiques détectées. Impacte la maintenabilité sans bloquer l’exécution. Contribue à la dette technique.';
COMMENT ON COLUMN ma_moulinette.mesures.maintainability_issues IS '[10] Ensemble des issues liées à la maintenabilité du code. Elles regroupent principalement les code smells impactant la lisibilité et l’évolution du projet. Ces problèmes augmentent la dette technique et rendent les modifications futures plus coûteuses.';
COMMENT ON COLUMN ma_moulinette.mesures.sqale_index IS '[CORE] Dette technique estimée en minutes. Représente l’effort de correction total. Permet de prioriser les actions.';
COMMENT ON COLUMN ma_moulinette.mesures.sqale_debt_ratio IS '[CORE] Ratio de dette technique par rapport au coût de développement. Exprimé en pourcentage. Plus il est faible, meilleure est la qualité.';
COMMENT ON COLUMN ma_moulinette.mesures.sqale_rating IS '[CORE] Note du niveau de maintenabilité. Permet d’évaluer la qualité de maintenabilité du code. Une note élevée indique une dette technique faible.';
COMMENT ON COLUMN ma_moulinette.mesures.effort_to_reach_maintainability_rating_a IS '[10] Effort estimé nécessaire pour atteindre la note A en maintenabilité. Exprimé généralement en temps (minutes ou jours). Permet d’évaluer le coût d’amélioration du code.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_maintainability_issues IS '[2024] Nombre d’issues liées à la maintenabilité selon le modèle de qualité logiciel récent. Inclut les problèmes impactant la lisibilité et l’évolution du code. Une valeur élevée indique un besoin de refactoring.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_maintainability_rating IS '[10] Note de maintenabilité pour la qualité logicielle.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_maintainability_debt_ratio IS '[10] Ratio de dette technique lié à la maintenabilité, exprimé en pourcentage. Il compare l’effort de correction au coût estimé de développement du code. Plus ce ratio est faible, meilleure est la qualité de maintenabilité.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_maintainability_remediation_effort IS '[10] Effort total requis pour corriger les problèmes de maintenabilité. Représente la dette technique associée aux code smells. Sert à prioriser les actions de refactoring.';
COMMENT ON COLUMN ma_moulinette.mesures.effort_to_reach_software_quality_maintainability_rating_a IS '[2024] Effort estimé pour atteindre la note A en maintenabilité dans le modèle software quality. Représente le coût de correction des problèmes identifiés. Permet d’anticiper les travaux d’amélioration.';

COMMENT ON COLUMN ma_moulinette.mesures.bugs IS '[CORE] Nombre de bugs détectés. Représente les défauts pouvant provoquer des erreurs. Impact direct sur la fiabilité.';
COMMENT ON COLUMN ma_moulinette.mesures.reliability_issues IS '[10] Nombre d’issues liées à la fiabilité du code, principalement les bugs. Ces problèmes peuvent provoquer des erreurs à l’exécution ou des comportements inattendus. Leur correction est essentielle pour garantir la stabilité de l’application.';
COMMENT ON COLUMN ma_moulinette.mesures.reliability_rating IS '[CORE] Note de fiabilité de A à E. Basée sur les bugs détectés. Permet d’évaluer le niveau de risque.';
COMMENT ON COLUMN ma_moulinette.mesures.reliability_remediation_effort IS '[CORE] Effort estimé nécessaire pour corriger les problèmes de fiabilité du code, généralement exprimé en temps. Cette métrique correspond au coût de résolution des bugs identifiés. Elle permet de prioriser les actions visant à améliorer la stabilité du logiciel.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_reliability_issues IS '[2024] Nombre d’issues de fiabilité selon le modèle de qualité logiciel récent. Inclut les bugs impactant le comportement du système. Une valeur élevée indique un risque accru d’erreurs en production.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_reliability_rating IS '[10] Note de fiabilité selon le modèle de qualité logiciel, allant de A à E. Elle est calculée en fonction du nombre et de la sévérité des bugs détectés. Une note élevée indique un faible risque de défaillance en production.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_reliability_remediation_effort IS '[10] Effort total requis pour corriger les problèmes de fiabilité identifiés. Représente le coût de résolution des bugs dans le modèle software quality. Permet d’anticiper les travaux nécessaires pour améliorer la stabilité.';

COMMENT ON COLUMN ma_moulinette.mesures.vulnerabilities IS '[CORE] Nombre de vulnérabilités de sécurité détectées. Ces failles peuvent être exploitées. Doivent être corrigées rapidement.';

COMMENT ON COLUMN ma_moulinette.mesures.security_issues IS '[10] Ensemble des problèmes de sécurité détectés dans le code, incluant les vulnérabilités. Ces issues peuvent exposer l’application à des attaques ou des failles exploitables. Leur analyse et correction sont essentielles pour protéger le système.';
COMMENT ON COLUMN ma_moulinette.mesures.security_rating IS '[CORE] Note de sécurité de A à E. Basée sur les vulnérabilités. Indique le niveau de risque global.';
COMMENT ON COLUMN ma_moulinette.mesures.security_remediation_effort IS '[CORE] Effort estimé nécessaire pour corriger les vulnérabilités de sécurité détectées dans le code. Exprimé généralement en temps, il représente le coût de mise en conformité. Cette métrique aide à prioriser les actions de sécurisation du projet.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_security_issues IS '[2024] Nombre d’issues de sécurité identifiées dans le modèle de qualité logiciel récent. Inclut les vulnérabilités pouvant compromettre le système. Une valeur élevée indique un niveau de risque important.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_security_rating IS '[10] Note de sécurité du projet selon le modèle software quality, allant de A à E. Elle dépend du nombre et de la sévérité des vulnérabilités identifiées. Une note élevée indique un faible niveau de risque en matière de sécurité.';
COMMENT ON COLUMN ma_moulinette.mesures.software_quality_security_remediation_effort IS '[10] Effort total requis pour corriger les problèmes de sécurité selon le modèle software quality. Représente le coût global de remédiation des vulnérabilités. Permet d’évaluer l’investissement nécessaire pour sécuriser l’application.';

COMMENT ON COLUMN ma_moulinette.mesures.security_hotspots IS '[CORE] Nombre de Security Hotspots identifiés, c’est-à-dire des zones de code sensibles nécessitant une revue manuelle. Ces éléments ne sont pas forcément des vulnérabilités mais peuvent le devenir selon le contexte. Ils doivent être analysés par un développeur ou un expert sécurité.';
COMMENT ON COLUMN ma_moulinette.mesures.security_review_rating IS '[CORE] Note de revue des Security Hotspots, allant de A à E. Elle reflète la qualité et le niveau de complétude des analyses effectuées sur les zones sensibles. Une bonne note indique un processus de revue sécurité maîtrisé.';
COMMENT ON COLUMN ma_moulinette.mesures.security_hotspots_reviewed IS '[CORE] Pourcentage de Security Hotspots ayant été examinés et validés. Indique le niveau de couverture des revues de sécurité manuelles. Un taux élevé signifie que les zones sensibles ont été correctement analysées.';

COMMENT ON COLUMN ma_moulinette.mesures.mode_collecte IS '[MA-MOULINETTE] Type de collecte : REBUILD | COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE.';
COMMENT ON COLUMN ma_moulinette.mesures.utilisateur_collecte IS '[MA-MOULINETTE] Auteur de la collecte de données.';
COMMENT ON COLUMN ma_moulinette.mesures.date_enregistrement IS '[MA-MOULINETTE] date de l’événement.';
-- ============================================
-- TABLE no_sonar
-- ============================================
COMMENT ON COLUMN ma_moulinette.no_sonar.id IS 'Identifiant unique pour chaque entrée NoSonar';
COMMENT ON COLUMN ma_moulinette.no_sonar.maven_key IS 'Clé Maven du projet';
COMMENT ON COLUMN ma_moulinette.no_sonar.rule IS 'Règle appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.component IS 'Composant auquel la règle est appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.line IS 'Ligne où la règle est appliquée';
COMMENT ON COLUMN ma_moulinette.no_sonar.mode_collecte IS 'Mode de collecte : collecte, traitement manuel ou traitement automatique';
COMMENT ON COLUMN ma_moulinette.no_sonar.utilisateur_collecte IS 'Compte de l’utilisateur qui a réalisé la collecte';
COMMENT ON COLUMN ma_moulinette.no_sonar.date_enregistrement IS 'Date d’enregistrement de l’entrée NoSonar';
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
COMMENT ON COLUMN ma_moulinette.utilisateur.last_activity_at IS 'Dernière connexion.';
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
