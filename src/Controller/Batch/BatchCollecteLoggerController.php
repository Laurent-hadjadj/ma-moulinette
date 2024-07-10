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
    public static $request = "requête : ";

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

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /* Fonction générique pour executer une requête et retourner le résultat dans un tableau */
        $makeRequest = function($queryParams) use ($tempoUrl) {
            $queryString = http_build_query($queryParams);
            /** Appelle le client HTTP */
            $result = $this->client->http("$tempoUrl/api/issues/search?$queryString");
            if (isset($result['code']) && in_array($result['code'], [401, 404])) {
                return ['error' => $result['code'], $result['erreur']];
            }
            return $result['total'] ?? -1;
        };

        /* Liste des différents Logger */
        $methods = [ 'track-info-method', 'track-warn-method', 'track-error-method', 'track-debug-method' ];

        /* On appelle les API en passant les querryParams à la fonction générique */
        $results = [];
        foreach ($methods as $method) {
            $queryParams = [ 'componentKeys' => $mavenKey,
            'facets'  => 'rules',
            'statuses' => 'OPEN',
            'rules' => 'track-logger-method:'.$method,
            'ps' => 500,
            ];
            $results[$method] = $makeRequest($queryParams);

            if (isset($results[$method]['error'])) {
                return ['code' => $results[$method]['error'], 'error'=>$results['erreur']];
            }
        }

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $delete=$loggerRepository->deleteLoggerMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'], 'error'=>[$delete['erreur'], static::$request=>'deleteLoggerMavenKey']];
        }
        /** Création de la date du jour */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone("Europe/Paris"));

         /** On enregistre les données */
        $loggerData = [
            'maven_key' => $mavenKey,
            'logger_info' => $results['track-info-method'],
            'logger_warn' => $results['track-warn-method'],
            'logger_error' => $results['track-error-method'],
            'logger_debug' => $results['track-debug-method'],
            'mode_collecte' => $modeCollecte,
            'utilisateur_collecte' => $utilisateurCollecte,
            'date_enregistrement' => $date
        ];

        $insert=$loggerRepository->insertLogger($loggerData);
        if ($insert['code'] !== 200) {
            return ['code' => $insert['code'],
                    'error'=>[$insert['erreur'],
                    static::$request => 'insertLogger']];
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
