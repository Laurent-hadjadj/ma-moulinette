<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
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

use App\Entity\Anomalie;
use App\Service\ExtractName;
use App\Service\DateTools;
use App\Service\Client;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteAnomalieController]
 */
class BatchCollecteAnomalieController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $statuses = "OPEN,REOPENED";
    public static $statusesMin = "OPEN,CONFIRMED,REOPENED,RESOLVED";
    public static $statusesAll = "OPEN, CONFIRMED, REOPENED, RESOLVED, CLOSED";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private Client $client,
        private ExtractName $serviceExtractName,
        private DateTools $serviceDateTools,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for makeRequest]
     * Fonction générique pour executer une requête et retourner le résultat dans un tableau
     *
     * @param array $queryParams
     * @param string $tempoUrl
     *
     * @return array
     *
     * Created at: 09/08/2024 07:52:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function makeRequest(array $queryParams): array
        {
            /** On renvoi un tableau avec le résultat de la requête ou un tableau avec un code erreur. */
            /** Sécurisation de l'URL */
            $url = $this->urlBuilder->build(
                $this->getParameter(static::$sonarUrl),
                '/api/issues/search',
                $queryParams
            );

            $result = $this->client->httpSonarQube($url);
            if (isset($result['code']) && in_array($result['code'], [401, 403, 404, 500, 503])) {
                return [
                    'code' => $result['code'],
                    'erreur' => $result['erreur']
                ];
            }
            return $result['json'] ?? [];
        }

    /**
     * [Description for BatchCollecteAnomalie]
     *
     * @param string $mavenKey
     * @param string $modeCollecte
     * @param string $utilisateurCollecte
     *
     * @return array
     *
     * Created at: 21/05/2024 23:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteAnomalie(string $maven_key, string $modeCollecte, string $utilisateurCollecte): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $anomalieRepos = $this->em->getRepository(Anomalie::class);

        $this->logger->info('ℹ️ [Batch Anomalie] Début de collecte', [
            'maven_key' => $maven_key,
            'utilisateur' => $utilisateurCollecte
        ]);

        /** On créé un objet date. */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /** On récupère le nom du projet */
        $app = $this->serviceExtractName->extractNameFromMavenKey($maven_key);

        /* Tableau des paramètres pour les requêtes HTTP */
        $queryParamsList = [
            'general' => [ 'componentKeys' => $maven_key, 'facets' => 'directories,types,severities',
                'p' => 1, 'ps' => 1, 'statuses' => static::$statuses ],
            'BUG' => [ 'componentKeys' => $maven_key, 'types' => 'BUG', 'p' => 1, 'ps' => 1 ],
            'VULNERABILITY' => [ 'componentKeys' => $maven_key, 'types' => 'VULNERABILITY',
                'p' => 1, 'ps' => 1 ],
            'CODE_SMELL' => [ 'componentKeys' => $maven_key, 'types' => 'CODE_SMELL',
                'p' => 1, 'ps' => 1 ]
        ];

        /**
         * On appelle les API en passant les queryParams à la fonction générique.
         * Si la méthode renvoi une clé 'code' alors il y a une erreur sinon on a une clé 'json'
         */
        $results = [];
        $results['general'] = self::makeRequest($queryParamsList['general']);
        if (isset($results['general']['code'])) {
            $this->logger->error('❌ [Batch Anomalie] Erreur API "general"', ['erreur' => $results['general']]);
                return [
                    'code' => $results['general']['code'],
                    'erreur' => $results['general']['erreur'],
                    'type' => 'general'
                ];
            }

        // Appels BUG, VULNERABILITY, CODE_SMELL
        foreach (['BUG', 'VULNERABILITY', 'CODE_SMELL'] as $type) {
            $results[$type] = $this->makeRequest($queryParamsList[$type]);
            if (isset($results[$type]['code'])) {
                $this->logger->error("❌ [Batch Anomalie] Erreur API \"$type\"", ['erreur' => $results[$type]]);
                return [
                    'code' => $results[$type]['code'],
                    'erreur' => $results[$type]['erreur'],
                    'type' => $type
                ];
            }
        }

        if ($results['general']['paging']['total'] != 0) {
            /** On supprime les résultats pour la maven_key. */
            $map = [ 'maven_key' => $maven_key ];
            $this->logger->info('🧹 [batch Anomalie] Suppression des anomalies précédentes');
            $delete = $anomalieRepos->deleteAnomalieMavenKey($map);
            if ($delete['code'] != 200) {
                return [
                    'code' => $delete['code'],
                    'erreur' => $delete['erreur']
                ];
            }

            //** On récupère le nombre d'anomalie et la dette technique */
            $anomalieTotal = $results['general']['total'];
            $dette = $this->serviceDateTools->minutesTo($results['general']['effortTotal']);
            $detteMinute = $results['general']['effortTotal'];
            $detteReliability = $this->serviceDateTools->minutesTo($results['BUG']['effortTotal']);
            $detteReliabilityMinute = $results['BUG']['effortTotal'];
            $detteVulnerability = $this->serviceDateTools->minutesTo($results['VULNERABILITY']['effortTotal']);
            $detteVulnerabilityMinute = $results['VULNERABILITY']['effortTotal'];
            $detteCodeSmell = $this->serviceDateTools->minutesTo($results['CODE_SMELL']['effortTotal']);
            $detteCodeSmellMinute = $results['CODE_SMELL']['effortTotal'];

            /* On initialise les indicateurs de sévérité, de type et de répartition */
            $severities = ['BLOCKER' => 0, 'CRITICAL' => 0, 'MAJOR' => 0, 'INFO' => 0, 'MINOR' => 0];
            $types = ['BUG' => 0, 'VULNERABILITY' => 0, 'CODE_SMELL' => 0];
            $modules = ['frontend' => 0, 'backend' => 0, 'autre' => 0, 'inconnue' => 0];

            /** On récupère les informations */
            foreach ($results['general']['facets'] as $facet) {
                switch ($facet['property']) {
                    case 'severities':
                        foreach ($facet['values'] as $severity) {
                            $severities[$severity['val']] = $severity['count'];
                        }
                        break;
                    case 'types':
                        foreach ($facet['values'] as $type) {
                            $types[$type['val']] = $type['count'];
                        }
                        break;
                    case 'directories':
                        foreach ($facet['values'] as $directory) {
                            $file = str_replace($maven_key . ':', "", $directory['val']);
                            $module = explode('/', $file)[0];
                            $count = $directory['count'];
                            if (in_array($module, ['du-presentation', 'rs-presentation', "$app-presentation", "$app-presentation-commun", "$app-presentation-ear", "$app-webapp"])) {
                                $modules['frontend'] += ($module === "$app-presentation" || $module === "$app-presentation-commun" || $module === "$app-presentation-ear" || $module === "$app-webapp") ? 1 : $count;
                            } elseif (in_array($module, ['rs-metier', "$app-metier", "$app-common", "$app-api", "$app-dao", "$app-metier-ear", "$app-service", "$app-serviceweb", "$app-middleoffice", "$app-metier-rest", "$app-entite", "$app-serviceweb-client"])) {
                                $modules['backend'] += $count;
                            } elseif (in_array($module, ["$app-batch", "$app-batchs", "$app-batch-envoi-dem-aval", "$app-batch-import-billets", "$app-rdd"])) {
                                $modules['autre'] += $count;
                            } else { $modules['inconnue'] += $count; }
                        }
                        break;
                    default:
                        break;
                    break;
                }
            }
        }

        /** Enregistrement dans la table Anomalie. */
        $map = [
            'maven_key' => $maven_key,
            'project_name' => $app,
            'anomalie_total' => $anomalieTotal ?? 0,
            'dette' => $dette ?? 0,
            'dette_minute' => $detteMinute ?? 0,
            'dette_reliability' => $detteReliability ?? 0,
            'dette_reliability_minute' => $detteReliabilityMinute ?? 0,
            'dette_vulnerability' => $detteVulnerability ?? 0,
            'dette_vulnerability_minute' => $detteVulnerabilityMinute ?? 0,
            'dette_code_smell' => $detteCodeSmell ?? 0,
            'dette_code_smell_minute' => $detteCodeSmellMinute ?? 0,
            'frontend' => $modules['frontend'] ?? 0,
            'backend' => $modules['backend'] ?? 0,
            'autre' => $modules['autre'] ?? 0,
            'inconnue' => $modules['inconnue'] ?? 0,
            'blocker' => $severities['BLOCKER'] ?? 0,
            'critical' => $severities['CRITICAL'] ?? 0,
            'major' => $severities['MAJOR'] ?? 0,
            'info' => $severities['INFO'] ?? 0,
            'minor' => $severities['MINOR'] ?? 0,
            'bug' => $types['BUG'] ?? 0,
            'vulnerability' => $types['VULNERABILITY'] ?? 0,
            'code_smell' => $types['CODE_SMELL'] ?? 0,
            'mode_collecte' => $modeCollecte,
            'utilisateur_collecte' => $utilisateurCollecte,
            'date_enregistrement' => $date
        ];

        $insert = $anomalieRepos->insertAnomalie($map);
        if ($insert['code'] !== 200) {
            $this->logger->error('❌ [Batch Anomalie] Échec d’insertion en base', ['erreur' => $insert['erreur']]);
            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        /** On prépare les données pour l'historique */
        $data = [
                    'violations' => $anomalieTotal ?? 0,
                    'dette' => $detteMinute ?? 0,
                    'nombre_bug' => $types['BUG'] ?? 0,
                    'nombre_vulnerability' => $types['VULNERABILITY'] ?? 0,
                    'nombre_code_smell' => $types['CODE_SMELL'] ?? 0,
                    'frontend' => $modules['frontend'] ?? 0,
                    'backend' => $modules['backend'] ?? 0,
                    'autre' => $modules['autre'] ?? 0,
                    'inconnue' => $modules['inconnue'] ?? 0,
                    'nombre_anomalie_bloquant' => $severities['BLOCKER'] ?? 0,
                    'nombre_anomalie_critique' =>$severities['CRITICAL'] ?? 0,
                    'nombre_anomalie_info' =>$severities['INFO'] ?? 0,
                    'nombre_anomalie_majeur' => $severities['MAJOR'] ?? 0,
                    'nombre_anomalie_mineur'=>$severities['MINOR'] ?? 0
                ];

        $this->logger->info('[Collecte Anomalie] Collecte réussie', [
            'total' => $anomalieTotal ?? 0,
            'frontend' => $modules['frontend'] ?? 0,
            'backend' => $modules['backend'] ?? 0
        ]);

        $total = $anomalieTotal ?? 0;
        $info = "Nombre d'anomalie : $total";
        return [
            'code' => 200,
            'info' => $info,
            'message' => $map,
            'data' => $data
        ];
    }

}
