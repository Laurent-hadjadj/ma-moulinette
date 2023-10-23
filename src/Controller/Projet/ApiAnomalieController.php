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

namespace App\Controller\Projet;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

// Accès aux tables SLQLite
use App\Entity\Main\Anomalie;
use App\Entity\Main\AnomalieDetails;

use Doctrine\ORM\EntityManagerInterface;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

/** minuteTools */
use App\Service\DateTools;

/**
 * [Description ApiAnomalieController]
 */
class ApiAnomalieController extends AbstractController
{

  /** Définition des constantes */
  public static $sonarUrl = "sonar.url";
  public static $europeParis = "Europe/Paris";
  public static $apiIssuesSearch = "/api/issues/search?componentKeys=";
  public static $regex = "/\s+/u";
  public static $erreurMavenKey="La clé maven est vide!";
  public static $reference="<strong>[PROJET-002]</strong>";
  public static $message="Vous devez avoir le rôle COLLECTE pour réaliser cette action.";
  public static $statuses="OPEN,REOPENED";
  public static $statusesMin = "OPEN,CONFIRMED,REOPENED,RESOLVED";
  public static $statusesAll = "OPEN, CONFIRMED, REOPENED, RESOLVED, CLOSED";

  /**
   * [Description for __construct]
   *
   * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct(
    private LoggerInterface $logger,
    private EntityManagerInterface $em)
  {
    $this->logger = $logger;
    $this->em = $em;
  }

  /**
   * [Description for Anomalie]
   * Récupère le total des anomalies, avec un filtre par répertoire, sévérité et types.
   * https://{URL}/api/issues/search?componentKeys={mavenKey}&facets=directories,types,severities&p=1&ps=1&statuses=OPEN
   * https://{URL}/api/issues/search?componentKeys={mavenKey}&types={type}&p=1&ps=1
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:32:28 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/anomalie', name: 'projet_anomalie', methods: ['GET'])]
  public function projetAnomalie(Request $request, Client $client, DateTools $dateTools): response
  {
    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;
    $mavenKey = $request->get('mavenKey');
    $mode = $request->get('mode');

    /** On teste si la clé est valide */
    if ($mavenKey==="null" && $mode==="TEST") {
      return $response->setData(["mode"=>$mode, "mavenKey"=>$mavenKey,
                                "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    /** On vérifie si l'utilisateur à un rôle Collecte ? */
    if (!$this->isGranted('ROLE_COLLECTE')){
      return $response->setData(["mode"=>$mode , "type"=>'alert',
                                "reference" => static::$reference, "message"=> static::$message, Response::HTTP_OK]);
    }

    /** On créé un objet date. */
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));

    /**
     * On choisi le type de status des anomalies : [OPEN, CONFIRMED, REOPENED, RESOLVED, CLOSED]
     * Type : statuses, statusesMin et statusesAll
     */
    $typeStatuses=static::$statuses;
    $url1 = "$tempoUrlLong$mavenKey&facets=directories,types,severities&p=1&ps=1&statuses=$typeStatuses";

    /** On récupère le total de la Dette technique pour les BUG. */
    $url2 = "$tempoUrlLong$mavenKey&types=BUG&p=1&ps=1";

    /** On récupère le total de la Dette technique pour les VULNERABILITY. */
    $url3 = "$tempoUrlLong$mavenKey&types=VULNERABILITY&p=1&ps=1";

    /** On récupère le total de la Dette technique pour les CODE_SMELL. */
    $url4 = "$tempoUrlLong$mavenKey&types=CODE_SMELL&p=1&ps=1";

    /** On appel le client http pour les requête 1 à 4 (2 à 4 pour la dette). */
    $result1 = $client->http(trim(preg_replace(static::$regex, " ", $url1)));
    $result2 = $client->http($url2);
    $result3 = $client->http($url3);
    $result4 = $client->http($url4);

    if ($result1["paging"]["total"] != 0) {
      //** On supprime  les enregistrement correspondant à la clé. */
      $sql = "DELETE FROM anomalie WHERE maven_key='$mavenKey'";
      if ($mode!=="TEST"){
        $this->em->getConnection()->prepare($sql)->executeQuery();
      }

      /** nom du projet. */
      $app = explode(":", $mavenKey);

      $anomalieTotal = $result1["total"];
      $detteMinute = $result1["effortTotal"];
      $dette = $dateTools->minutesTo($detteMinute);
      $detteReliabilityMinute = $result2["effortTotal"];
      $detteReliability = $dateTools->minutesTo($detteReliabilityMinute);
      $detteVulnerabilityMinute = $result3["effortTotal"];
      $detteVulnerability = $dateTools->minutesTo($detteVulnerabilityMinute);
      $detteCodeSmellMinute = $result4["effortTotal"];
      $detteCodeSmell = $dateTools->minutesTo($detteCodeSmellMinute);

      $facets = $result1["facets"];
      /** Modules. */
      $frontend=$backend=$autre=$nombreAnomalie= 0;
      foreach ($facets as $facet) {
        $nombreAnomalie++;
        /** On récupère le nombre de signalement par sévérité. */
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
                default: $this->logger->INFO("Référentiel severité !");
              }
            }
        }
        /** On récupère le nombre de signalement par type. */
        if ($facet["property"] == "types") {
          foreach ($facet["values"] as $type) {
            switch ($type["val"]) {
              case "BUG" : $bug = $type["count"];
                    break;
              case "VULNERABILITY" : $vulnerability = $type["count"];
                    break;
              case "CODE_SMELL" : $codeSmell = $type["count"];
                    break;
            default: $this->logger->INFO("Référentiel Type !");
            }
          }
        }
        /** On récupère le nombre de signalement par module. */
        if ($facet["property"] == "directories") {
          foreach ($facet["values"] as $directory) {
            $file = str_replace($mavenKey . ":", "", $directory["val"]);
            $module = explode("/", $file);
            if ($module[0]==="du-presentation" || $module[0]==="rs-presentation"){
                $frontend = $frontend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-presentation" || $module[0]===$app[1] . "-presentation-commun" ||
                $module[0]===$app[1] . "-presentation-ear" || $module[0]===$app[1] . "-webapp"){
                $frontend = $frontend + 1;
            }
            if ($module[0]==="rs-metier"){
              $backend = $backend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-metier" || $module[0]===$app[1] . "-common" ||
                $module[0]===$app[1] . "-api" || $module[0]===$app[1] . "-dao"){
                  $backend = $backend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-metier-ear" || $module[0]===$app[1] . "-service" ||
                $module[0]===$app[1] . "-serviceweb" || $module[0]===$app[1] . "-middleoffice"){
                  $backend = $backend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-metier-rest" || $module[0]===$app[1] . "-entite" ||
                $module[0]===$app[1] . "-serviceweb-client"){
                  $backend = $backend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-batch" || $module[0]===$app[1] . "-batchs" ||
                $module[0]===$app[1] . "-batch-envoi-dem-aval" || $module[0]===$app[1] . "-batch-import-billets"){
                  $autre = $autre + $directory["count"];
            }
            if ($module[0]===$app[1] . "-rdd") {
              $autre = $autre + $directory["count"];
            }
          }
        }
      }
      /** Enregistrement dans la table Anomalie. */
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
      if ($mode!=="TEST") {
        $this->em->flush();
      }
    }

    $info = "Enregistrement des défauts (" . $nombreAnomalie . ") correctement effectué.";
    return $response->setData(["mode"=>$mode, "info" => $info, Response::HTTP_OK]);
  }


  /**
   * [Description for projetAnomalieDetails]
   * Récupère le détails des sévérités pour chaque type
   * https://{URL}/api/issues/search?componentKeys={key}&&facets=severities&types=BUG&ps=1&p=1&statuses=OPEN
   * https://{URL}/api/issues/search?componentKeys={key}&&facets=severities&types=VULNERABILITY&ps=1&p=1&statuses=OPEN
   * https://{URL}/api/issues/search?componentKeys={key}&&facets=severities&types=CODE_SMELLBUG&ps=1&p=1&statuses=OPEN
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:35:00 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/anomalie/details', name: 'projet_anomalie_details', methods: ['GET'])]
  public function projetAnomalieDetails(Request $request, Client $client): response
  {

    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $mavenKey = $request->get('mavenKey');
    $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;
    $mode = $request->get('mode');

    /** On teste si la clé est valide */
    if ($mavenKey==="null" && $mode==="TEST") {
      return $response->setData([
        "mode"=>$mode, "mavenKey"=>$mavenKey,
        "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    /** On vérifie si l'utilisateur à un rôle Collecte ? */
    if (!$this->isGranted('ROLE_COLLECTE')){
      return $response->setData([
        "mode"=>$mode ,
        "type"=>'alert',
        "reference" => static::$reference,
        "message"=> static::$message,
        Response::HTTP_OK]);
    }

    /** Pour les Bug. */
    $url1 = "$tempoUrlLong$mavenKey&facets=severities&types=BUG&ps=1&p=1&statuses=OPEN";

    /** Pour les Vulnérabilités. */
    $url2 = "$tempoUrlLong$mavenKey&facets=severities&types=VULNERABILITY&ps=1&p=1&statuses=OPEN";

    /** Pour les mauvaises pratiques. */
    $url3 = "$tempoUrlLong$mavenKey&facets=severities&types=CODE_SMELL&ps=1&p=1&statuses=OPEN";

    /** On appel le client http pour les requête 1 à 3. */
    $result1 = $client->http($url1);
    $result2 = $client->http($url2);
    $result3 = $client->http($url3);

    $total1 = $result1["paging"]["total"];
    $total2 = $result2["paging"]["total"];
    $total3 = $result3["paging"]["total"];

    if ($total1 !== 0 || $total2 !== 0 || $total3 !== 0) {
      /** On supprime  l'enregistrement correspondant à la clé. */
      $sql = "DELETE FROM anomalie_details WHERE maven_key='$mavenKey'";
      if ($mode!=="TEST"){
        $this->em->getConnection()->prepare($sql)->executeQuery();
      }

      $date = new DateTime();
      $date->setTimezone(new DateTimeZone(static::$europeParis));
      $r1 = $result1["facets"];
      $r2 = $result2["facets"];
      $r3 = $result3["facets"];

      foreach ($r1[0]["values"] as $severity) {
        if ($severity["val"] === "BLOCKER") {
          $bugBlocker = $severity["count"];
        }
        if ($severity["val"] === "CRITICAL") {
          $bugCritical = $severity["count"];
        }
        if ($severity["val"] === "MAJOR") {
          $bugMajor = $severity["count"];
        }
        if ($severity["val"] === "MINOR") {
          $bugMinor = $severity["count"];
        }
        if ($severity["val"] === "INFO") {
          $bugInfo = $severity["count"];
        }
      }

      foreach ($r2[0]["values"] as $severity) {
        if ($severity["val"] === "BLOCKER") {
          $vulnerabilityBlocker = $severity["count"];
        }
        if ($severity["val"] === "CRITICAL") {
          $vulnerabilityCritical = $severity["count"];
        }
        if ($severity["val"] === "MAJOR") {
          $vulnerabilityMajor = $severity["count"];
        }
        if ($severity["val"] === "MINOR") {
          $vulnerabilityMinor = $severity["count"];
        }
        if ($severity["val"] === "INFO") {
          $vulnerabilityInfo = $severity["count"];
        }
      }

      foreach ($r3[0]["values"] as $severity) {
        if ($severity["val"] === "BLOCKER") {
          $codeSmellBlocker = $severity["count"];
        }
        if ($severity["val"] === "CRITICAL") {
          $codeSmellCritical = $severity["count"];
        }
        if ($severity["val"] === "MAJOR") {
          $codeSmellMajor = $severity["count"];
        }
        if ($severity["val"] === "MINOR") {
          $codeSmellMinor = $severity["count"];
        }
        if ($severity["val"] === "INFO") {
          $codeSmellInfo = $severity["count"];
        }
      }

      /** On récupère le nom de l'application. */
      $explode = explode(":", $mavenKey);
      $name = $explode[1];

      /** On enregistre en base. */
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
      $this->em->persist($details);

      /** On catch l'erreur sur la clé composite : maven_key, version, date_version. */
      try {
        if ($mode!=="TEST") {
          $this->em->flush();
        }
      } catch (\Doctrine\DBAL\Exception $e) {
        return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
      }
    }
    return $response->setData(["mode"=>$mode, "code" => "OK", Response::HTTP_OK]);
  }

}
