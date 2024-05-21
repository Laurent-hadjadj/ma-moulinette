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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Logger */
use Psr\Log\LoggerInterface;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\NoSonar;

/** Client HTTP */
use App\Service\Client;
use App\Service\IsValideMavenKey;

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
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private IsValideMavenKey $isValidMavenKey,
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->isValidMavenKey = $isValidMavenKey;
    }

    public function getIssuesByMavenKey($mavenKey, $sonarKey) {
        $noSonar = $suppressWarning = 0;
        $result = $this->entityManager->getRepository('Application\Entity\InformationProjet')->findOneByMavenKeyAndSonarKey($mavenKey, $sonarKey);

        if ($result) {
            foreach ($result["issues"] as $issue) {
                $nosonar = new NoSonar();
                $nosonar->setMavenKey($mavenKey);
                $nosonar->setRule($issue["rule"]);

                if ($issue["rule"] === "java:S1309") {
                    $suppressWarning++;
                }
                if ($issue["rule"] !== "java:NoSonar" && $issue["rule"] !== "NoSonar") {
                    $noSonar++;
                }

                $component = str_replace("$mavenKey :", "", $issue["component"]);
                $nosonar->setComponent($component);
                $line = (empty($issue["line"]) ? 0 : $issue["line"]);

                /** On enregistre la ligne */
                if ($line === 0) {
                    // Do nothing, line is zero
                } else {
                    // Store the line number in the database
                    $nosonar->setLine($line);
                }
                $noSonar++;
            }
        }
    }

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
        if (array_key_exists('code', $result)){
            if ($result['code']===401) {
                return ['code' => 401];
            }
            if ($result['code']===404){
                return ['code' => 404];
            }
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
                $nosonar = new NoSonar();
                $nosonar->setMavenKey($mavenKey);

                $nosonar->setRule($issue["rule"]);
                if ($issue["rule"] === "java:S1309") {
                    $suppressWarning++;
                }
                if ($issue["rule"] === "java:NoSonar") {
                    $noSonar++;
                }

                $component = str_replace("$mavenKey :", "", $issue["component"]);
                $nosonar->setComponent($component);

                /** On récupère la ligne */
                if (empty($issue["line"])) {
                    $line = 0;
                } else {
                    $line = $issue["line"];
                }

                $nosonar->setLine($line);
                $nosonar->setDateEnregistrement($date);
                $this->em->persist($nosonar);
                $this->em->flush();
            }
        } else {
            /** Il n'y a pas de noSOnar ou de suppressWarning */
        }

        /** On enregistre les données */
        $nosonar = ["suppress_warning" => $suppressWarning, "no_sonar" => $noSonar];

        return ["nosonar" => $nosonar];
    }

    public function batchCollecteInformation(Client $client, $mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $informationProjetEntity = $this->em->getRepository(InformationProjet::class);

        /** On forge la requête */
        $url = $this->getParameter(static::$sonarUrl) .
                "/api/project_analyses/search?project=" . $mavenKey;

        /** On appel le client http */
        $result = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));

        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (array_key_exists('code', $result)){
            if ($result['code']===401) {
                return ['code' => 401];
            }
            if ($result['code']===404){
                return ['code' => 404];
            }
        }

        /** On créé un objet date */
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On supprime les informations pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $request=$informationProjetEntity->deleteInformationProjetMavenKey($map);
        if ($request['code']!=200) {
            return ['code' => $request['code'], 'methode'=>'deleteInformationProjetMavenKey'];
        }

        /** On ajoute les informations du projet dans la table information_projet. */
        foreach ($result['analyses'] as $information) {
            /**
             *  La version du projet doit être xxx-release, xxx-snapshot ou xxx
             *  Dans ce cas, le tableau renvoi toujours [0] pour la version et
             *  [1] pour le type de version (release, snaphot ou null)
             */
            $explode = explode('-', $information['projectVersion']);
            if (empty($explode[1])) {
                $explode[1] = 'N.C';
            }
            $map=['maven_key'=>$mavenKey, 'Analyse_key'=>$information['key'],
                'date'=>new \DateTime($information['date']),
                'project_version', $information['projectVersion'],
                'type'=>strtoupper($explode[1]),
                'date_enregisytement'=>$date];
        }
        $update=$informationProjetEntity->updateInformationProjet($map);
        if ($update['code']!=200) {
            return ['code' => $update['code'], 'methode'=>'deleteInformationProjetMavenKey'];
        }

        /** On appel la méthode de traitement des données */
        $versions = $this->batchInformationVersion($request, $mavenKey);
        return [ 'code'=>200, 'information' => $versions ];
    }

}
