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

use App\Entity\AnomalieDetails;
use App\Service\ExtractName;
use App\Service\ClientService;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteAnomalieDetailController]
 */
class BatchCollecteAnomalieDetailController extends AbstractController
{
    /** Définition des constantes */
    private static $sonarUrl = "sonar.url";
    private static $europeParis = "Europe/Paris";

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
        private ExtractName $serviceExtractName,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for makeRequest]
     * On renvoi un tableau avec le résultat de la requête ou un tableau avec un code erreur.
     *
     * @param array $queryParams
     * @param string $tempoUrl
     *
     * @return array
     *
     * Created at: 25/01/2025 21:27:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function makeRequest(array $queryParams): array
    {
        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/issues/search',
            $queryParams
        );

        $this->logger->debug("[Batch AnomalieDétail] 🛠️ Appel API SonarQube", ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error("[Batch AnomalieDétail] ❌ Erreur API SonarQube.", [
                'url' => $url,
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
            ]);

            return [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
            ];
        }

        $this->logger->debug("[Batch AnomalieDétail] 🛠️ Résultat JSON SonarQube reçu", [
            'queryParams' => $queryParams,
            'result' => $result['json'] ?? null
        ]);

        return $result['json'] ?? [];
    }

    /**
     * [Description for mapSeverities]
     *
     * @param array $severities
     *
     * @return array
     *
     * Created at: 26/01/2025 00:29:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function mapSeverities(array $severities): array
    {
        // Initialisation du tableau de sévérités avec des valeurs par défaut
        $severityMap = [
            'BLOCKER' => 0,
            'CRITICAL' => 0,
            'MAJOR' => 0,
            'MINOR' => 0,
            'INFO' => 0
        ];
        if (isset($severities['values'])) {
            foreach ($severities['values'] as $severity) {
                $severityMap[$severity['val']] = $severity['count'];
            }
        }

        $this->logger->debug("[Batch AnomalieDétail] 🛠️ Mapping severities effectué", $severityMap);
        return $severityMap;
    }

    /**
     * [Description for BatchCollecteAnomalieDetail]
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array
     *
     * Created at: 03/06/2024 14:14:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteAnomalieDetail(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $anomalieDetailsRepos = $this->em->getRepository(AnomalieDetails::class);
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        $this->logger->info('[Batch AnomalieDétail] ℹ️ Début de collecte anomalies', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        /* Tableau des paramètres pour les requêtes HTTP */
        $queryParamsList = [
            'BUG' => [ 'componentKeys' => $maven_key, 'facets' => 'severities', 'types' => 'BUG', 'statuses' => 'OPEN', 'p' => 1, 'ps' => 1 ],
            'VULNERABILITY' => [ 'componentKeys' => $maven_key, 'facets' => 'severities', 'types' => 'VULNERABILITY', 'statuses' => 'OPEN', 'p' => 1, 'ps' => 1 ],
            'CODE_SMELL' => [ 'componentKeys' => $maven_key, 'facets' => 'severities', 'types' => 'CODE_SMELL', 'statuses' => 'OPEN', 'p' => 1, 'ps' => 1 ]
        ];

        /* On appelle les API en passant les querryParams à la fonction générique */
        $results = [];
        foreach ($queryParamsList as $key => $queryParams) {
            $results[$key] = self::makeRequest($queryParams);
            if (isset($results[$key]['code'])) {
                $this->logger->error("[Batch AnomalieDétail] ❌ Erreur pour le type {$key}", ['key' => $results[$key]]);
                return [
                    'code' => $results[$key]['code'],
                    'erreur' => $results[$key]['erreur'],
                    'type' => $key
                ];
            }
        }
        $issueTypes = [ 'BUG', 'VULNERABILITY', 'CODE_SMELL' ];
        $pagingTotals = [];

        /** On map pour chaque type les tableau de résultats qui nous intéresse */
        foreach ($issueTypes as $type) {
            if (isset($results[$type]['paging']['total'])) {
                $pagingTotals[$type] = $results[$type]['paging']['total'];
            }
        }

        /** Si la somme du total des anomalies est égale à zéro, on sort */
        if (array_sum($pagingTotals) === 0) {
            $this->logger->info("[Batch AnomalieDétail] ℹ️ Aucune anomalie détectée", ['maven_key' => $maven_key]);
            return [
                'code' => 200,
                'message' => "Pas d'anomalie trouvée.",
                'historique' => []
            ];
        }

        /** On supprime l'enregistrement correspondant à la clé */
        $map = [ 'maven_key' => $maven_key ];
        $delete = $anomalieDetailsRepos->deleteAnomalieDetailsMavenKey($map);

        if ($delete['code'] != 200) {
            $this->logger->error("[Batch AnomalieDétail] ❌ Échec suppression ancienne donnée", $delete);
            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        /** On bind les résultats */
        $bugSeverities = self::mapSeverities($results['BUG']['facets'][0]);
        $vulnerabilitySeverities = self::mapSeverities($results['VULNERABILITY']['facets'][0]);
        $codeSmellSeverities = self::mapSeverities($results['CODE_SMELL']['facets'][0]);

        /** On prépare les données */
        $mapData = [
                    'maven_key' => $maven_key,
                    'name' =>$this->serviceExtractName->extractNameFromMavenKey($maven_key),
                    'bug_blocker' => $bugSeverities['BLOCKER'],
                    'bug_critical' => $bugSeverities['CRITICAL'],
                    'bug_major' => $bugSeverities['MAJOR'],
                    'bug_minor' => $bugSeverities['MINOR'],
                    'bug_info' => $bugSeverities['INFO'],

                    'vulnerability_blocker' => $vulnerabilitySeverities['BLOCKER'],
                    'vulnerability_critical' => $vulnerabilitySeverities['CRITICAL'],
                    'vulnerability_major' => $vulnerabilitySeverities['MAJOR'],
                    'vulnerability_minor' => $vulnerabilitySeverities['MINOR'],
                    'vulnerability_info' => $vulnerabilitySeverities['INFO'],

                    'code_smell_blocker' => $codeSmellSeverities['BLOCKER'],
                    'code_smell_critical' => $codeSmellSeverities['CRITICAL'],
                    'code_smell_major' => $codeSmellSeverities['MAJOR'],
                    'code_smell_minor' => $codeSmellSeverities['MINOR'],
                    'code_smell_info' => $codeSmellSeverities['INFO'],

                    'mode_collecte' => $mode_collecte,
                    'utilisateur_collecte' => $utilisateur_collecte,
                    'date_enregistrement' => $date
            ];

            $insert = $anomalieDetailsRepos->insertAnomalieDetail($mapData);
            if ($insert['code'] !== 200) {
                $this->logger->error("[Batch AnomalieDétail] ❌ Échec de la requête insertAnomalieDetail.", [
                    'code' => $insert['code'],
                    'erreur' => $insert['erreur']
                ]);

                return [
                    'code' => $insert['code'],
                    'erreur' => $insert['erreur']
                ];
            }

            $historique = [
                    'bug_blocker' => $bugSeverities['BLOCKER'],
                    'bug_critical' => $bugSeverities['CRITICAL'],
                    'bug_major' => $bugSeverities['MAJOR'],
                    'bug_minor' => $bugSeverities['MINOR'],
                    'bug_info' => $bugSeverities['INFO'],
                    'vulnerability_blocker' => $vulnerabilitySeverities['BLOCKER'],
                    'vulnerability_critical' => $vulnerabilitySeverities['CRITICAL'],
                    'vulnerability_major' => $vulnerabilitySeverities['MAJOR'],
                    'vulnerability_minor' => $vulnerabilitySeverities['MINOR'],
                    'vulnerability_info' => $vulnerabilitySeverities['INFO'],
                    'code_smell_blocker' => $codeSmellSeverities['BLOCKER'],
                    'code_smell_critical' => $codeSmellSeverities['CRITICAL'],
                    'code_smell_major' => $codeSmellSeverities['MAJOR'],
                    'code_smell_minor' => $codeSmellSeverities['MINOR'],
                    'code_smell_info' => $codeSmellSeverities['INFO']
            ];

        $this->logger->info("[Batch AnomalieDétail] ℹ️ Collecte et insertion terminées.", [
            'maven_key' => $maven_key,
            'data' => $mapData
        ]);

        return [
            'code' => 200,
            'message' => "Collecte du détail des anomalies terminée.",
            'historique' => $historique //avant 'data'
        ];
    }
}
