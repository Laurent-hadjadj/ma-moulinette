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
use App\Entity\Mesures;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteMesureController]
 */
class BatchCollecteMesureController extends AbstractController
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
     * [Description for BatchCollecteMesure]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 21/05/2024 23:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteMesure(string $mavenKey, string $modeCollecte, string $utilisateurCollecte): array
    {
        /** On instancie l'EntityRepository */
        $mesuresRepository = $this->em->getRepository(Mesures::class);

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /** Construit l'URL en utilisant http_build_query pour les paramètres de la requête */
        $queryParams = [ 'component' => $mavenKey ];
        $queryString = http_build_query($queryParams);

        /** Appelle le client HTTP */
        $result = $this->client->http("$tempoUrl/api/components/app?$queryString");
        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            return ['code' => $result['code'], 'error'=>[$result['erreur']]];
        }

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $delete=$mesuresRepository->deleteMesuresMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'], 'error'=>[$delete['erreur'], static::$request=>'deleteMesureMavenKey']];
        }
        /** Création de la date du jour */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone("Europe/Paris"));

        /** Initialisation des mesures avec des valeurs par défaut */
        $lines = intval($result['measures']['lines'] ?? 0);
        $coverage = $result['measures']['coverage'] ?? 0;
        $duplicationDensity = $result['measures']['duplicationDensity'] ?? 0;
        $tests = intval($result['measures']['tests'] ?? 0);
        $issues = intval($result['measures']['issues'] ?? 0);

        /** Appelle le client HTTP */
        $queryParams = [
        'component' => $mavenKey,
        'metricKeys' => 'ncloc'
        ];
        $queryString = http_build_query($queryParams);

        /** Appelle le client HTTP */
        $result2 = $this->client->http("$tempoUrl/api/measures/component?$queryString");


        /** Initialise ncloc avec une valeur par défaut */
        $ncloc = intval($result2['component']['measures'][0]['value'] ?? 0);

        /** On récupère le ratio de dette technique */
        $queryParams = [
            'component' => $mavenKey,
            'metricKeys' => 'sqale_debt_ratio'
            ];
            $queryString = http_build_query($queryParams);
        /** Appelle le client HTTP */
        $result3 = $this->client->http("$tempoUrl/api/measures/component?$queryString");

        $sqaleRatio = intval($result3['component']['measures'][0]['value'] ?? -1);

         /** On enregistre les données */
        $mesureData = [
            'maven_key' => $mavenKey,
            'project_name' => $result['projectName'],
            'lines' => $lines,
            'ncloc' => $ncloc,
            'sqale_debt_ratio' => $sqaleRatio,
            'coverage' => $coverage,
            'duplication_density' => $duplicationDensity,
            'tests' => $tests,
            'issues' => $issues,
            'mode_collecte' => $modeCollecte,
            'utilisateur_collecte' => $utilisateurCollecte,
            'date_enregistrement' => $date
        ];
        $insert=$mesuresRepository->insertMesures($mesureData);
        if ($insert['code'] !== 200) {
            return ['code' => $insert['code'],
                    'error'=>[$insert['erreur'],
                    static::$request => 'insertMesures']];
        }

        return ['code' => 200, 'message' => $mesureData];
    }
}
