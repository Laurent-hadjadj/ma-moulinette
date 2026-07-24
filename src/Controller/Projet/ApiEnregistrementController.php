<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\Projet;

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Response, Request};
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Historique;
use App\Entity\CleanCode;

/**
 * [Description ApiEnregistrementController]
 */
class ApiEnregistrementController extends AbstractController
{
    use AppUserAware;

    /** Définition des constantes */
    private static string $europeParis = "Europe/Paris";
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static string $loggerE403 = "[Enregistrement] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_COLLECTE).";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {}

    /**
     * [Description for enregistrement]
     * Enregistrement des données du projet
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:44:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/enregistrement', name: 'enregistrement', methods: ['PUT'])]
    public function enregistrement(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/enregistrement");

        $user = $this->appUser();

        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->error(self::$loggerE403, ['user' => $user]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403
            ], Response::HTTP_OK);
        }

        /** On décode le body. */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null) {
            $this->logger->error(
                "[Enregistrement] ❌ Requête invalide : clé 'data' manquante ou JSON mal formé.",
                ['payload' => $data]
            );

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On créé un objet date Immutable, avec la date courante. */
        $dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();

        /* MODIF 2026-05-17 : lecture depuis clean_code pour
         * alimenter les colonnes historique (retro-compatible : null si pas de collecte). */
        $cleanCodeRepos = $this->em->getRepository(CleanCode::class);
        $cleanCodeResult = $cleanCodeRepos->selectCleanCode(['maven_key' => $data->maven_key]);
        $cc = (!empty($cleanCodeResult['liste'])) ? $cleanCodeResult['liste'][0] : [];

        try {
            /* MODIF 2026-07-23 : $json valait auparavant toujours [] — actuator_info
             * n'était donc jamais persisté par ce chemin d'enregistrement interactif
             * (seul le batch/cron CollecteController écrivait réellement dans cette
             * colonne). Même pattern de décodage que logger_breakdown ci-dessous. */
            $json = [];
            if (property_exists($data, 'actuator_info') && is_object($data->actuator_info)) {
                $json = json_decode(json_encode($data->actuator_info), true);
            } elseif (property_exists($data, 'actuator_info') && is_array($data->actuator_info)) {
                $json = $data->actuator_info;
            } else {
                $this->logger->debug('[Enregistrement] 🔍 actuator_info absent ou null (pas de point d\'accès déclaré, ou collecte non lancée).');
            }
            $map = [
                'maven_key' => $data->maven_key,
                'analyse_key' => $data->analyse_key,
                'version' => $data->version,
                'date_version' => $data->date_version,
                'project_name' => $data->project_name,
                'version_release' => $data->version_release,
                'version_snapshot' => $data->version_snapshot,
                'version_autre' => $data->version_autre,
                'suppress_warning' => $data->suppress_warning,
                'no_sonar' => $data->no_sonar,
                'todo' => $data->todo,
                'logger_info' => $data->logger_info,
                'logger_warn' => $data->logger_warn,
                'logger_error' => $data->logger_error,
                'logger_debug' => $data->logger_debug,
                'lines' => $data->lines,
                'ncloc' => $data->ncloc,
                'files' => $data->files,
                'classes' => $data->classes,
                'functions' => $data->functions,
                'coverage' => $data->coverage,
                'duplicated_lines_density' => $data->duplicated_lines_density,
                'sqale_debt_ratio' => $data->sqale_debt_ratio,
                'tests' => $data->tests,
                'violations' => $data->violations,
                'sqale_index' => $data->sqale_index,
                'bugs' => $data->bugs,
                'vulnerabilities' => $data->vulnerabilities,
                'code_smells' => $data->code_smells,
                'bug_blocker' => $data->bug_blocker,
                'bug_critical' => $data->bug_critical,
                'bug_major' => $data->bug_major,
                'bug_minor' => $data->bug_minor,
                'bug_info' => $data->bug_info,
                'vulnerability_blocker' => $data->vulnerability_blocker,
                'vulnerability_critical' => $data->vulnerability_critical,
                'vulnerability_major' => $data->vulnerability_major,
                'vulnerability_minor' => $data->vulnerability_minor,
                'vulnerability_info' => $data->vulnerability_info,
                'code_smell_blocker' => $data->code_smell_blocker,
                'code_smell_critical' => $data->code_smell_critical,
                'code_smell_major' => $data->code_smell_major,
                'code_smell_minor' => $data->code_smell_minor,
                'code_smell_info' => $data->code_smell_info,
                'repartition_frontend' => $data->repartition_frontend,
                'repartition_backend' => $data->repartition_backend,
                'repartition_autre' => $data->repartition_autre,
                'repartition_inconnu' => $data->repartition_inconnu,
                'blocker_violations' => $data->blocker_violations,
                'critical_violations' => $data->critical_violations,
                'major_violations' => $data->major_violations,
                'minor_violations' => $data->minor_violations,
                'info_violations' => $data->info_violations,
                'reliability_rating' => $data->reliability_rating,
                'security_rating' => $data->security_rating,
                'sqale_rating' => $data->sqale_rating,
                'security_review_rating' => $data->security_review_rating,
                'menace_potentielle_totale' => $data->menace_potentielle_totale,
                'menace_potentielle_to_review_high' => $data->menace_potentielle_to_review_high,
                'menace_potentielle_to_review_medium' => $data->menace_potentielle_to_review_medium,
                'menace_potentielle_to_review_low' => $data->menace_potentielle_to_review_low,
                'menace_potentielle_reviewed_high' => $data->menace_potentielle_reviewed_high,
                'menace_potentielle_reviewed_medium' => $data->menace_potentielle_reviewed_medium,
                'menace_potentielle_reviewed_low' => $data->menace_potentielle_reviewed_low,
                /* MODIF 2026-05-06 : nouveaux champs propages depuis ApiPeintureController
                * via window.peintureData (cf. peinture.js + enregistrement.js).
                * Tous en `?? null` pour rester retro-compatible si le
                * payload JS n'envoie pas le champ (anciennes versions du client). */
                'alert_status' => $data->alert_status ?? null,
                'statements' => $data->statements ?? null,
                /* MODIF 2026-05-06 : ApiPeintureController pre-parse ncloc_language_distribution
                * en array (pour l'affichage des langages dans la modale Ajouter).
                * Mais la colonne historique attend un string format SonarQube "java=12345;js=3000". On reconstitue. */
                'ncloc_language_distribution' => self::serializeLanguageDistribution($data->ncloc_language_distribution ?? null),
                /* Commentaires */
                'comment_lines' => $data->comment_lines ?? null,
                'comment_lines_density' => $data->comment_lines_density ?? null,
                'comment_lines_rating' => $data->comment_lines_rating ?? null,
                /* Couverture détaillée */
                'branch_coverage' => $data->branch_coverage ?? null,
                'line_coverage' => $data->line_coverage ?? null,
                'lines_to_cover' => $data->lines_to_cover ?? null,
                'conditions_to_cover' => $data->conditions_to_cover ?? null,
                'uncovered_conditions' => $data->uncovered_conditions ?? null,
                'coverage_rating' => $data->coverage_rating ?? null,
                /* Tests détaillés */
                'test_execution_time' => $data->test_execution_time ?? null,
                'test_errors' => $data->test_errors ?? null,
                'test_failures' => $data->test_failures ?? null,
                'skipped_tests' => $data->skipped_tests ?? null,
                'test_success_density' => $data->test_success_density ?? null,
                /* Duplication détaillée */
                'duplicated_files' => $data->duplicated_files ?? null,
                'duplicated_blocks' => $data->duplicated_blocks ?? null,
                'duplicated_lines' => $data->duplicated_lines ?? null,
                'duplicated_lines_rating' => $data->duplicated_lines_rating ?? null,
                /* Complexité */
                'complexity' => $data->complexity ?? null,
                'complexity_rating' => $data->complexity_rating ?? null,
                'complexity_ratio' => $data->complexity_ratio ?? null,
                'cognitive_complexity' => $data->cognitive_complexity ?? null,
                'cognitive_complexity_rating' => $data->cognitive_complexity_rating ?? null,
                'cognitive_complexity_ratio' => $data->cognitive_complexity_ratio ?? null,
                /* Issues lifecycle */
                'open_issues' => $data->open_issues ?? null,
                'reopened_issues' => $data->reopened_issues ?? null,
                'confirmed_issues' => $data->confirmed_issues ?? null,
                'false_positive_issues' => $data->false_positive_issues ?? null,
                'accepted_issues' => $data->accepted_issues ?? null,
                'high_impact_accepted_issues' => $data->high_impact_accepted_issues ?? null,
                /* Sonar 10 issues par sévérité */
                'software_quality_blocker_issues' => $data->software_quality_blocker_issues ?? null,
                'software_quality_high_issues' => $data->software_quality_high_issues ?? null,
                'software_quality_medium_issues' => $data->software_quality_medium_issues ?? null,
                'software_quality_low_issues' => $data->software_quality_low_issues ?? null,
                'software_quality_info_issues' => $data->software_quality_info_issues ?? null,
                /* Maintenabilité */
                'maintainability_issues' => $data->maintainability_issues ?? null,
                'effort_to_reach_maintainability_rating_a' => $data->effort_to_reach_maintainability_rating_a ?? null,
                'software_quality_maintainability_issues' => $data->software_quality_maintainability_issues ?? null,
                'software_quality_maintainability_rating' => $data->software_quality_maintainability_rating ?? null,
                'software_quality_maintainability_debt_ratio' => $data->software_quality_maintainability_debt_ratio ?? null,
                'software_quality_maintainability_remediation_effort' => $data->software_quality_maintainability_remediation_effort ?? null,
                'effort_to_reach_software_quality_maintainability_rating_a' => $data->effort_to_reach_software_quality_maintainability_rating_a ?? null,
                /* Fiabilité */
                'reliability_issues' => $data->reliability_issues ?? null,
                'reliability_remediation_effort' => $data->reliability_remediation_effort ?? null,
                'software_quality_reliability_issues' => $data->software_quality_reliability_issues ?? null,
                'software_quality_reliability_rating' => $data->software_quality_reliability_rating ?? null,
                'software_quality_reliability_remediation_effort' => $data->software_quality_reliability_remediation_effort ?? null,
                /* Sécurité */
                'security_issues' => $data->security_issues ?? null,
                'security_remediation_effort' => $data->security_remediation_effort ?? null,
                'software_quality_security_issues' => $data->software_quality_security_issues ?? null,
                'software_quality_security_rating' => $data->software_quality_security_rating ?? null,
                'software_quality_security_remediation_effort' => $data->software_quality_security_remediation_effort ?? null,
                /* Hotspots */
                'security_hotspots' => $data->security_hotspots ?? null,
                'security_hotspots_reviewed' => $data->security_hotspots_reviewed ?? null,
                /* MODIF 2026-05-06 : nouveaux champs propagés depuis ApiPeintureController via window.peintureData
                 * Counts NoSonar et To.do par langage,  `suppress_warning` est deja mappé (legacy total) — on le laisse pour compatibilité, le payload peinture l’écrasera. */
                'java_no_sonar' => $data->java_no_sonar ?? null,
                'python_no_sonar' => $data->python_no_sonar ?? null,
                'php_no_sonar' => $data->php_no_sonar ?? null,
                'no_pmd' => $data->no_pmd ?? null,
                'check_style' => $data->check_style ?? null,
                'java_todo' => $data->java_todo ?? null,
                'python_todo' => $data->python_todo ?? null,
                'php_todo' => $data->php_todo ?? null,
                'xml_todo' => $data->xml_todo ?? null,
                'web_todo' => $data->web_todo ?? null,
                'javascript_todo' => $data->javascript_todo ?? null,
                'typescript_todo' => $data->typescript_todo ?? null,
                'ruby_todo' => $data->ruby_todo ?? null,
                /* MODIF 2026-05-17 : indicateurs SonarQube 10+
                 * lus depuis clean_code (null si pas encore collectés). */
                'cc_consistent' => isset($cc['cc_consistent']) ? (int) $cc['cc_consistent'] : null,
                'cc_intentional' => isset($cc['cc_intentional']) ? (int) $cc['cc_intentional'] : null,
                'cc_adaptable' => isset($cc['cc_adaptable']) ? (int) $cc['cc_adaptable'] : null,
                'cc_responsible' => isset($cc['cc_responsible']) ? (int) $cc['cc_responsible'] : null,
                'quality_maintainability' => isset($cc['quality_maintainability']) ? (int) $cc['quality_maintainability'] : null,
                'quality_reliability' => isset($cc['quality_reliability']) ? (int) $cc['quality_reliability'] : null,
                'quality_security' => isset($cc['quality_security']) ? (int) $cc['quality_security'] : null,
                'impact_blocker' => isset($cc['impact_blocker']) ? (int) $cc['impact_blocker'] : null,
                'impact_high' => isset($cc['impact_high']) ? (int) $cc['impact_high'] : null,
                'impact_medium' => isset($cc['impact_medium']) ? (int) $cc['impact_medium'] : null,
                'impact_low' => isset($cc['impact_low']) ? (int) $cc['impact_low'] : null,
                'impact_info' => isset($cc['impact_info']) ? (int) $cc['impact_info'] : null,
                'owasp_top10' => isset($cc['owasp_top10']) ? (int) $cc['owasp_top10'] : null,
                'sans_top25' => isset($cc['sans_top25']) ? (int) $cc['sans_top25'] : null,
                'cwe' => isset($cc['cwe']) ? (int) $cc['cwe'] : null,
                'mode_collecte' => 'COLLECTE',
                'utilisateur_collecte' => $utilisateur_collecte,
                'date_enregistrement' => $dateEnregistrement
            ];

            /** Normalise les valeurs string 'null' / '' / null en vrai null PHP pour
             *  les champs numériques (Postgres refuse 'null' string sur INT/FLOAT).
             *  Les champs string conservés tels quels via la whitelist $stringKeys. */
            $stringKeys = [
                'maven_key',
                'analyse_key',
                'version',
                'date_version',
                'project_name',
                'mode_collecte',
                'utilisateur_collecte',
                'date_enregistrement'
            ];
            foreach ($map as $k => $v) {
                if (in_array($k, $stringKeys, true)) {
                    continue;
                }
                if ($v === null || $v === '' || $v === 'null') {
                    $map[$k] = null;
                }
            }

            /* MODIF 2026-05-15 : propagation du breakdown logger (level × framework) vers historique.logger_breakdown.
             * Le payload JS contient `logger_breakdown` (cf data.breakdown
             * retourné par /api/secure/collecte/logger). Null si plugin v1.x
             * ou collecte non lancée. */
            $loggerBreakdown = null;
            if (property_exists($data, 'logger_breakdown') && is_object($data->logger_breakdown)) {
                $loggerBreakdown = json_decode(json_encode($data->logger_breakdown), true);
            } elseif (property_exists($data, 'logger_breakdown') && is_array($data->logger_breakdown)) {
                $loggerBreakdown = $data->logger_breakdown;
            // MODIF 2026-05-26 : else terminal requis par S126 (logger_breakdown absent ou null = plugin v1.x).
            } else {
                $this->logger->debug('[Enregistrement] 🔍 logger_breakdown absent ou null (plugin v1.x ou collecte non lancée).');
            }

            /** Enregistrement dans le table historique */
            $historique = $historiqueRepos->insertHistoriqueAjoutProjet($map, $json, $loggerBreakdown);
            if ($historique['code'] != 200 && $historique['code'] != 23505) {
                $this->logger->error("[Enregistrement] ❌ Échec de la requête insertHistoriqueAjoutProjet.", [
                    'code' => $historique['code'],
                    'erreur' => $historique['erreur'] ?? "aucun message d'erreur remonté"
                ]);

                return new JsonResponse([
                    'code' => $historique['code'],
                    'type' => 'error',
                    'message' => "Une erreur lors de l'ajout de données est survenue (Erreur {$historique['code']}).",
                    'trace' => $historique['erreur']
                ], Response::HTTP_OK);
            }

            if ($historique['code'] === 23505) {
                $this->logger->info("[Enregistrement] ❌ détection de doublon.", [
                    'code' => $historique['code'],
                    'erreur' => $historique['erreur'],
                    'payload' => $map
                ]);

                return new JsonResponse([
                    'code' => $historique['code'],
                    'erreur' => $historique['erreur']
                ], Response::HTTP_OK);
            }
        } catch (\Throwable $e) {
            $this->logger->critical("[Enregistrement] 🔴 Erreur lors de l'enregistrement des données.", ['exception' => $e]);
            return new JsonResponse([
                'code' => 500,
                'type' => 'critical',
                'message' => "Erreur globale lors de l'enregistrement des données.",
                'trace' => $e->getMessage()
            ], Response::HTTP_OK);
        }

        /** Tout va bien ! */
        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

    /**
     * [Description for serializeLanguageDistribution]
     * MODIF 2026-05-06 : Reserialise ncloc_language_distribution au format string SonarQube
     * "java=12345;js=3000" pour insertion dans la colonne historique (TEXT).
     *
     * Cas d'entrée :
     *   - string deja au format SonarQube       -> retourne tel quel (idempotent)
     *   - object/array (parse cote ApiPeinture) -> reserialise
     *   - null / vide                           -> null
     *
     * @param mixed $value
     *
     * @return string|null
     *
     * Created at: 12/07/2026 11:11:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function serializeLanguageDistribution(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }
        // Deja une string au format attendu : retourne tel quel.
        if (is_string($value)) {
            return $value;
        }
        // Objet stdClass -> tableau associatif.
        if (is_object($value)) {
            $value = (array) $value;
        }
        if (!is_array($value) || $value === []) {
            return null;
        }
        // Reconstruction "code1=val1;code2=val2"
        $parts = [];
        foreach ($value as $code => $lines) {
            if (is_string($code) && (is_int($lines) || (is_string($lines) && ctype_digit($lines)))) {
                $parts[] = $code . '=' . (int) $lines;
            }
        }
        return $parts === [] ? null : implode(';', $parts);
    }
}
