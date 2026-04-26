-- ############################################################################
-- ## MASTER INSTALL Ma-Moulinette
-- ## V2.0.1 - 24/04/2026
-- ##
-- ## USAGE : psql -U postgres -v ON_ERROR_STOP=1 -f 99_master_install.sql
-- ##         (depuis le repertoire migrations/PosgreSQL/)
-- ##
-- ## Note : \ir = include relative (relatif au fichier courant).
-- ##        Necessite PostgreSQL >= 9.6.
-- ##
-- ## Ordre d'execution :
-- ##   1. Drop DB + drop role                  (00_init/00)
-- ##   2. Create role                          (00_init/01)
-- ##   3. Create database                      (00_init/02)
-- ##   4. Extensions (pg_stat_statements)      (00_init/03)
-- ##   5. Search path                          (00_init/04)
-- ##   6. Schema ma_moulinette                 (10_schema/01)
-- ##   7. Tables 20_tables                     (37 fichiers .sql)
-- ##   8. Views 30_views                       (5 fichiers .sql)
-- ##   9. Indexes 40_indexes
-- ##  10. Functions 50_functions               (2 fichiers .sql)
-- ##  11. Comments 60_comments
-- ##  12. Constraints 70_constraints
-- ##  13. Grants 80_grants
-- ##  14. Fixtures 90_fixtures
-- ############################################################################

\c postgres;
\echo ===== 1. Drop database + role =====
\ir 00_init/00_drop_database.sql

\echo ===== 2. Create role =====
\ir 00_init/01_create_roles.sql

\echo ===== 3. Create database =====
\ir 00_init/02_create_database.sql

\echo ===== 4. Extensions =====
\ir 00_init/03_extensions.sql

\echo ===== 5. Search path =====
\ir 00_init/04_search_path.sql

\echo ===== 6. Schema =====
\ir 10_schema/01_create_schema.sql

\echo ===== 7. Tables =====
\ir 20_tables/activity.sql
\ir 20_tables/activity_batch_report.sql
\ir 20_tables/activity_historique.sql
\ir 20_tables/actuator.sql
\ir 20_tables/actuator_info.sql
\ir 20_tables/anomalie.sql
\ir 20_tables/anomalie_details.sql
\ir 20_tables/batch.sql
\ir 20_tables/batch_execution.sql
\ir 20_tables/batch_execution_journal.sql
\ir 20_tables/batch_profiling.sql
\ir 20_tables/batch_traitement.sql
\ir 20_tables/groupe_fonctionnel.sql
\ir 20_tables/groupe_utilisateur.sql
\ir 20_tables/historique.sql
\ir 20_tables/hotspot_details.sql
\ir 20_tables/hotspot_owasp.sql
\ir 20_tables/hotspots.sql
\ir 20_tables/information_projet.sql
\ir 20_tables/liste_projet.sql
\ir 20_tables/logger.sql
\ir 20_tables/ma_moulinette.sql
\ir 20_tables/mesures.sql
\ir 20_tables/no_sonar.sql
\ir 20_tables/owasp.sql
\ir 20_tables/owasp_top10.sql
\ir 20_tables/portefeuille.sql
\ir 20_tables/portefeuille_historique.sql
\ir 20_tables/profiles.sql
\ir 20_tables/profiles_historique.sql
\ir 20_tables/properties.sql
\ir 20_tables/repartition.sql
\ir 20_tables/repartition_temp.sql
\ir 20_tables/todo.sql
\ir 20_tables/user_agent_analysis.sql
\ir 20_tables/user_agent_event.sql
\ir 20_tables/user_role_log.sql
\ir 20_tables/utilisateur.sql

\echo ===== 8. Views =====
\ir 30_views/01_vw_batch_profiling_weekly.sql
\ir 30_views/02_vw_batch_profiling_monthly.sql
\ir 30_views/03_vw_batch_profiling_global.sql
\ir 30_views/04_vw_batch_profiling_stats.sql
\ir 30_views/05_vw_batch_profiling_summary.sql

\echo ===== 9. Indexes =====
\ir 40_indexes/indexes.sql

\echo ===== 10. Functions =====
\ir 50_functions/get_pg_stat_activity.sql
\ir 50_functions/purge_batch_profiling.sql

\echo ===== 11. Comments =====
\ir 60_comments/comments.sql

\echo ===== 12. Constraints =====
\ir 70_constraints/constraints.sql

\echo ===== 13. Grants =====
\ir 80_grants/grants.sql

\echo ===== 14. Fixtures =====
\ir 90_fixtures/fixtures.sql

\echo ===== 15. Fixtures E2E (Phase K) =====
\ir 90_fixtures/fixtures-e2e.sql

\echo ===== INSTALL TERMINE =====
