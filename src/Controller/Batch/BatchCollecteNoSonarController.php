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
use App\Entity\NoSonar;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteNoSonarController]
 */
class BatchCollecteNoSonarController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
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
        private Client $client
    ) {
        $this->em = $em;
        $this->client = $client;
    }

    /**
     * [Description for BatchCollecteNoSonar]
     *
     * @param string $mavenKey
     * @param string $modeCollecte
     * @param string $utilisateurCollecte
     *
     * @return array
     *
     * Created at: 21/05/2024 22:25:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteNoSonar(string $mavenKey, string $modeCollecte, string $utilisateurCollecte): array
    {
        /** On instancie l'EntityRepository */
        $noSonarRepository = $this->em->getRepository(NoSonar::class);

        /** On créé un objet date. */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /** Appelle le client HTTP */
        $queryParams = ['componentKeys'=>$mavenKey, 'rules'=> 'java:S1309,java:NoSonar', 'p'=>1, 'ps'=>500 ];
        $result = $this->client->http("$tempoUrl/api/issues/search?".http_build_query($queryParams));
         /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            return ['code' => $result['code'], 'error'=>[$result['erreur']]];
        }

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $delete=$noSonarRepository->deleteNoSonarMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'],
                    'error'=>[$delete['erreur'],static::$request=>'deleteNoSonarMavenKey']
                ];
        }

        /**
         * Si on a trouvé des @notations de type noSonar ou suppressWarning.
         * dans le code alors on les dénombre
         */

        $noSonar = $suppressWarning = 0;
        $mapData=[];
        if ($result["paging"]["total"] !== 0) {
            foreach ($result["issues"] as $issue) {
                switch ($issue["rule"]) {
                    case "java:S1309":
                        $suppressWarning++;
                        break;
                    case "java:NoSonar":
                        $noSonar++;
                        break;
                    default: break;
                }
                $component = str_replace("$mavenKey :", "", $issue["component"]);
                $line = empty($issue["line"]) ? 0 : $issue["line"];

                /** On créé la map */
                $mapData[] = [
                    'maven_key' => $mavenKey,
                    'rule' => $issue["rule"],
                    'component' => $component,
                    'line' => $line,
                    'mode_collecte' => $modeCollecte,
                    'utilisateur_collecte' => $utilisateurCollecte,
                    'date_enregistrement' => $date
                ];
            }
        } else {
            /** Il n'y a pas de noSOnar ou de suppressWarning */
        }

        /* On enregistre */
        $request=$noSonarRepository->insertNoSonar($mapData);
        if ($request['code']!=200) {
            return ['code' => $request['code'],
            'error'=>[$request['erreur'],static::$request=>'insertNoSonar']];
        }

        /** On prépare les données pour l'historique */
        $data = ['suppress_warning' => $suppressWarning, 'no_sonar' => $noSonar];

        return ['code' => 200, 'message' => $data, 'data'=>$data];
    }
}
