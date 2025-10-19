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

use App\Entity\Logger;
use App\Service\Client;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteLoggerController]
 */
class BatchCollecteLoggerController extends AbstractController
{
    /** Définition des constantes */
    private static $sonarUrl = "sonar.url";
    private static $trackLoggerMethod = 'track-logger-method:';

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
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for makeRequest]
     * Fonction générique pour executer une requête et retourner le résultat dans un tableau
     *
     * @param array $queryParams
     *
     * @return array
     *
     * Created at: 12/08/2024 11:24:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function makeRequest(array $queryParams): array
    {
         /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/project_analyses/search',
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

        return ['total' => $result['total']] ?? ['total' => -1];
    }

    /**
     * [Description for BatchCollecteLogger]
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array
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
        if ((boolean)$loggerPlugin === false || $loggerPlugin === 'false' || $loggerPlugin === 'False'){
            $this->logger->info("[Batch Logger] ⚠️ Collecte non lancée : plugin désactivé (TRACK_LOGGER_METHOD=false)", [
                'maven_key' => $maven_key
            ]);

            return [
                    'code' => 404,
                    'message' => "La collecte des LOGGERS n'a pas été lancée. (TRACK_LOGGER_METHOD=false).",
                    'historique' => ''
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
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => static::$trackLoggerMethod.$method[0], 'ps' => 500],
            $method[1] => [ 'componentKeys' => $maven_key,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => static::$trackLoggerMethod.$method[1], 'ps' => 500],
            $method[2] => [ 'componentKeys' => $maven_key,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => static::$trackLoggerMethod.$method[2], 'ps' => 500],
            $method[3] => [ 'componentKeys' => $maven_key,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => static::$trackLoggerMethod.$method[3], 'ps' => 500]
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

        /** Log de succès */
        $this->logger->info("[Batch Logger] ℹ️ Collecte réussie", [
            'maven_key' => $maven_key,
            'info' => $loggerData['logger_info'],
            'warn' => $loggerData['logger_warn'],
            'error' => $loggerData['logger_error'],
            'debug' => $loggerData['logger_debug']
        ]);

        /** Données à retourner */
        $historique = [
            'maven_key' => $maven_key,
            'logger_info' => $results['track-info-method'],
            'logger_warn' => $results['track-warn-method'],
            'logger_error' => $results['track-error-method'],
            'logger_debug' => $results['track-debug-method'],
        ];

        return [
            'code' => 200,
            'message' => 'La collecte des Logger Java pour le projet est terminée.',
            'historique' => $historique
        ];

    }
}
