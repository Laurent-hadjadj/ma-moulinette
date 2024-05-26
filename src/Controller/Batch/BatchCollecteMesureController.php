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
 * [Description BatchCollecteInformationProjetController]
 */
class BatchCollecteMesureController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $removeReturnline = "/\s+/u";

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
    public function BatchCollecteMesure(string $mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $mesuresRepository = $this->em->getRepository(Mesures::class);

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $url1 = "$tempoUrl/api/components/app?component=$mavenKey";

       /** Appelle le client HTTP */
        $result1 = $this->client->http(trim(preg_replace(static::$removeReturnline, " ", $url1)));
        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            return ['code' => $result['code']];
        }

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $request=$mesuresRepository->deleteMesuresMavenKey($map);
        if ($request['code']!=200) {
            return ['code' => $request['code'], 'requête'=>'deleteMesureMavenKey'];
        }
        /** Création de la date du jour */
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone("Europe/Paris"));

        /** Initialisation des mesures avec des valeurs par défaut */
        $lines = intval($result1['measures']['lines'] ?? 0);
        $coverage = $result1['measures']['coverage'] ?? 0;
        $duplicationDensity = $result1['measures']['duplicationDensity'] ?? 0;
        $tests = intval($result1['measures']['tests'] ?? 0);
        $issues = intval($result1['measures']['issues'] ?? 0);

        /** Appelle le client HTTP */
        $url2 = "$tempoUrl/api/measures/component?component=$mavenKey&metricKeys=ncloc";
        $result2 = $this->client->http($url2);

        /** Initialise ncloc avec une valeur par défaut */
        $ncloc = intval($result2['component']['measures'][0]['value'] ?? 0);

        /** On récupère le ratio de dette technique */
        $url3 = "$tempoUrl/api/measures/component?component=$mavenKey&metricKeys=sqale_debt_ratio";
        $result3 = $this->client->http(preg_replace(static::$removeReturnline, " ", $url3));
        $sqaleRatio = intval($result3['component']['measures'][0]['value'] ?? -1);

         /** On enregistre les données */
        $mesureData = [
            'maven_key' => $mavenKey,
            'project_name' => $result1['projectName'],
            'lines' => $lines,
            'ncloc' => $ncloc,
            'sqale_debt_ratio' => $sqaleRatio,
            'coverage' => $coverage,
            'duplication_density' => $duplicationDensity,
            'tests' => $tests,
            'issues' => $issues,
            'date_enregistrement' => $date
        ];
        $insert=$mesuresRepository->insertMesures($mesureData);
        if ($insert['code'] !== 200) {
            return [
                'code' => $insert['code'],
                'requête' => 'insertMesures'
            ];
        }

        return ['code' => 200, 'mesure' => $mesureData];
    }
}
