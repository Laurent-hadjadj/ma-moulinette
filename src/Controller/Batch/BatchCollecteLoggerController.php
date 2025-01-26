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

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Logger;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteLoggerController]
 */
class BatchCollecteLoggerController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $trackLoggerMethod = 'track-logger-method:';

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
    ) {
        $this->em = $em;
        $this->client = $client;
    }

    /**
     * [Description for makeRequest]
     *
     * @param array $queryParams
     * @param string $tempoUrl
     *
     * @return array
     *
     * Created at: 12/08/2024 11:24:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function makeRequest(array $queryParams, string $tempoUrl): array
    {
        /* Fonction générique pour executer une requête et retourner le résultat dans un tableau */
        $queryString = http_build_query($queryParams);
        /** Appelle le client HTTP */
        $result = $this->client->httpSonarQube("$tempoUrl/api/issues/search?$queryString");
        if (isset($result['code']) && in_array($result['code'], [401, 403, 404, 500])) {
            return ['code' => $result['code'], 'erreur' => $result['erreur']];
        }
        if (isset($result['code']) && $result['code'] != 200) {
            return ['code' => $result['code'], 'erreur' => $result['erreur']];
        }
        return ['total' => $result['total']] ?? ['total' => -1];
    }

    /**
     * [Description for BatchCollecteLogger]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 10/07/2024 22:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteLogger(string $mavenKey, string $modeCollecte, string $utilisateurCollecte): array
    {
        /** On instancie l'EntityRepository */
        $loggerRepository = $this->em->getRepository(Logger::class);

        /** On regarde si le plugin Track-Logger-Method est activé */
        $loggerPlugin = $this->getParameter('track.logger.method');
        if ((boolean)$loggerPlugin === false || $loggerPlugin === 'false' || $loggerPlugin === 'False'){
            return ['code' => 404, 'message' => "La collecte des LOGGERS n'a pas été lancée. (TRACK_LOGGER_METHOD=false).", 'data' => ''];
        }

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /* Liste des différents Logger */
        $method = [ 'track-info-method', 'track-warn-method', 'track-error-method', 'track-debug-method' ];
        $queryParams = [
            $method[0] => [ 'componentKeys' => $mavenKey,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => static::$trackLoggerMethod.$method[0], 'ps' => 500],
            $method[1] => [ 'componentKeys' => $mavenKey,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => static::$trackLoggerMethod.$method[1], 'ps' => 500],
            $method[2] => [ 'componentKeys' => $mavenKey,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => static::$trackLoggerMethod.$method[2], 'ps' => 500],
            $method[3] => [ 'componentKeys' => $mavenKey,
            'facets'  => 'rules', 'statuses' => 'OPEN', 'rules' => static::$trackLoggerMethod.$method[3], 'ps' => 500]
        ];

        /* On appelle les API en passant les QueryParams à la fonction générique */
        $results = [];
        $results['track-info-method'] = self::makeRequest($queryParams['track-info-method'], $tempoUrl);
        if (isset($results['track-info-method']['code']) && $results['track-info-method']['code'] != 200) {
                return ['code' => $results['track-info-method']['code'],
                        'erreur' => $results['track-info-method']['erreur'],
                        'tracker' => 'track-info-method'];
            }
        $results['track-warn-method'] = self::makeRequest($queryParams['track-warn-method'], $tempoUrl);
        if (isset($results['track-warn-method']['code']) && $results['track-warn-method']['code'] != 200) {
                return ['code' => $results['track-warn-method']['code'],
                        'erreur' => $results['track-warn-method']['erreur'],
                        'tracker' => 'track-warn-method'];
            }
        $results['track-error-method'] = self::makeRequest($queryParams['track-error-method'], $tempoUrl);
        if (isset($results['track-error-method']['erreur']) && $results['track-error-method']['code'] != 200) {
                return ['code' => $results['track-error-method']['code'],
                        'erreur' => $results['track-error-method']['erreur'],
                        'tracker' => 'track-error-method'];
            }
        $results['track-debug-method'] = self::makeRequest($queryParams['track-debug-method'], $tempoUrl);
        if (isset($results['track-debug-method']['erreur']) && $results['track-debug-method']['code'] != 200) {
                return ['code' => $results['track-debug-method']['code'],
                        'erreur' => $results['track-debug-method']['erreur'],
                        'tracker' => 'track-debug-method'];
            }

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $delete=$loggerRepository->deleteLoggerMavenKey($map);
        if ($delete['code'] != 200) {
            return [
                    'code' => $delete['code'],
                    'erreur' => $delete['erreur']
                ];
        }

        /** Création de la date du jour */
        $date = new \DateTimeImmutable('now', new \DateTimeZone("Europe/Paris"));

         /** On enregistre les données */
        $loggerData = [
            'maven_key' => $mavenKey,
            'logger_info' => $results['track-info-method']['total'],
            'logger_warn' => $results['track-warn-method']['total'],
            'logger_error' => $results['track-error-method']['total'],
            'logger_debug' => $results['track-debug-method']['total'],
            'mode_collecte' => $modeCollecte,
            'utilisateur_collecte' => $utilisateurCollecte,
            'date_enregistrement' => $date
        ];

        $insert=$loggerRepository->insertLogger($loggerData);
        if ($insert['code'] !== 200) {
            return [
                    'code' => $insert['code'],
                    'erreur' => $insert['erreur']
                ];
        }

        /** On prépare les données pour l'historique */
        $data=[
            'maven_key' => $mavenKey,
            'logger_info' => $results['track-info-method'],
            'logger_warn' => $results['track-warn-method'],
            'logger_error' => $results['track-error-method'],
            'logger_debug' => $results['track-debug-method'],
        ];

        return ['code' => 200, 'message' => $loggerData, 'data' => $data];
    }

}
