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
    private static $sonarUrl = "sonar.url";
    private static $europeParis = "Europe/Paris";
    private static $statuses = "OPEN,REOPENED";
    //'private static $statusesMin = "OPEN,CONFIRMED,REOPENED,RESOLVED";
    //'private static $statusesAll = "OPEN, CONFIRMED, REOPENED, RESOLVED, CLOSED";
    private static $beginRegEx = '/\b(?:';
    private static $endRegEx = ')\b/i';

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
     * [Description for batchAnalyseAnomalie]
     * Calcule la répartition des anomalies par module
     *
     * @param mixed $facet
     * @param mixed $maven_key
     *
     * @return array
     *
     * Created at: 28/07/2025 15:45:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function batchAnalyseAnomalie($facet, $maven_key): array
    {
        /** On récupère le nombre total d'anomalie par chemin,donc il suffit d'identifier la nature du chemin pour connaître le nombre d'anomalie par module. */

        $this->logger->info('[Batch Anomalie] ℹ️ Début de l’analyse des répertoires', [
            'nombre_directory' => count($facet['values'])
        ]);

        /** On calcule la répartition pour les application java et ?Php */
        $scoreFrontend = $scoreBackend = $scoreAutre = $scoreInconnu = 0;

        /** on récupère la liste des clés pour chaque module */
        $listeFrontend = $this->getParameter('module.frontend');
        $listeBackend = $this->getParameter('module.backend');
        $listeAutre = $this->getParameter('module.autre');

        $frontendKeywords = array_map('strtolower', array_map('trim', explode(',', $listeFrontend)));
        $backendKeywords = array_map('strtolower', array_map('trim', explode(',', $listeBackend)));
        $autreKeywords = array_map('strtolower', array_map('trim', explode(',', $listeAutre)));

        // On prépare les regex pour une vérification rapide
        // Utilisation de preg_quote pour échapper d'éventuels caractères spéciaux dans les mots-clés
        $regexFrontend = static::$beginRegEx . implode('|', array_map('preg_quote', $frontendKeywords)) . static::$endRegEx;
        $regexBackend  = static::$beginRegEx . implode('|', array_map('preg_quote', $backendKeywords)) . static::$endRegEx;
        $regexAutre  = static::$beginRegEx . implode('|', array_map('preg_quote', $autreKeywords)) . static::$endRegEx;

        try {
            foreach ($facet['values'] as $directory) {
                $path = str_replace($maven_key . ':', '', $directory['val']);
                $count = $directory['count'];

                // On vérifie si le chemin correspond à un module frontend
                if (preg_match($regexFrontend, $path)) {
                    $scoreFrontend += $count;
                }
                // Sinon, on vérifie s'il correspond à un module backend
                elseif (preg_match($regexBackend, $path)) {
                    $scoreBackend += $count;
                } // Sinon, on vérifie s'il correspond à un module autre
                elseif (preg_match($regexAutre, $path)) {
                    $scoreAutre += $count;
                } // Si aucune des regex ne matche, c'est considéré comme "inconnu"
                else {
                    $scoreInconnu += $count;
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical("[Batch Anomalie] 🔴 Exception lors de l'analyse du chemin", [
                'exception' => $e->getMessage()]);

            return [
                'code' => 500,
                'type' => 'critical',
                'message' => "Une erreur inétendue lors de l'analyse du chemin (<strong>batchAnalyseAnomalie</strong>) est survenue (Erreur 500).",
            ];
        }

        $this->logger->info('[Batch Anomalie] ℹ️ Analyse du chemin.', [
            'frontend' => $scoreFrontend,
            'backend' => $scoreBackend,
            'autre' => $scoreAutre,
            'inconnu' => $scoreInconnu
        ]);

        return [
                'code' => 200,
                'frontend' => $scoreFrontend,
                'backend' => $scoreBackend,
                'autre' => $scoreAutre,
                'inconnu' => $scoreInconnu
            ];
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
            /** Sécurisation de l'URL */
            $url = $this->urlBuilder->build(
                $this->getParameter(static::$sonarUrl),
                '/api/issues/search',
                $queryParams
            );

            $this->logger->debug("[Batch Anomalie] 🛠️ Appel API SonarQube", ['url' => $url]);
            $result = $this->client->httpSonarQube($url);

            if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
                $this->logger->error("[Batch Anomalie] ❌ Erreur API SonarQube", [
                    'url' => $url,
                    'code' => $result['code'],
                    'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
                ]);

                return [
                    'code' => $result['code'],
                    'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
                ];
            }

            return $result['json'] ?? [];
        }

    /**
     * [Description for BatchCollecteAnomalie]
     *
     * @param string $mavenKey
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array
     *
     * Created at: 21/05/2024 23:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteAnomalie(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $anomalieRepos = $this->em->getRepository(Anomalie::class);

        $this->logger->info('[Batch Anomalie] ℹ️ Début de la collecte des anomalies.', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
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
            $this->logger->error("[Batch Anomalie] ❌ Erreur API [general]", ['erreur' => $results['general']]);
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
                $this->logger->error("[Batch Anomalie] ❌  Erreur API [$type]", ['erreur' => $results[$type]]);
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

            $this->logger->info('[batch Anomalie] 🧹 Suppression des anomalies précédentes');
            $delete = $anomalieRepos->deleteAnomalieMavenKey($map);

            if ($delete['code'] != 200) {
                $this->logger->error('[Batch Anomalie] ❌ Échec suppression anomalies précédentes', [
                    'maven_key' => $maven_key,
                    'erreur' => $delete['erreur']
                ]);

                return [
                    'code' => $delete['code'],
                    'erreur' => $delete['erreur']
                ];
            }

            /** On récupère le nombre d'anomalie et la dette technique */
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
                        $analyse = $this->batchAnalyseAnomalie($facet, $maven_key);
                        break;
                    default:
                        $this->logger->critical("[Batch Anomalie] 🔴 On ne devrait pas arriver dans le default du switch.");
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
            'frontend' => $analyse['frontend'] ?? 0,
            'backend' => $analyse['backend'] ?? 0,
            'autre' => $analyse['autre'] ?? 0,
            'inconnu' => $analyse['inconnu'] ?? 0,
            'blocker' => $severities['BLOCKER'] ?? 0,
            'critical' => $severities['CRITICAL'] ?? 0,
            'major' => $severities['MAJOR'] ?? 0,
            'info' => $severities['INFO'] ?? 0,
            'minor' => $severities['MINOR'] ?? 0,
            'bug' => $types['BUG'] ?? 0,
            'vulnerability' => $types['VULNERABILITY'] ?? 0,
            'code_smell' => $types['CODE_SMELL'] ?? 0,
            'mode_collecte' => $mode_collecte,
            'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $date
        ];

        $insert = $anomalieRepos->insertAnomalie($map);
        if ($insert['code'] !== 200) {
            $this->logger->error('[Batch Anomalie] ❌ Échec d’insertion en base', ['erreur' => $insert['erreur']]);
            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        /** On prépare les données pour l'historique */
        $historique = [
                    'violations' => $anomalieTotal ?? 0,
                    'dette' => $detteMinute ?? 0,
                    'nombre_bug' => $types['BUG'] ?? 0,
                    'nombre_vulnerability' => $types['VULNERABILITY'] ?? 0,
                    'nombre_code_smell' => $types['CODE_SMELL'] ?? 0,
                    'frontend' => $analyse['frontend'] ?? 0,
                    'backend' => $analyse['backend'] ?? 0,
                    'autre' => $analyse['autre'] ?? 0,
                    'inconnu' => $analyse['inconnu'] ?? 0,
                    'nombre_anomalie_bloquant' => $severities['BLOCKER'] ?? 0,
                    'nombre_anomalie_critique' =>$severities['CRITICAL'] ?? 0,
                    'nombre_anomalie_info' =>$severities['INFO'] ?? 0,
                    'nombre_anomalie_majeur' => $severities['MAJOR'] ?? 0,
                    'nombre_anomalie_mineur'=>$severities['MINOR'] ?? 0
                ];

        $this->logger->info('[Batch Anomalie] ℹ️ Collecte des anomalies réussie', [
            'maven_key' => $maven_key,
            'violations' => $anomalieTotal ?? 0,
            'frontend' => $analyse['frontend'] ?? 0,
            'backend' => $analyse['backend'] ?? 0,
            'autre' => $analyse['autre'] ?? 0,
            'inconnu' => $analyse['inconnu'] ?? 0,
            'utilisateur' => $utilisateur_collecte
        ]);

        $total = $anomalieTotal ?? 0;
        $info = "Nombre d'anomalie : $total";
        return [
            'code' => 200,
            'info' => $info,
            'message' => "La collecte des anomalies pour ce projet est terminé.",
            'data' => $map,
            'historique' => $historique
        ];
    }
}
