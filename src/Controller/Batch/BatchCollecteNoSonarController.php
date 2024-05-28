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
 * [Description BatchCollecteInformationProjetController]
 */
class BatchCollecteNoSonarController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
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
    ) {
        $this->em = $em;
    }


    /**
     * [Description for BatchCollecteNoSonar]
     *
     * @param Client $client
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 21/05/2024 22:25:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteNoSonar(Client $client, $mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $noSonarEntity = $this->em->getRepository(NoSonar::class);

        /** On construit l'URL et on appel le WS */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $url = "$tempoUrl/api/issues/search?componentKeys=$mavenKey
            &rules=java:S1309,java:NoSonar&ps=500&p=1";

        $result = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));

        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            return ['code' => $result['code']];
        }

        /** On crée un objet date */
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On supprime les notes pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $request=$noSonarEntity->deleteNoSonarMavenKey($map);
        if ($request['code']!=200) {
            return ['code' => $request['code'], 'methode'=>'deleteNoSonarMavenKey'];
        }

        /**
         * Si on a trouvé des @notations de type noSonar ou suppressWarning.
         * dans le code alors on les dénombre
         */

        $noSonar = $suppressWarning = 0;
        if ($result["paging"]["total"] !== 0) {
            foreach ($result["issues"] as $issue) {
                if ($issue["rule"] === "java:S1309") {
                    $suppressWarning++;
                } elseif ($issue["rule"] === "java:NoSonar") {
                    $noSonar++;
                }
                $component = str_replace("$mavenKey :", "", $issue["component"]);
                $line = empty($issue["line"]) ? 0 : $issue["line"];

                /** On créé la map */
                $map = [
                    'maven_key' => $mavenKey,
                    'rule' => $issue["rule"],
                    'component' => $component,
                    'line' => $line,
                    'date_enregistrement' => $date
                ];
                /* On enregistre */
                $request=$noSonarEntity->insertNoSonar($map);
                if ($request['code']!=200) {
                    return ['code' => $request['code'], 'methode'=>'insertNoSonar'];
                }
            }
        } else {
            /** Il n'y a pas de noSOnar ou de suppressWarning */
        }

        /** On enregistre les données */
        $nosonar = ["suppress_warning" => $suppressWarning, "no_sonar" => $noSonar];
        return ['code' => 200, "nosonar" => $nosonar];
    }
}
