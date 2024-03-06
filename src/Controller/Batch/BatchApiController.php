<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2022.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Batch;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/** Logger */
use Psr\Log\LoggerInterface;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Accès aux tables SLQLite*/
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Main\InformationProjet;
use App\Entity\Main\NoSonar;
use App\Entity\Main\Mesures;
use App\Entity\Main\Anomalie;
use App\Entity\Main\AnomalieDetails;
use App\Entity\Main\Hotspots;
use App\Entity\Main\HotspotOwasp;
use App\Entity\Main\Historique;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchController]
 */
class BatchApiController extends AbstractController
{
    /** Définition des constantes */
    public static $strContentType = 'application/json';
    public static $sonarUrl = "sonar.url";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatMini = "Y-m-d";
    public static $europeParis = "Europe/Paris";
    public static $apiIssuesSearch = "/api/issues/search?componentKeys=";
    public static $regex = "/\s+/u";
    public static $statuses = "OPEN,REOPENED";
    public static $statusesMin = "OPEN,CONFIRMED,REOPENED,RESOLVED";
    public static $statusesAll = "OPEN, CONFIRMED, REOPENED, RESOLVED, CLOSED";


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
        private Connection $connection,
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->connection = $connection;
    }

    /**
     * [Description for minutesTo]
     * Converti les minutes en jours, heures et minutes
     * @param mixed $minutes
     *
     * @return string
     *
     * Created at: 10/12/2022, 23:23:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    protected function minutesTo($minutes): string
    {
        $j = (int)($minutes / 1440);
        $h = (int)(($minutes - ($j * 1440)) / 60);
        $m = round($minutes % 60);
        if (empty($h) || is_null($h)) {
            $h = 0;
        }
        if ($j > 0) {
            return ($j . "d, " . $h . "h:" . $m . "min");
        } else {
            return ($h . "h:" . $m . "min");
        }
    }

    /**
     * [Description for batchInformationVersion]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 09/12/2022, 17:13:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchInformationVersion($mavenKey): array
    {
        /** On récupère le nombre de version par type */
        $sql = "SELECT type, COUNT(type) AS 'total'
            FROM information_projet
            WHERE maven_key='$mavenKey'
            GROUP BY type";
        $list = $this->em->getConnection()->prepare($sql)->executeQuery()->fetchAllAssociativeIndexed();
        $release = 0;
        $snapshot = 0;
        $autre = 0;
        //"SNAPSHOT" => array:1 ["total" => 2]
        foreach ($list as $key => $value) {
            if ($key === "RELEASE") {
                $release = $value["total"];
            }
            if ($key === "SNAPSHOT") {
                $snapshot = $value["total"];
            }
            if ($key === "N.C") {
                $autre = $value["total"];
            }
        }

        /** On récupère la dernière version et sa date de publication */
        $sql = "SELECT project_version as projet, date
            FROM information_projet
            WHERE maven_key='$mavenKey'
            ORDER BY date DESC LIMIT 1 ";
        $projet = $this->em->getConnection()->prepare($sql)->executeQuery()->fetchAllAssociative();

        return [
          "release" => $release, "snapshot" => $snapshot, "autre" => $autre,
          "projet" => $projet[0]["projet"],
          "date" => $projet[0]["date"]
        ];

    }

    /**
     * [Description for batchInformation]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 09/12/2022, 16:42:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchInformation(Client $client, $mavenKey): array
    {
        $sonar = $this->getParameter(static::$sonarUrl);
        $url = "$sonar/api/project_analyses/search?project=$mavenKey";

        /** On appel le client http */
        $result = $client->http($url);

        /** On récupère le manager de BD */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** On supprime les informations sur le projet */
        $sql = "DELETE FROM information_projet WHERE maven_key='$mavenKey'";
        $this->em->getConnection()->prepare($sql)->executeQuery();

        /* On ajoute les informations du projet dans la table information_projet. */
        foreach ($result["analyses"] as $value) {
            /**
             *  La version du projet doit être xxx-release, xxx-snapshot ou xxx
             *  Dans ce cas le tableau renvoi toujours [0] pour la version et
             *  [1] pour le type de version (release, snaphot ou null)
             */
            $explode = explode("-", $value["projectVersion"]);
            if (empty($explode[1])) {
                $explode[1] = 'N.C';
            }

            $information = new InformationProjet();
            $information->setMavenKey($mavenKey);
            $information->setAnalyseKey($value["key"]);
            $information->setDate(new DateTime($value["date"]));
            $information->setProjectVersion($value["projectVersion"]);
            $information->setType(strtoupper($explode[1]));
            $information->setDateEnregistrement($date);

            $this->em->persist($information);
            $this->em->flush();
        }

        $type = $this->batchInformationVersion($mavenKey);
        return [ "information" => $type ];
    }

    /**
     * [Description for batchMesure]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 09/12/2022, 16:59:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchMesure(Client $client, $mavenKey): array
    {
        /** On bind les variables */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** mesures globales */
        $url1 = "$tempoUrl/api/components/app?component=$mavenKey";

        /** on appel le client http */
        $result1 = $client->http($url1);
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /* On ajoute les mesures dans la table mesures. */
        /** Warning: Undefined array key "line" */
        if (intval($result1["measures"]["lines"])) {
            $lines = intval($result1["measures"]["lines"]);
        } else {
            $lines = 0;
        }

        /** Warning: Undefined array key "coverage" */
        if (array_key_exists("coverage", $result1["measures"])) {
            $coverage = $result1["measures"]["coverage"];
        } else {
            $coverage = 0;
        }

        /** Warning: Undefined array key "duplicationDensity" */
        if (array_key_exists("duplicationDensity", $result1["measures"])) {
            $duplicationDensity = $result1["measures"]["duplicationDensity"];
        } else {
            $duplicationDensity = 0;
        }

        /** Warning: Undefined array key "tests" */
        if (array_key_exists("tests", $result1["measures"])) {
            $tests = intval($result1["measures"]["tests"]);
        } else {
            $tests = 0;
        }

        /** Warning: Undefined array key "measures" */
        if (array_key_exists("issues", $result1["measures"])) {
            $issues = intval($result1["measures"]["issues"]);
        } else {
            $issues = 0;
        }

        /** On récupère le nombre de ligne de code */
        $url2 = "$tempoUrl/api/measures/component?component=$mavenKey&metricKeys=ncloc";
        $result2 = $client->http($url2);

        /** Warning: Undefined array key "measures" */
        if (array_key_exists("measures", $result2["component"])) {
            $ncloc = intval($result2["component"]["measures"][0]["value"]);
        } else {
            $ncloc = 0;
        }

        /** On enregistre */
        $mesure = new Mesures();
        $mesure->setMavenKey($mavenKey);
        $mesure->setProjectName($result1["projectName"]);
        $mesure->setLines($lines);
        $mesure->setNcloc($ncloc);
        $mesure->setCoverage($coverage);
        $mesure->setDuplicationDensity($duplicationDensity);
        $mesure->setTests(intval($tests));
        $mesure->setIssues(intval($issues));
        $mesure->setDateEnregistrement($date);

        $this->em->persist($mesure);
        $this->em->flush();

        /** On défini les données à historiser par le batch */
        $mesure = [
          "project_name" => $result1["projectName"],
          "lines" => $lines,
          "ncloc" => $ncloc,
          "coverage" => $coverage,
          "duplication_density" => $duplicationDensity,
          "tests" => intval($tests),
          "issues" => intval($issues),
        ];

        return [ "mesure" => $mesure ];
    }

    /**
     * [Description for batchNote]
     *
     * @param mixed $mavenKey
     * @param mixed $type
     *
     * @return array
     *
     * Created at: 10/12/2022, 19:05:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchNote(Client $client, $mavenKey, $type): array
    {
        /** On bind les variables */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        $url = "$tempoUrl/api/measures/search_history?component=$mavenKey
            &metrics=".$type."_rating&ps=1000";

        /** On appel le client http */
        $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $tempoDate = $date->format(static::$dateFormat);

        $mesures = $result["measures"][0]["history"];
        /** Enregistrement des nouvelles valeurs */
        foreach ($mesures as $mesure) {
            $tempoMesureDate = $mesure["date"];
            $tempoMesureValue = $mesure["value"];

            $sql = "INSERT OR IGNORE INTO notes (maven_key, type, date, value, date_enregistrement)
              VALUES ('$mavenKey', '$type', '$tempoMesureDate', '$tempoMesureValue', '$tempoDate')";
            $this->em->getConnection()->prepare($sql)->executeQuery();
        }
        /** On récupère la dernière note */
        $sql = "SELECT value FROM notes
            WHERE maven_key='$mavenKey' AND type='$type'
            ORDER BY date DESC
            LIMIT 1;";
        $r = $this->em->getConnection()->prepare($sql)->executeQuery()->fetchAllAssociative();

        /** On converti la valeur en note A, B , C, D, ou E */
        switch ($r[0]['value']) {
            case 1: $note = "A";
                break;
            case 2: $note = "B";
                break;
            case 3: $note = "C";
                break;
            case 4: $note = "D";
                break;
            case 5: $note = "E";
                break;
            default:
                echo "Je n'ai pas trouvé la note !!!";
        }
        /**
         *
         *( $r[0]['value'] === 1) {
         *       $note = "A";
         *     }
         *     if ($r[0]['value'] === 2) {
         *      $note = "B";
         *     }
         *     if ($r[0]['value'] === 3) {
         *       $note = "C";
         *     }
         *     if ($r[0]['value'] === 4) {
         *       $note = "D";
         *     }
         *     if ($r[0]['value'] === 5) {
         *       $note = "E";
         *     }
         */

        return ["note_$type" => ["value" => $note]];
    }

    /**
     * [Description for batchHotspot]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 10/12/2022, 21:08:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchHotspot(Client $client, $mavenKey): array
    {
        /** On bind les variables */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** On construit l'URL */
        $url = "$tempoUrl/api/hotspots/search?projectKey=$mavenKey&ps=500&p=1";

        /** On appel l'Api */
        $result = $client->http($url);

        /** On créé un objet Date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $niveau = 0;

        /** On supprime  les enregistrements correspondant à la clé **/
        $sql = "DELETE FROM hotspots WHERE maven_key='$mavenKey'";
        $this->em->getConnection()->prepare($sql)->executeQuery();

        /** On initialise le nombre de hotspot */
        $high = $medium = $low = 0;

        if ($result["paging"]["total"] != 0) {
            foreach ($result["hotspots"] as $value) {
                if ($value["vulnerabilityProbability"] == "HIGH") {
                    $niveau = 1;
                    $high++;
                }
                if ($value["vulnerabilityProbability"] == "MEDIUM") {
                    $niveau = 2;
                    $medium++;
                }
                if ($value["vulnerabilityProbability"] == "LOW") {
                    $niveau = 3;
                    $low++;
                }

                $hotspot = new  Hotspots();
                $hotspot->setMavenKey($mavenKey);
                $hotspot->setKey($value["key"]);
                $hotspot->setProbability($value["vulnerabilityProbability"]);
                $hotspot->setStatus($value["status"]);
                $hotspot->setNiveau($niveau);
                $hotspot->setDateEnregistrement($date);

                $this->em->persist($hotspot);
                $this->em->flush();
            }
        }

        /** On enregiste les données */
        $hotspot = [
          "nombre" => $result["paging"]["total"],
          "high" => $high, "medium" => $medium, "low" => $low
        ];

        return [ "hotspot" => $hotspot ];
    }

    /**
     * [Description for batchHotspotDetails]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 12/12/2022, 10:31:48 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchHotspotDetails(Client $client, $mavenKey, $owasp): array
    {
        /** On bind les variables */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /**On construit l'URL */
        $url = "$tempoUrl/api/hotspots/search?projectKey=$mavenKey&ps=500&p=1";

        /**  On appel l'Api */
        $result = $client->http($url);

        /** On crée un objet Date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $niveau = 0;

        /** On supprime  les enregistrements correspondant à la clé */
        $sql = "DELETE FROM hotspots WHERE maven_key='$mavenKey'";
        $this->em->getConnection()->prepare($sql)->executeQuery();

        $high = $medium = $low = 0;
        if ($result["paging"]["total"] != 0) {
            foreach ($result["hotspots"] as $value) {
                if ($value["vulnerabilityProbability"] == "HIGH") {
                    $niveau = 1;
                    $high++;
                }
                if ($value["vulnerabilityProbability"] == "MEDIUM") {
                    $niveau = 2;
                    $medium++;
                }
                if ($value["vulnerabilityProbability"] == "LOW") {
                    $niveau = 3;
                    $low++;
                }

                /** On enregistre en base de données */
                $hotspot = new  Hotspots();
                $hotspot->setMavenKey($mavenKey);
                $hotspot->setKey($value["key"]);
                $hotspot->setProbability($value["vulnerabilityProbability"]);
                $hotspot->setStatus($value["status"]);
                $hotspot->setNiveau($niveau);
                $hotspot->setDateEnregistrement($date);

                $this->em->persist($hotspot);
                $this->em->flush();
            }
        } else {
            $hotspot = new  HotspotOwasp();
            $hotspot->setMavenKey($mavenKey);
            $hotspot->setMenace($owasp);
            $hotspot->setProbability("NC");
            $hotspot->setStatus("NC");
            $hotspot->setNiveau("0");
            $hotspot->setDateEnregistrement($date);

            $this->em->persist($hotspot);
            $this->em->flush();
        }

        /** On enregistre les données */
        $hotspotDetails = [
          "nombre" => $result["paging"]["total"],
          "high" => $high,
          "medium" => $medium,
          "low" => $low,
        ];

        return [ "hotspot_details" => $hotspotDetails ];
    }

    public function BatchNoteHotspot($mavenKey): array
    {
        /** On récupère la dernière version et sa date de publication **/
        $sql = "SELECT COUNT(*) as to_review FROM hotspots WHERE maven_key='$mavenKey' AND status='TO_REVIEW'";
        $r = $this->em->getConnection()->prepare($sql)->executeQuery();
        $toReview = $r->fetchAllAssociative();

        $sql = "SELECT COUNT(*) as reviewed FROM hotspots WHERE maven_key='$mavenKey' AND status='REVIEWED'";
        $r = $this->em->getConnection()->prepare($sql)->executeQuery();
        $reviewed = $r->fetchAllAssociative();

        if (empty($toReview[0]["to_review"])) {
            $note = "A";
        } else {
            $ratio = intval($reviewed[0]["reviewed"]) * 100 / intval($toReview[0]["to_review"]) +
              intval($reviewed[0]["reviewed"]);
            if ($ratio >= 80) {
                $note = "A";
            }
            if ($ratio >= 70 && $ratio < 80) {
                $note = "B";
            }
            if ($ratio >= 50 && $ratio < 70) {
                $note = "C";
            }
            if ($ratio >= 30 && $ratio < 50) {
                $note = "D";
            }
            if ($ratio < 30) {
                $note = "E";
            }
        }

        $noteHotspot = ["value" => $note];
        return ["note_hotspot" => $noteHotspot];
    }

    /**
     * [Description for BatchNoSonar]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 10/12/2022, 22:29:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function BatchNoSonar(Client $client, $mavenKey): array
    {
        /** Bind les données */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** On construit l'URL et on appel le WS */
        $url = "$tempoUrl/api/issues/search?componentKeys=$mavenKey
            &rules=java:S1309,java:NoSonar&ps=500&p=1";

        $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** On supprime les données du projet de la table NoSonar **/
        $sql = "DELETE FROM no_sonar WHERE maven_key='$mavenKey'";
        $this->em->getConnection()->prepare($sql)->executeQuery();

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

    /**
     * [Description for BatchAnomalie]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 11/12/2022, 09:03:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function BatchAnomalie(Client $client, $mavenKey): array
    {
        /** On bind les variables */
        $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;

        /** On créé un objet date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /**
         * On choisi le type de status des anomalies : [OPEN, CONFIRMED, REOPENED, RESOLVED, CLOSED]
         * Type : statuses, statusesMin et statusesAll
         */
        $typeStatuses = static::$statuses;
        $url1 = "$tempoUrlLong$mavenKey&facets=directories,types,severities&p=1&ps=1&statuses=$typeStatuses";

        /** On récupère le total de la Dette technique pour les BUG */
        $url2 = "$tempoUrlLong$mavenKey&types=BUG&p=1&ps=1";

        /** On récupère le total de la Dette technique pour les VULNERABILITY */
        $url3 = "$tempoUrlLong$mavenKey&types=VULNERABILITY&p=1&ps=1";

        /** On récupère le total de la Dette technique pour les CODE_SMELL */
        $url4 = "$tempoUrlLong$mavenKey&types=CODE_SMELL&p=1&ps=1";

        /** On appel le client http pour les requête 1 à 4 (2 à 4 pour la dette) */
        $result1 = $client->http(trim(preg_replace(static::$regex, " ", $url1)));
        $result2 = $client->http($url2);
        $result3 = $client->http($url3);
        $result4 = $client->http($url4);

        if ($result1["paging"]["total"] != 0) {
            /** On supprime  les enregistrement correspondant à la clé */
            $sql = "DELETE FROM anomalie WHERE maven_key='$mavenKey'";
            $this->em->getConnection()->prepare($sql)->executeQuery();

            /** nom du projet */
            $app = explode(":", $mavenKey);
            if (count($app)===1) {
                /** La clé maven n'est pas conforme, on ne peut pas déduire le nom de l'application */
                array_push($app, $mavenKey);
            }

            $anomalieTotal = $result1["total"];
            $detteMinute = $result1["effortTotal"];
            $dette = $this->minutesTo($detteMinute);
            $detteReliabilityMinute = $result2["effortTotal"];
            $detteReliability = $this->minutesTo($detteReliabilityMinute);
            $detteVulnerabilityMinute = $result3["effortTotal"];
            $detteVulnerability = $this->minutesTo($detteVulnerabilityMinute);
            $detteCodeSmellMinute = $result4["effortTotal"];
            $detteCodeSmell = $this->minutesTo($detteCodeSmellMinute);

            $facets = $result1["facets"];
            /** modules */
            $frontend = $backend = $autre = $nombreAnomalie = 0;
            foreach ($facets as $facet) {
                $nombreAnomalie++;
                //** On récupère le nombre de signalement par sévérité */
                if ($facet["property"] == "severities") {
                    foreach ($facet["values"] as $severity) {
                        switch ($severity["val"]) {
                            case "BLOCKER" : $blocker = $severity["count"];
                                break;
                            case "CRITICAL" : $critical = $severity["count"];
                                break;
                            case "MAJOR" : $major = $severity["count"];
                                break;
                            case "INFO" : $info = $severity["count"];
                                break;
                            case "MINOR" : $minor = $severity["count"];
                                break;
                            default:
                                $this->logger->NOTICE("HoneyPot : Répartition par sévérité !");
                        }
                    }
                }

                //** On récupère le nombre de signalement par type */
                if ($facet["property"] == "types") {
                    foreach ($facet["values"] as $type) {
                        switch ($type["val"]) {
                            case "BUG" : $bug = $type["count"];
                                break;
                            case "VULNERABILITY" : $vulnerability = $type["count"];
                                break;
                            case "CODE_SMELL" : $codeSmell = $type["count"];
                                break;
                            default:
                                $this->logger->NOTICE("HoneyPot : Répartition par type !");

                        }
                    }
                }

                //** On récupère le nombre de signalement par module */
                if ($facet["property"] == "directories") {
                    foreach ($facet["values"] as $directory) {
                        $file = str_replace($mavenKey . ":", "", $directory["val"]);
                        $module = explode("/", $file);
                        if ($module[0] === "du-presentation" ||
                            $module[0] === "rs-presentation") {
                            $frontend = $frontend + $directory["count"];
                        }
                        if ($module[0] === $app[1] . "-presentation" ||
                            $module[0] === $app[1] . "-presentation-commun" ||
                            $module[0] === $app[1] . "-presentation-ear" ||
                            $module[0] === $app[1] . "-webapp") {
                            $frontend = $frontend + 1;
                        }
                        if ($module[0] === "rs-metier") {
                            $backend = $backend + $directory["count"];
                        }
                        if ($module[0] === $app[1] . "-metier" ||
                            $module[0] === $app[1] . "-common" ||
                            $module[0] === $app[1] . "-api" ||
                            $module[0] === $app[1] . "-dao") {
                            $backend = $backend + $directory["count"];
                        }
                        if ($module[0] === $app[1] . "-metier-ear" ||
                            $module[0] === $app[1] . "-service" ||
                            $module[0] === $app[1] . "-serviceweb" ||
                            $module[0] === $app[1] . "-middleoffice") {
                            $backend = $backend + $directory["count"];
                        }
                        if ($module[0] === $app[1] . "-metier-rest" ||
                            $module[0] === $app[1] . "-entite" ||
                            $module[0] === $app[1] . "-serviceweb-client") {
                            $backend = $backend + $directory["count"];
                        }
                        if ($module[0] === $app[1] . "-batch" ||
                            $module[0] === $app[1] . "-batchs" ||
                            $module[0] === $app[1] . "-batch-envoi-dem-aval" ||
                            $module[0] === $app[1] . "-batch-import-billets") {
                            $autre = $autre + $directory["count"];
                        }
                        if ($module[0] === $app[1] . "-rdd") {
                            $autre = $autre + 1;
                        }
                    }
                }
            }
            /** Enregistrement dans la table Anomalie */
            $issue = new Anomalie();
            $issue->setMavenKey($mavenKey);
            $issue->setProjectName($app[1]);
            $issue->setAnomalieTotal($anomalieTotal);
            $issue->setDette($dette);
            $issue->setDetteMinute($detteMinute);
            $issue->setDetteReliability($detteReliability);
            $issue->setDetteReliabilityMinute($detteReliabilityMinute);
            $issue->setDetteVulnerability($detteVulnerability);
            $issue->setDetteVulnerabilityMinute($detteVulnerabilityMinute);
            $issue->setDetteCodeSmell($detteCodeSmell);
            $issue->setDetteCodeSmellMinute($detteCodeSmellMinute);
            $issue->setFrontend($frontend);
            $issue->setBackend($backend);
            $issue->setAutre($autre);
            $issue->setBlocker($blocker);
            $issue->setCritical($critical);
            $issue->setMajor($major);
            $issue->setInfo($info);
            $issue->setMinor($minor);
            $issue->setBug($bug);
            $issue->setVulnerability($vulnerability);
            $issue->setCodeSmell($codeSmell);
            $issue->setDateEnregistrement($date);

            $this->em->persist($issue);
            $this->em->flush();
        }

        /** On enregistre les données */
        $anomalie = [
          "anomalie_totale" => $anomalieTotal, "dette_minute" => $detteMinute,
          "frontend" => $frontend, "backend" => $backend, "autre" => $autre,
          "blocker" => $blocker,"critical" => $critical,"major" => $major,"info" => $info,"minor" => $minor,
          "bug" => $bug,"vulnerability" => $vulnerability,"code_smell" => $codeSmell,
        ];

        return [ "anomalie" => $anomalie ];
    }

    /**
     * [Description for BatchAnomalieDetails]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 12/12/2022, 10:25:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function BatchAnomalieDetails(Client $client, $mavenKey): array
    {
        /** On bind les variables */
        $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;

        /** Pour les Bug */
        $url1 = "$tempoUrlLong$mavenKey&facets=severities&types=BUG&ps=1&p=1&statuses=OPEN";

        /** Pour les Vulnérabilités */
        $url2 = "$tempoUrlLong$mavenKey&facets=severities&types=VULNERABILITY&ps=1&p=1&statuses=OPEN";

        /** Pour les mauvaises pratiques */
        $url3 = "$tempoUrlLong$mavenKey&facets=severities&types=CODE_SMELL&ps=1&p=1&statuses=OPEN";

        /** On appel le client http pour les requêteq 1 à 3 */
        $result1 = $client->http($url1);
        $result2 = $client->http($url2);
        $result3 = $client->http($url3);

        $total1 = $result1["paging"]["total"];
        $total2 = $result2["paging"]["total"];
        $total3 = $result3["paging"]["total"];

        if ($total1 !== 0 || $total2 !== 0 || $total3 !== 0) {
            /** On supprime  l'enregistrement correspondant à la clé */
            $sql = "DELETE FROM anomalie_details WHERE maven_key='$mavenKey'";
            $this->em->getConnection()->prepare($sql)->executeQuery();

            /** On crée un objet DateTime */
            $date = new DateTime();
            $date->setTimezone(new DateTimeZone(static::$europeParis));

            /** On bind les résultats */
            $r1 = $result1["facets"];
            $r2 = $result2["facets"];
            $r3 = $result3["facets"];

            foreach ($r1[0]["values"] as $severity) {
                switch ($severity["val"]) {
                    case "BLOCKER" : $bugBlocker = $severity["count"];
                        break;
                    case "CRITICAL" : $bugCritical = $severity["count"];
                        break;
                    case "MAJOR" : $bugMajor = $severity["count"];
                        break;
                    case "INFO" : $bugInfo = $severity["count"];
                        break;
                    case "MINOR" : $bugMinor = $severity["count"];
                        break;
                    default:
                        $this->logger->NOTICE("HoneyPot: Répartition des bugs par sévérité !");
                }
            }

            foreach ($r2[0]["values"] as $severity) {
                switch ($severity["val"]) {
                    case "BLOCKER" : $vulnerabilityBlocker = $severity["count"];
                        break;
                    case "CRITICAL" : $vulnerabilityCritical = $severity["count"];
                        break;
                    case "MAJOR" : $vulnerabilityMajor = $severity["count"];
                        break;
                    case "INFO" : $vulnerabilityInfo = $severity["count"];
                        break;
                    case "MINOR" : $vulnerabilityMinor = $severity["count"];
                        break;
                    default:
                        $this->logger->NOTICE("HoneyPot : Répartition des bugs par sévérité !");
                }
            }

            foreach ($r3[0]["values"] as $severity) {
                switch ($severity["val"]) {
                    case "BLOCKER" : $codeSmellBlocker = $severity["count"];
                        break;
                    case "CRITICAL" : $codeSmellCritical = $severity["count"];
                        break;
                    case "MAJOR" : $codeSmellMajor = $severity["count"];
                        break;
                    case "INFO" : $codeSmellInfo = $severity["count"];
                        break;
                    case "MINOR" : $codeSmellMinor = $severity["count"];
                        break;
                    default:
                        $this->logger->NOTICE("HoneyPot : Répartition des bugs par sévérité !");
                }
            }

            /** On récupère le nom de l'application */
            $explode = explode(":", $mavenKey);
            $name = $explode[1];

            /** On enregistre en base */
            $details = new AnomalieDetails();
            $details->setMavenKey($mavenKey);
            $details->setName($name);

            $details->setBugBlocker($bugBlocker);
            $details->setBugCritical($bugCritical);
            $details->setBugMajor($bugMajor);
            $details->setBugMinor($bugMinor);
            $details->setBugInfo($bugInfo);

            $details->setVulnerabilityBlocker($vulnerabilityBlocker);
            $details->setVulnerabilityCritical($vulnerabilityCritical);
            $details->setVulnerabilityMajor($vulnerabilityMajor);
            $details->setVulnerabilityMinor($vulnerabilityMinor);
            $details->setVulnerabilityInfo($vulnerabilityInfo);

            $details->setCodeSmellBlocker($codeSmellBlocker);
            $details->setCodeSmellCritical($codeSmellCritical);
            $details->setCodeSmellMajor($codeSmellMajor);
            $details->setCodeSmellMinor($codeSmellMinor);
            $details->setCodeSmellInfo($codeSmellInfo);

            $details->setDateEnregistrement($date);

            /** On enregistre les données en base */
            $this->em->persist($details);

            /** On catch l'erreur sur la clé composite : maven_key, version, date_version */
            try {
                $this->em->flush();
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->logger->ERROR("BatchAnomalieDetail: Enregistrement : ", [$e->getCode()]);
            }
        }

        //** On enregiste les données */
        $anomalieDetails = [
          "bug_blocker" => $bugBlocker,
          "bug_critical" => $bugCritical,
          "bug_major" => $bugMajor,
          "bug_minor" => $bugMinor,
          "bug_info" => $bugInfo,

          "vulnerability_blocker" => $vulnerabilityBlocker,
          "vulnerability_critical" => $vulnerabilityCritical,
          "vulnerability_major" => $vulnerabilityMajor,
          "vulnerability_minor" => $vulnerabilityMinor,
          "vulnerability_info" => $vulnerabilityInfo,

          "code_smell_blocker" => $codeSmellBlocker,
          "code_smell_critical" => $codeSmellCritical,
          "code_smell_major" => $codeSmellMajor,
          "code_smell_minor" => $codeSmellMinor,
          "code_smell_info" => $codeSmellInfo,
        ];

        return [ "anomalie_details" => $anomalieDetails ];
    }

    /**
     * [Description for BatchNouvelleCollecte]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 12/12/2022, 16:13:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchNouvelleCollecte(Client $client, $mavenKey): array
    {
        /** On démarre la mesure du traitement */
        $debutTraitement = new DateTime();
        $debutTraitement->setTimezone(new DateTimeZone(static::$europeParis));

        /**
         * "information" => array:5
         *  "release" => 0
         *  "snapshot" => 2
         *  "autre" => 0
         *  "projet" => "1.1.5-SNAPSHOT"
         *  "date" => "2022-02-18 15:29:02"
         */
        $batchInformation = $this->batchInformation($client, $mavenKey);

        /**
         * "mesure" => array:7
         *  "project_name" => "ServicesTransversesFAM"
         *  "lines" => 16790
         *  "ncloc" => 6561
         *  "coverage" => "0.0"
         *  "duplication_density" => "8.9"
         *  "tests" => 0
         *  "issues" => 2677
         */
        $batchMesure = $this->batchMesure($client, $mavenKey);

        /**
         * "note" => array:1
         *  "value" => 3 --> [A, B, C , D , E, F]
         * */
        $batchNoteReliability = $this->batchNote($client, $mavenKey, "reliability");
        $batchNoteSecurity = $this->batchNote($client, $mavenKey, "security");
        $batchNoteSqale = $this->batchNote($client, $mavenKey, "sqale");

        /**
          * "hotspot" => array:4
          *   "nombre" => 4
          *   "high" => 0
          *   "medium" => 0
          *   "low" => 4
          */
        $batchHotspot = $this->batchHotspot($client, $mavenKey);

        /**
         *  On lance la collecte des Hotspot OWASP.
         * "hotspot_details" => array:4
         *    "nombre" => 4
         *    "high" => 0
         *    "medium" => 0
         *    "low" => 4
         */
        $this->batchHotspotDetails($client, $mavenKey, "a1");
        $this->batchHotspotDetails($client, $mavenKey, "a2");
        $this->batchHotspotDetails($client, $mavenKey, "a3");
        $this->batchHotspotDetails($client, $mavenKey, "a4");
        $this->batchHotspotDetails($client, $mavenKey, "a5");
        $this->batchHotspotDetails($client, $mavenKey, "a6");
        $this->batchHotspotDetails($client, $mavenKey, "a7");
        $this->batchHotspotDetails($client, $mavenKey, "a8");
        $this->batchHotspotDetails($client, $mavenKey, "a9");
        $this->batchHotspotDetails($client, $mavenKey, "a10");

        /**
         * note_hotspot" => array:1
         *  "value" => "E"
         */
        $batchNoteHotspot = $this->batchNoteHotspot($mavenKey);

        /**
         * "nosonar" => array:2
         *    "suppress_warning" => 1
         *    "no_sonar" => 0
         */
        $batchNoSonar = $this->batchNoSonar($client, $mavenKey);

        /**
          * "anomalie": 13
          *   "anomalie_totale" => 2677
          *   "dette_minute" => 20539
          *   "frontend" => 2672
          *   "backend" => 0
          *   "autre" => 0
          *   "blocker" => 4
          *   "critical" => 23
          *   "major" => 977
          *   "info" => 325
          *   "minor" => 1348
          *   "bug" => 6
          *   "vulnerability" => 7
          *   "code_smell" => 2664
          */
        $batchAnomalie = $this->batchAnomalie($client, $mavenKey);
        /**
         * anomalie_details" => array:15
         *  "bug_blocker" => 0
         *  "bug_critical" => 0
         *  "bug_major" => 6
         *  "bug_minor" => 0
         *  "bug_info" => 0
         *  "vulnerability_blocker" => 0
         *  "vulnerability_critical" => 1
         *  "vulnerability_major" => 3
         *  "vulnerability_minor" => 3
         *  "vulnerability_info" => 0
         *  "code_smell_blocker" => 4
         *  "code_smell_critical" => 22
         *  "code_smell_major" => 968
         *  "code_smell_minor" => 1345
         *  "code_smell_info" => 325
         */
        $batchAnomalieDetails = $this->batchAnomalieDetails($client, $mavenKey);

        /** Enregistrement des données en base **/
        $save = new Historique();

        /** Information version */
        $save->setMavenKey($mavenKey);
        $nom = strtolower($batchMesure["mesure"]["project_name"]);
        $save->setNomProjet($nom);
        $save->setVersionRelease($batchInformation["information"]["release"]);
        $save->setVersionSnapshot($batchInformation["information"]["snapshot"]);
        $save->setVersionAutre($batchInformation["information"]["autre"]);
        $save->setVersion($batchInformation["information"]["projet"]);
        $save->setDateVersion($batchInformation["information"]["date"]);

        /** Mesure projet */
        $save->setNombreLigne($batchMesure["mesure"]["lines"]);
        $save->setNombreLigneCode($batchMesure["mesure"]["ncloc"]);
        $save->setCouverture($batchMesure["mesure"]["coverage"]);
        $save->setDuplication($batchMesure["mesure"]["duplication_density"]);
        $save->setTestsUnitaires($batchMesure["mesure"]["tests"]);
        $save->setNombreDefaut($batchMesure["mesure"]["issues"]);

        /** Informations sur les exceptions */
        $save->setSuppressWarning($batchNoSonar["nosonar"]["suppress_warning"]);
        $save->setNoSonar($batchNoSonar["nosonar"]["no_sonar"]);

        /** Notes Fiabilité, sécurité, hotspots et mauvaises pratique */
        $save->setNoteReliability($batchNoteReliability["note_reliability"]["value"]);
        $save->setNoteSecurity($batchNoteSecurity["note_security"]["value"]);
        $save->setNoteHotspot($batchNoteHotspot["note_hotspot"]["value"]);
        $save->setNoteSqale($batchNoteSqale["note_sqale"]["value"]);

        /** répartition des hotspots **/
        $save->setHotspotHigh($batchHotspot["hotspot"]["high"]);
        $save->setHotspotMedium($batchHotspot["hotspot"]["medium"]);
        $save->setHotspotLow($batchHotspot["hotspot"]["low"]);
        $save->setHotspotTotal($batchHotspot["hotspot"]["nombre"]);

        /** Dette technique */
        $save->setDette($batchAnomalie["anomalie"]["dette_minute"]);

        /** Nombre de défaut */
        $save->setNombreBug($batchAnomalie["anomalie"]["bug"]);
        $save->setNombreVulnerability($batchAnomalie["anomalie"]["vulnerability"]);
        $save->setNombreCodeSmell($batchAnomalie["anomalie"]["code_smell"]);

        /** répartition par modules (Java) */
        $save->setFrontend($batchAnomalie["anomalie"]["frontend"]);
        $save->setBackend($batchAnomalie["anomalie"]["backend"]);
        $save->setAutre($batchAnomalie["anomalie"]["autre"]);

        /** Répartition par type */
        $save->setNombreAnomalieBloquant($batchAnomalie["anomalie"]["blocker"]);
        $save->setNombreAnomalieCritique($batchAnomalie["anomalie"]["critical"]);
        $save->setNombreAnomalieInfo($batchAnomalie["anomalie"]["info"]);
        $save->setNombreAnomalieMajeur($batchAnomalie["anomalie"]["major"]);
        $save->setNombreAnomalieMineur($batchAnomalie["anomalie"]["minor"]);

        /** Nombre de défaut par sévérité **/
        /** Les BUG */
        $save->setBugBlocker($batchAnomalieDetails["anomalie_details"]["bug_blocker"]);
        $save->setBugCritical($batchAnomalieDetails["anomalie_details"]["bug_critical"]);
        $save->setBugMajor($batchAnomalieDetails["anomalie_details"]["bug_major"]);
        $save->setBugMinor($batchAnomalieDetails["anomalie_details"]["bug_minor"]);
        $save->setBugInfo($batchAnomalieDetails["anomalie_details"]["bug_info"]);

        /** Les VULNERABILITY */
        $save->setVulnerabilityBlocker($batchAnomalieDetails["anomalie_details"]["vulnerability_blocker"]);
        $save->setVulnerabilityCritical($batchAnomalieDetails["anomalie_details"]["vulnerability_critical"]);
        $save->setVulnerabilityMajor($batchAnomalieDetails["anomalie_details"]["vulnerability_major"]);
        $save->setVulnerabilityMinor($batchAnomalieDetails["anomalie_details"]["vulnerability_minor"]);
        $save->setVulnerabilityInfo($batchAnomalieDetails["anomalie_details"]["vulnerability_info"]);

        /** Les CODE SMELL */
        $save->setCodeSmellBlocker($batchAnomalieDetails["anomalie_details"]["code_smell_blocker"]);
        $save->setCodeSmellCritical($batchAnomalieDetails["anomalie_details"]["code_smell_critical"]);
        $save->setCodeSmellMajor($batchAnomalieDetails["anomalie_details"]["code_smell_major"]);
        $save->setCodeSmellMinor($batchAnomalieDetails["anomalie_details"]["code_smell_minor"]);
        $save->setCodeSmellInfo($batchAnomalieDetails["anomalie_details"]["code_smell_info"]);

        /** Je une verion initiale ?  0 (false) and 1 (true). */
        $save->setInitial(0);

        /** On ajoute la date et on enregistre */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $save->setDateEnregistrement($date);
        $this->em->persist($save);

        /** On catch l'erreur sur la clé composite : maven_key, version, date_version*/
        try {
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            /** General error: 5 database is locked" */
            /** General error: 19 violation de clé */
            if ($e->getCode() === 19) {
                $this->logger->ERROR("[BATCH-009] [$nom] Violation de clé : $e");
                $this->em->close();
            } else {
                $this->logger->ERROR("[BATCH-009] [$nom] Erreur lors de l'enregistrement.", ["Cause $e"]);
                $this->em->close();
            }
        }

        /** Fin du traitement */
        $finTraitement = new DateTime();
        $finTraitement->setTimezone(new DateTimeZone(static::$europeParis));
        $interval = $debutTraitement->diff($finTraitement);
        $temps = $interval->format("%H:%I:%S");

        $this->logger->INFO("[BATCH-010] [$nom] Enregistrement des données en $temps");
        return ["message" => "[BATCH-010] [$nom] Enregistrement des données en $temps"];
    }

    public function batchAjouteCollecte(Client $client, $mavenKey): array
    {
        /** On démarre la mesure du traitement */
        $debutTraitement = new DateTime();
        $debutTraitement->setTimezone(new DateTimeZone(static::$europeParis));

        /**
         * "information" => array:5
         *  "release" => 0
         *  "snapshot" => 2
         *  "autre" => 0
         *  "projet" => "1.1.5-SNAPSHOT"
         *  "date" => "2022-02-18 15:29:02"
         */
        $batchInformation = $this->batchInformation($client, $mavenKey);

        /**
         * "mesure" => array:7
         *  "project_name" => "ServicesTransversesFAM"
         *  "lines" => 16790
         *  "ncloc" => 6561
         *  "coverage" => "0.0"
         *  "duplication_density" => "8.9"
         *  "tests" => 0
         *  "issues" => 2677
         */
        $batchMesure = $this->batchMesure($client, $mavenKey);

        /**
         * "note" => array:1
         *  "value" => 3
         * */
        $batchNoteReliability = $this->batchNote($client, $mavenKey, "reliability");
        $batchNoteSecurity = $this->batchNote($client, $mavenKey, "security");
        $batchNoteSqale = $this->batchNote($client, $mavenKey, "sqale");

        /**
          * "hotspot" => array:4
          *   "nombre" => 4
          *   "high" => 0
          *   "medium" => 0
          *   "low" => 4
          */
        $batchHotspot = $this->batchHotspot($client, $mavenKey);

        /**
         * "hotspot_details" => array:4
         *    "nombre" => 4
         *    "high" => 0
         *    "medium" => 0
         *    "low" => 4
         */
        $this->batchHotspotDetails($client, $mavenKey, "a1");
        $this->batchHotspotDetails($client, $mavenKey, "a2");
        $this->batchHotspotDetails($client, $mavenKey, "a3");
        $this->batchHotspotDetails($client, $mavenKey, "a4");
        $this->batchHotspotDetails($client, $mavenKey, "a5");
        $this->batchHotspotDetails($client, $mavenKey, "a6");
        $this->batchHotspotDetails($client, $mavenKey, "a7");
        $this->batchHotspotDetails($client, $mavenKey, "a8");
        $this->batchHotspotDetails($client, $mavenKey, "a9");
        $this->batchHotspotDetails($client, $mavenKey, "a10");

        /**
         * note_hotspot" => array:1
         *  "value" => "E"
         */
        $batchNoteHotspot = $this->batchNoteHotspot($mavenKey);

        /**
         * "nosonar" => array:2
         *    "suppress_warning" => 1
         *    "no_sonar" => 0
         */
        $batchNoSonar = $this->batchNoSonar($client, $mavenKey);

        /**
          * "anomalie": 13
          *   "anomalie_totale" => 2677
          *   "dette_minute" => 20539
          *   "frontend" => 2672
          *   "backend" => 0
          *   "autre" => 0
          *   "blocker" => 4
          *   "critical" => 23
          *   "major" => 977
          *   "info" => 325
          *   "minor" => 1348
          *   "bug" => 6
          *   "vulnerability" => 7
          *   "code_smell" => 2664
          */
        $batchAnomalie = $this->batchAnomalie($client, $mavenKey);
        /**
         * anomalie_details" => array:15
         *  "bug_blocker" => 0
         *  "bug_critical" => 0
         *  "bug_major" => 6
         *  "bug_minor" => 0
         *  "bug_info" => 0
         *  "vulnerability_blocker" => 0
         *  "vulnerability_critical" => 1
         *  "vulnerability_major" => 3
         *  "vulnerability_minor" => 3
         *  "vulnerability_info" => 0
         *  "code_smell_blocker" => 4
         *  "code_smell_critical" => 22
         *  "code_smell_major" => 968
         *  "code_smell_minor" => 1345
         *  "code_smell_info" => 325
         */
        $batchAnomalieDetails = $this->batchAnomalieDetails($client, $mavenKey);

        /** Enregistrement des données en base **/
        $save = new Historique();

        /** Information version */
        $save->setMavenKey($mavenKey);
        $nom = strtolower($batchMesure["mesure"]["project_name"]);
        $save->setNomProjet($nom);
        $save->setVersionRelease($batchInformation["information"]["release"]);
        $save->setVersionSnapshot($batchInformation["information"]["snapshot"]);
        $save->setVersionAutre($batchInformation["information"]["autre"]);
        $save->setVersion($batchInformation["information"]["projet"]);
        $save->setDateVersion($batchInformation["information"]["date"]);

        /** Mesure projet */
        $save->setNombreLigne($batchMesure["mesure"]["lines"]);
        $save->setNombreLigneCode($batchMesure["mesure"]["ncloc"]);
        $save->setCouverture($batchMesure["mesure"]["coverage"]);
        $save->setDuplication($batchMesure["mesure"]["duplication_density"]);
        $save->setTestsUnitaires($batchMesure["mesure"]["tests"]);
        $save->setNombreDefaut($batchMesure["mesure"]["issues"]);

        /** Informations sur les exceptions */
        $save->setSuppressWarning($batchNoSonar["nosonar"]["suppress_warning"]);
        $save->setNoSonar($batchNoSonar["nosonar"]["no_sonar"]);

        /** Notes Fiabilité, sécurité, hotspots et mauvaises pratique */
        $save->setNoteReliability($batchNoteReliability["note_reliability"]["value"]);
        $save->setNoteSecurity($batchNoteSecurity["note_security"]["value"]);
        $save->setNoteHotspot($batchNoteHotspot["note_hotspot"]["value"]);
        $save->setNoteSqale($batchNoteSqale["note_sqale"]["value"]);

        /** répartition des hotspots **/
        $save->setHotspotHigh($batchHotspot["hotspot"]["high"]);
        $save->setHotspotMedium($batchHotspot["hotspot"]["medium"]);
        $save->setHotspotLow($batchHotspot["hotspot"]["low"]);
        $save->setHotspotTotal($batchHotspot["hotspot"]["nombre"]);

        /** Dette technique */
        $save->setDette($batchAnomalie["anomalie"]["dette_minute"]);

        /** Nombre de défaut */
        $save->setNombreBug($batchAnomalie["anomalie"]["bug"]);
        $save->setNombreVulnerability($batchAnomalie["anomalie"]["vulnerability"]);
        $save->setNombreCodeSmell($batchAnomalie["anomalie"]["code_smell"]);

        /** répartition par modules (Java) */
        $save->setFrontend($batchAnomalie["anomalie"]["frontend"]);
        $save->setBackend($batchAnomalie["anomalie"]["backend"]);
        $save->setAutre($batchAnomalie["anomalie"]["autre"]);

        /** Répartition par type */
        $save->setNombreAnomalieBloquant($batchAnomalie["anomalie"]["blocker"]);
        $save->setNombreAnomalieCritique($batchAnomalie["anomalie"]["critical"]);
        $save->setNombreAnomalieInfo($batchAnomalie["anomalie"]["info"]);
        $save->setNombreAnomalieMajeur($batchAnomalie["anomalie"]["major"]);
        $save->setNombreAnomalieMineur($batchAnomalie["anomalie"]["minor"]);

        /** Nombre de défaut par sévérité **/
        /** Les BUG */
        $save->setBugBlocker($batchAnomalieDetails["anomalie_details"]["bug_blocker"]);
        $save->setBugCritical($batchAnomalieDetails["anomalie_details"]["bug_critical"]);
        $save->setBugMajor($batchAnomalieDetails["anomalie_details"]["bug_major"]);
        $save->setBugMinor($batchAnomalieDetails["anomalie_details"]["bug_minor"]);
        $save->setBugInfo($batchAnomalieDetails["anomalie_details"]["bug_info"]);

        /** Les VULNERABILITY */
        $save->setVulnerabilityBlocker($batchAnomalieDetails["anomalie_details"]["vulnerability_blocker"]);
        $save->setVulnerabilityCritical($batchAnomalieDetails["anomalie_details"]["vulnerability_critical"]);
        $save->setVulnerabilityMajor($batchAnomalieDetails["anomalie_details"]["vulnerability_major"]);
        $save->setVulnerabilityMinor($batchAnomalieDetails["anomalie_details"]["vulnerability_minor"]);
        $save->setVulnerabilityInfo($batchAnomalieDetails["anomalie_details"]["vulnerability_info"]);

        /** Les CODE SMELL */
        $save->setCodeSmellBlocker($batchAnomalieDetails["anomalie_details"]["code_smell_blocker"]);
        $save->setCodeSmellCritical($batchAnomalieDetails["anomalie_details"]["code_smell_critical"]);
        $save->setCodeSmellMajor($batchAnomalieDetails["anomalie_details"]["code_smell_major"]);
        $save->setCodeSmellMinor($batchAnomalieDetails["anomalie_details"]["code_smell_minor"]);
        $save->setCodeSmellInfo($batchAnomalieDetails["anomalie_details"]["code_smell_info"]);

        /** Je suis une verion initiale ?  0 (false) and 1 (true). */
        $save->setInitial(0);

        /** On ajoute la date et on enregistre */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $save->setDateEnregistrement($date);
        $this->em->persist($save);

        /** On catch l'erreur sur la clé composite : maven_key, version, date_version*/
        try {
            $this->em->flush();
        } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
            /** General error: 5 database is locked" */
            /** General error: 19 violation de clé */
            if ($e->getCode() === 19) {
                $this->logger->ERROR("[BATCH-009] [$nom] Violation de clé : $e");
                $this->em->close();
            } else {
                $this->logger->ERROR("[BATCH-009] [$nom] Erreur lors de l'enregistrement.", ["Cause $e"]);
                $this->em->close();
            }
        }

        /** Fin du traitement */
        $finTraitement = new DateTime();
        $finTraitement->setTimezone(new DateTimeZone(static::$europeParis));
        $interval = $debutTraitement->diff($finTraitement);
        $temps = $interval->format("%H:%I:%S");

        $this->logger->INFO("[BATCH-010] [$nom] Enregistrement des données en $temps");
        return ["message" => "[BATCH-010] [$nom] Enregistrement des données en $temps"];
    }
}
