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

namespace App\Controller\Batch;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{InformationProjet, Logger, LoggerDetail};
use App\Service\{ClientService, UrlBuilderService};

/**
 * [Description BatchCollecteLoggerController]
 */
class BatchCollecteLoggerController extends AbstractController
{
    /** Définition des constantes */
    private static string $sonarUrl = "sonar.url";
    private static string $trackLoggerMethod = 'track-logger-method:';

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for makeRequest]
     * Fonction générique pour executer une requête et retourner le résultat dans un tableau
     *
     * @param array<int|string, mixed> $queryParams
     *
     * @return array<int|string, mixed>
     *
     * Created at: 12/08/2024 11:24:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function makeRequest(array $queryParams): array
    {
        /** Sécurisation de l'URL
          * MODIF 2026-05-15 : endpoint corrigé.
          * `/api/project_analyses/search` ne supporte PAS les paramètres
          * `statuses=OPEN` + `rules=...` (qui appartiennent à
          * `/api/issues/search`). En Sonar 9, l'API était permissive et
          * silencieusement tolérait ces paramètres ignorés. En Sonar 10+
          * la validation est stricte et retourne 400 :
          * "Value of parameter 'statuses' (OPEN) must be one of: [P, U, L]".
          * Le bon endpoint pour compter les issues d'une règle est
          * `/api/issues/search` qui existe en Sonar 7+ et reste compatible
          * 9 / 10 / 11+. */
        $url = $this->urlBuilder->build(
            $this->getParameter(self::$sonarUrl),
            '/api/issues/search',
            $queryParams
        );

        $this->logger->debug('[Batch Logger] 🛠️ Appel API SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Batch Logger] ❌ Erreur SonarQube', [
                'url' => $url,
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
            ]);

            return [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
            ];
        }

        /* MODIF 2026-05-15 : on remonte aussi le tableau
         * des issues (jusqu'a `ps=500`). En plus du `total` pour la table
         * `logger` aggregate, ces issues serviront a peupler `logger_details`
         * (1 ligne par occurrence, drill-down par fichier + framework). */
        return [
            'total'  => $result['json']['paging']['total'] ?? -1,
            'issues' => $result['json']['issues'] ?? [],
        ];
    }

    /**
     * MODIF 2026-05-15 : parse une issue Sonar en row
     * `logger_details`. Retourne null si la row est invalide (sans level
     * ou sans file_path qui sont NOT NULL en BDD).
     *
     * Défensif (compat plugin v1.x) : framework et line_number peuvent être
     * null sans rejeter la row.
     *
     * @param array<string, mixed> $issue
     * @return array<string, mixed>|null
     */
    private static function parseIssueToDetail(
        array $issue,
        string $mavenKey,
        ?string $projectVersion,
        string $modeCollecte,
        string $utilisateurCollecte,
        \DateTimeImmutable $date,
    ): ?array {
        // level depuis `rule` : "track-logger-method:track-<level>-method"
        $rule = (string) ($issue['rule'] ?? '');
        if (preg_match('/track-(\w+)-method$/', $rule, $m) !== 1) {
            return null; // rule malformee, on skip
        }
        $level = strtolower($m[1]);

        // framework depuis le `message` (plugin v2+). Pattern : "...[<framework>]"
        // En v1, le message n'a pas de [...] -> framework = null (insertion quand meme).
        $framework = null;
        $message = (string) ($issue['message'] ?? '');
        if (preg_match('/\[([^\]]+)\]\s*$/', $message, $m) === 1) {
            $framework = trim($m[1]);
        }

        // file_path depuis `component` : "<group>:<artifact>:<chemin/dans/repo>"
        // On prend tout apres les 2 premiers ':' (le chemin peut en contenir).
        $component = (string) ($issue['component'] ?? '');
        $parts = explode(':', $component, 3);
        if (count($parts) !== 3 || $parts[2] === '') {
            return null; // pas de file_path identifiable, on skip (NOT NULL)
        }
        $filePath  = $parts[2];
        $fileName  = basename($filePath);
        $className = pathinfo($fileName, PATHINFO_FILENAME); // ex: ApplicationExceptionHandler.java -> ApplicationExceptionHandler

        $lineNumber    = isset($issue['line']) ? (int) $issue['line'] : null;
        $sonarIssueKey = isset($issue['key']) ? (string) $issue['key'] : null;

        return [
            'maven_key'            => $mavenKey,
            'project_version'      => $projectVersion,
            'level'                => $level,
            'framework'            => $framework,
            'file_path'            => $filePath,
            'file_name'            => $fileName,
            'class_name'           => $className,
            'line_number'          => $lineNumber,
            'sonar_issue_key'      => $sonarIssueKey,
            'mode_collecte'        => $modeCollecte,
            'utilisateur_collecte' => $utilisateurCollecte,
            'date_enregistrement'  => $date,
        ];
    }

    /**
     * [Description for BatchCollecteLogger]
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array<int|string, mixed>
     *
     * Created at: 10/07/2024 22:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteLogger(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $loggerRepos = $this->em->getRepository(Logger::class);

        $this->logger->info('[Batch Logger] ℹ️ Début de collecte pour les logger Java', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** On regarde si le plugin Track-Logger-Method est activé */
        $loggerPlugin = $this->getParameter('track.logger.method');
        if ((bool)$loggerPlugin === false || $loggerPlugin === 'false' || $loggerPlugin === 'False'){
            $this->logger->info("[Batch Logger] ⚠️ Collecte non lancée : plugin désactivé (TRACK_LOGGER_METHOD=false)", [
                'maven_key' => $maven_key
            ]);

            return [
                    'code' => 404,
                    'message' => "La collecte des LOGGERS n'a pas été lancée. (TRACK_LOGGER_METHOD=false).",
                    'historique' => [
                        'logger_info' => 0,
                        'logger_warn' => 0,
                        'logger_error' => 0,
                        'logger_debug' => 0,
                    ]
                ];
        }

        /* Liste des différents Logger */
        $method = [
                    'track-info-method',
                    'track-warn-method',
                    'track-error-method',
                    'track-debug-method'
                ];
        $queryParams = [
            $method[0] => [ 'componentKeys' => $maven_key,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => self::$trackLoggerMethod.$method[0], 'ps' => 500],
            $method[1] => [ 'componentKeys' => $maven_key,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => self::$trackLoggerMethod.$method[1], 'ps' => 500],
            $method[2] => [ 'componentKeys' => $maven_key,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => self::$trackLoggerMethod.$method[2], 'ps' => 500],
            $method[3] => [ 'componentKeys' => $maven_key,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => self::$trackLoggerMethod.$method[3], 'ps' => 500]
        ];

        /** Appels API et vérification des retours */
        $results = [];
        foreach ($method as $tracker) {
            $this->logger->debug("[Batch Logger] 🛠️ Appel API Sonar : {$tracker}", [
                'params' => $queryParams[$tracker]]);
            $results[$tracker] = self::makeRequest($queryParams[$tracker]);

            if (isset($results[$tracker]['code']) && $results[$tracker]['code'] !== 200) {
                $this->logger->error("[Batch Logger] ❌ Erreur API pour {$tracker}", [
                    'code' => $results[$tracker]['code'],
                    'erreur' => $results[$tracker]['erreur'] ?? 'Erreur inconnue'
                ]);

                return [
                    'code' => $results[$tracker]['code'],
                    'erreur' => $results[$tracker]['erreur'],
                    'tracker' => $tracker
                ];
            }
        }

        /** Suppression des anciens enregistrements */
        $map = ['maven_key' => $maven_key];
        $delete = $loggerRepos->deleteLoggerMavenKey($map);
        if ($delete['code'] !== 200) {
            $this->logger->error("[Batch Logger] ❌ Échec de la requête deleteLoggerMavenKey", [
                'maven_key' => $maven_key,
                'erreur' => $delete['erreur']
            ]);

            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

         /** Enregistrement en base */
        $date = new \DateTimeImmutable('now', new \DateTimeZone("Europe/Paris"));
        $loggerData = [
            'maven_key' => $maven_key,
            'logger_info' => $results['track-info-method']['total'] ?? 0,
            'logger_warn' => $results['track-warn-method']['total'] ?? 0,
            'logger_error' => $results['track-error-method']['total'] ?? 0,
            'logger_debug' => $results['track-debug-method']['total'] ?? 0,
            'mode_collecte' => $mode_collecte,
            'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $date
        ];

        $insert = $loggerRepos->insertLogger($loggerData);

        if ($insert['code'] !== 200) {
            $this->logger->error("[Batch Logger] ❌ Échec de la requête insertLogger", [
                'maven_key' => $maven_key,
                'erreur' => $insert['erreur']
            ]);

            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        /* MODIF 2026-05-15 : peuplement de la table
         * `logger_details` pour le drill-down par fichier + framework.
         * 1 ligne par occurrence parsée depuis les `issues` retournées
         * par chaque appel (makeRequest renvoie maintenant aussi 'issues').
         * Compat plugin v1.x : framework et line_number peuvent être null,
         * la row est insérée quand meme (filtre défensif uniquement sur
         * level + file_path qui sont NOT NULL en BDD).
         *
         * Stratégie DELETE + batch INSERT (1 SQL pour N rows). Non bloquant :
         * si l'insertion échoue, on log mais on ne fait pas planter la
         * collecte (la table `logger` aggregate est deja a jour). */
        $detailsRepos = $this->em->getRepository(LoggerDetail::class);
        $delete = $detailsRepos->deleteLoggerDetailMavenKey(['maven_key' => $maven_key]);
        if ($delete['code'] !== 200) {
            $this->logger->warning("[Batch Logger] ⚠️ Échec deleteLoggerDetailMavenKey, drill-down potentiellement obsolete", [
                'maven_key' => $maven_key,
                'erreur' => $delete['erreur'],
            ]);
        } else {
            /* MODIF 2026-05-15 : recuperation de la version
             * courante depuis information_projet pour la stocker dans chaque
             * row logger_details (utile pour le drill-down "loggers de la
             * version X"). En cas d'échec/absence on garde null, ce qui
             * reste valide (colonne nullable). */
            $projectVersion = null;
            $informationProjetRepos = $this->em->getRepository(InformationProjet::class);
            $versionResult = $informationProjetRepos->selectInformationProjetVersion(['maven_key' => $maven_key]);
            // MODIF 2026-06-10 — fetchAssociative() retourne un tableau plat, pas [0]
            if (($versionResult['code'] ?? 500) === 200 && !empty($versionResult['info']['version'])) {
                $projectVersion = (string) $versionResult['info']['version'];
            }

            $detailRows = [];
            foreach ($method as $tracker) {
                $issues = $results[$tracker]['issues'] ?? [];
                foreach ($issues as $issue) {
                    if (!is_array($issue)) { continue; }
                    $row = self::parseIssueToDetail($issue, $maven_key, $projectVersion, $mode_collecte, $utilisateur_collecte, $date);
                    if ($row !== null) {
                        $detailRows[] = $row;
                    }
                }
            }

            $insertDetails = $detailsRepos->insertLoggerDetailBatch($detailRows);
            if ($insertDetails['code'] !== 200) {
                $this->logger->warning("[Batch Logger] ⚠️ Echec insertLoggerDetailBatch, drill-down indisponible", [
                    'maven_key' => $maven_key,
                    'nb_rows'   => count($detailRows),
                    'erreur'    => $insertDetails['erreur'],
                ]);
            } else {
                $this->logger->info("[Batch Logger] ℹ️ logger_details peuples", [
                    'maven_key' => $maven_key,
                    'nb_rows'   => $insertDetails['nombre'] ?? count($detailRows),
                ]);
            }
        }

        /** Log de succès */
        $this->logger->info("[Batch Logger] ℹ️ Collecte réussie", [
            'maven_key' => $maven_key,
            'info' => $loggerData['logger_info'],
            'warn' => $loggerData['logger_warn'],
            'error' => $loggerData['logger_error'],
            'debug' => $loggerData['logger_debug']
        ]);

        /* MODIF 2026-05-15 : breakdown level × framework
         * exposé au front. Forme : { info: { SLF4J: N, Commons Logging: M, "—": K },
         * warn: {...}, error: {...}, debug: {...} }. Les frameworks null
         * (plugin v1.x non parsable) sont consolidés sous la clé "—" pour
         * conserver le compte sans clé string-null en JSON. Si aucun
         * logger_details n'a été inséré (échec ou aucune occurrence), le
         * breakdown reste vide. */
        $breakdown = ['info' => [], 'warn' => [], 'error' => [], 'debug' => []];
        /* MODIF 2026-05-20 : $detailsRepos toujours défini (getRepository non-null). */
        $breakdownResult = $detailsRepos->countByMavenKeyGroupedByLevelAndFramework($maven_key);
        if ($breakdownResult['code'] === 200) {
            foreach ($breakdownResult['liste'] ?? [] as $row) {
                $level     = (string) $row['level'];
                $framework = $row['framework'] !== null ? (string) $row['framework'] : '—';
                $nb        = (int) $row['nb'];
                if (!isset($breakdown[$level])) { $breakdown[$level] = []; }
                $breakdown[$level][$framework] = $nb;
            }
        }

        /** Données à retourner */
        $historique = [
            'maven_key' => $maven_key,
            'logger_info' => $loggerData['logger_info'],
            'logger_warn' => $loggerData['logger_warn'],
            'logger_error' => $loggerData['logger_error'],
            'logger_debug' => $loggerData['logger_debug'],
            // MODIF 2026-05-15 : breakdown par level × framework
            'breakdown' => $breakdown,
        ];

        return [
            'code' => 200,
            'message' => 'La collecte des Logger Java pour le projet est terminée.',
            'historique' => $historique
        ];

    }
}
