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

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

// Accès aux tables SLQLite
use App\Entity\Main\InformationProjet;
use App\Entity\Main\NoSonar;
use App\Entity\Main\Mesures;
use App\Entity\Main\Anomalie;
use App\Entity\Main\AnomalieDetails;
use App\Entity\Main\Owasp;
use App\Entity\Main\Hotspots;
use App\Entity\Main\HotspotOwasp;
use App\Entity\Main\HotspotDetails;
use App\Entity\Main\Historique;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use DateTime;

// Logger
use Psr\Log\LoggerInterface;

class ApiProjetController extends AbstractController
{

  private $client;
  private $logger;
  private $em;

  public function __construct(
    HttpClientInterface $client,
    LoggerInterface $logger,
    EntityManagerInterface $em)
  {
    $this->client = $client;
    $this->logger = $logger;
    $this->em = $em;
  }

  public static $strContentType = 'application/json';
  public static $dateFormat = "Y-m-d H:m:s";
  public static $sonarUrl = "sonar.url";
  public static $apiIssuesSearch = "/api/issues/search?componentKeys=";

  /**
   * date_to_minute
   * Fonction privée pour convertir une date au format xxd aah xxmin en minutes
   *
   * @param  mixed $str
   * @return ($jour * 24 * 60) + ($heure * 60) + intval($minute)
   */
  protected function date_to_minute($str)
  {
    $jour = 0;
    $heure = 0;
    $minute = 0;
    //[2d1h1min]-- >[2] [1h1min]
    $j = explode('d', $str);
    if (count($j) == 1) {
      $h = explode('h', $j[0]);
    }
    if (count($j) == 2) {
      $jour = $j[0];
      $h = explode('h', $j[1]);
    }

    //heure [1], [1min]
    if (count($h) == 1) {
      $m = explode('min', $h[0]);
    }
    if (count($h) == 2) {
      $heure = $h[0];
      $m = explode('min', $h[1]);
    }

    //minute
    if (count($m) == 1) {
      $m = explode('min', $j[0]);
    }
    if (count($m) == 2) {
      $mm = explode('min', $m[0]);
      $minute = $mm[0];
    }

    return ($jour * 24 * 60) + ($heure * 60) + intval($minute);
  }

  /**
   * minutes_to
   * Converti les minutes en jours, heures et minutes
   *
   * @param  mixed $minutes
   * @return string
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
   * httpClient
   *
   * @param  mixed $url
   * @return void
   */
  protected function httpClient($url)
  {
    if (empty($this->getParameter('sonar.token'))) {
      $user = $this->getParameter('sonar.user');
      $password = $this->getParameter('sonar.password');
    } else {
      $user = $this->getParameter('sonar.token');
      $password = '';
    }

    $response = $this->client->request(
      'GET',
      $url,
      [
        'ciphers' => 'AES128-SHA AES256-SHA DH-DSS-AES128-SHA DH-DSS-AES256-SHA DH-RSA-AES128-SHA DH-RSA-AES256-SHA DHE-DSS-AES128-SHA DHE-DSS-AES256-SHA DHE-RSA-AES128-SHA DHE-RSA-AES256-SHA ADH-AES128-SHA ADH-AES256-SHA',
        'auth_basic' => [$user, $password], 'timeout' => 45,
        'headers' => [
          'Accept' => static::$strContentType,
          'Content-Type' => static::$strContentType
        ]
      ]
    );

    if (200 !== $response->getStatusCode()) {
      if ($response->getStatusCode() == 401) {
        throw new \Exception('Erreur d\'Authentification. La clé n\'est pas correcte.');
      } else {
        throw new \Exception('Retour de la réponse différent de ce qui est prévu. Erreur '
          . $response->getStatusCode());
      }
    }

    // La variable n'est pas utilisé, elle permet de collecter les données et de rendre la main.
    $contentType = $response->getHeaders()['content-type'][0];
    $this->logger->INFO('** ContentType *** ' . isset($contentType));

    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
  }

  /**
   * favori
   * Change le statut du favori pour un projet
   * http://{url}/api/favori{key}
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/favori', name: 'favori', methods: ['GET'])]
  public function favori(Request $request): response
  {
    // On bind les variables
    $mavenKey = $request->get('mavenKey');
    $statut = $request->get('statut');
    $date = new DateTime();
    $tempoDate = $date->format(static::$dateFormat);

    // On vérifie si le projet est déjà en favori
    $sql = "SELECT * FROM favori WHERE maven_key='${mavenKey}'";
    $select = $this->em->getConnection()->prepare($sql)->executeQuery();

    // Si on a pas trouvé l'application dans la liste des favoris, alors on la rajoute.
    if (empty($select->fetchAllAssociative())) {
      $sql = "INSERT INTO favori ('maven_key', 'favori', 'date_enregistrement')
        VALUES ('${mavenKey}', TRUE, '${tempoDate}')";
      $this->em->getConnection()->prepare($sql)->executeQuery();
    } else {
      $sql = "UPDATE favori SET favori='${statut}',
              date_enregistrement='${tempoDate}'
              WHERE maven_key='${mavenKey}'";
      $this->em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
    }
    $response = new JsonResponse();
    return $response->setData(["statut" => $statut, Response::HTTP_OK]);
  }

  /**
   * favori_check
   * Récupère le statut d'un favori. Le
   * favori est TRUE ou FALSE ou null
   * http://{url}/api/favori/check={key}
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/favori/check', name: 'favori_check', methods: ['GET'])]
  public function favoriCheck(EntityManagerInterface $em, Request $request): response
  {
    $mavenKey = $request->get('mavenKey');
    $response = new JsonResponse();

    // On vérifie si le projet est déjà en favori
    $sql = "SELECT favori FROM favori WHERE maven_key='${mavenKey}'";
    $select = $em->getConnection()->prepare($sql)->executeQuery();
    $r = $select->fetchAllAssociative();
    if (empty($r)) {
      return $response->setData(["statut" => "null", Response::HTTP_OK]);
    }

    return $response->setData(["favori" => "TRUE", "statut" => $r[0]['favori'], Response::HTTP_OK]);
  }

  /**
   * liste_projet
   * Récupère la liste des projets nom + clé
   * http://{url}}/api/liste/projet
   *
   * @param  mixed $connection
   * @return response
   */
  #[Route('/api/liste/projet', name: 'liste_projet', methods: ['GET'])]
  public function liste_projet(Connection $connection): response
  {

    $sql = "SELECT maven_key, name from 'liste_projet'";
    $rqt = $connection->fetchAllNumeric($sql);

    if (!$rqt) {
      throw $this->createNotFoundException('Oops - Il y a un problème.');
    }

    $liste = [];
    // objet = { id: clé, text: "blablabla" };
    foreach ($rqt as $value) {
      $objet = ['id' => $value[0], 'text' => $value[1]];
      array_push($liste, $objet);
    }

    $response = new JsonResponse();
    return $response->setData(["liste" => $liste, Response::HTTP_OK]);
  }

  /**
   * projet_analyses
   * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version).
   * http://{url}/api/project_analyses/search?project={key}
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/analyses', name: 'projet_analyses', methods: ['GET'])]
  public function projetAnalyses(Request $request): response
  {
    $url = $this->getParameter(static::$sonarUrl) .
      "/api/project_analyses/search?project=" . $request->get('mavenKey');

    // On appel le client http
    $result = $this->httpClient($url);
    // On récupère le manager de BD
    $date = new DateTime();
    $mavenKey = $request->get('mavenKey');

    // On supprime les informations sur le projet
    $sql = "DELETE FROM information_projet WHERE maven_key='$mavenKey'";
    $this->em->getConnection()->prepare($sql)->executeQuery();

    // On ajoute les informations du projets dans la table information_projet.
    $nombreVersion = 0;

    foreach ($result["analyses"] as $analyse) {
      $nombreVersion++;
      /**
       *  La version du projet doit être xxx-release, xxx-snapshot ou xxx
       *  Dans ce cas le tableau renvoi toujours [0] pour la version et
       *  [1] pour le type de version (release, snaphot ou null)
       */
      $explode = explode("-", $analyse["projectVersion"]);
      if (empty($explode[1])) {
        $explode[1] = 'N.C';
      }

      $informationProjet = new InformationProjet();
      $informationProjet->setMavenKey($mavenKey);
      $informationProjet->setAnalyseKey($analyse["key"]);
      $informationProjet->setDate(new DateTime($analyse["date"]));
      $informationProjet->setProjectVersion($analyse["projectVersion"]);
      $informationProjet->setType(strtoupper($explode[1]));
      $informationProjet->setDateEnregistrement($date);
      $this->em->persist($informationProjet);
      $this->em->flush();
    }
    $response = new JsonResponse();
    return $response->setData(["nombreVersion" => $nombreVersion, Response::HTTP_OK]);
  }

  /**
   * projet_mesures
   * Récupère les indicateurs de mesures
   * http://{url}/api/components/app?component={key}
   * http://{URL}/api/measures/component?component={key}&metricKeys=ncloc
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/mesures', name: 'projet_mesures', methods: ['GET'])]
  public function projetMesures(Request $request): response
  {
    // On bind les variables
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $mavenKey = $request->get('mavenKey');

    // mesures globales
    $url1 = "${tempoUrl}/api/components/app?component=${mavenKey}";

    // on appel le client http
    $result1 = $this->httpClient($url1);
    $date = new DateTime();

    // On ajoute les mesures dans la table mesures.
    if (intval($result1["measures"]["lines"])) {
      $lines = intval($result1["measures"]["lines"]);
    } else {
      $lines = 0;
    }

    //Warning: Undefined array key "coverage"
    if (array_key_exists("coverage", $result1["measures"])) {
      $coverage = $result1["measures"]["coverage"];
    } else {
      $coverage = 0;
    }

    if (array_key_exists("duplicationDensity", $result1["measures"])) {
      $duplicationDensity = $result1["measures"]["duplicationDensity"];
    } else {
      $duplicationDensity = 0;
    }

    if (array_key_exists("tests", $result1["measures"])) {
      $tests = intval($result1["measures"]["tests"]);
    } else {
      $tests = 0;
    }

    if (array_key_exists("issues", $result1["measures"])) {
      $issues = intval($result1["measures"]["issues"]);
    } else {
      $issues = 0;
    }

    // On récupère le nombre de ligne de code
    $url2 = "${tempoUrl}/api/measures/component?component=${mavenKey}&metricKeys=ncloc";
    $result2 = $this->httpClient($url2);

    if (array_key_exists("measures", $result2["component"])) {
      $ncloc = intval($result2["component"]["measures"][0]["value"]);
    } else {
      $ncloc = 0;
    }
    // On enregistre
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

    $response = new JsonResponse();
    return $response->setData([Response::HTTP_OK]);
  }

  /**
   * projet_anomalie
   * Récupère le total des anomalies, avec un filtre par répertoire, sévérité et types.
   * https://{URL}/api/issues/search?componentKeys={mavenKey}&facets=directories,types,severities&p=1&ps=1&statuses=OPEN
   * https://{URL}/api/issues/search?componentKeys={mavenKey}&types={type}&p=1&ps=1
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/anomalie', name: 'projet_anomalie', methods: ['GET'])]
  public function projetAnomalie(Request $request): response
  {
    // On bind les variables
    $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;
    $mavenKey = $request->get('mavenKey');

    // On créé un objet date
    $date = new DateTime();

    $statusesMin = "OPEN,CONFIRMED,REOPENED,RESOLVED";
    $statusesAll = "OPEN,CONFIRMED,REOPENED,RESOLVED,TO_REVIEW,IN_REVIEW";

    $url1 = "${tempoUrlLong}${mavenKey}&facets=directories,types,severities&p=1&ps=1&statuses=${statusesMin}";

    // On récupère le total de la Dette technique pour les BUG
    $url2 = "${tempoUrlLong}${mavenKey}&types=BUG&p=1&ps=1";

    // On récupère le total de la Dette technique pour les VULNERABILITY
    $url3 = "${tempoUrlLong}${mavenKey}&types=VULNERABILITY&p=1&ps=1";

    // On récupère le total de la Dette technique pour les CODE_SMELL
    $url4 = "${tempoUrlLong}${mavenKey}&types=CODE_SMELL&p=1&ps=1";

    // on appel le client http pour les requête 1 à 4 (2 à 4 pour la dette)
    $result1 = $this->httpClient(trim(preg_replace("/\s+/u", " ", $url1)));
    $result2 = $this->httpClient($url2);
    $result3 = $this->httpClient($url3);
    $result4 = $this->httpClient($url4);

    if ($result1["paging"]["total"] != 0) {
      // On supprime  les enregistrement correspondant à la clé
      $sql = "DELETE FROM anomalie WHERE maven_key='${mavenKey}'";
      $this->em->getConnection()->prepare($sql)->executeQuery();

      // nom du projet
      $app = explode(":", $mavenKey);

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
      // modules
      $frontend = 0;
      $backend = 0;
      $autre = 0;
      $nombreAnomalie = 0;
      foreach ($facets as $facet) {
        $nombreAnomalie++;
        if ($facet["property"] == "severities") {
          foreach ($facet["values"] as $severity) {
            if ($severity["val"] == "BLOCKER") {
              $blocker = $severity["count"];
            }
            if ($severity["val"] == "CRITICAL") {
              $critical = $severity["count"];
            }
            if ($severity["val"] == "MAJOR") {
              $major = $severity["count"];
            }
            if ($severity["val"] == "INFO") {
              $info = $severity["count"];
            }
            if ($severity["val"] == "MINOR") {
              $minor = $severity["count"];
            }
          }
        }
        if ($facet["property"] == "types") {
          foreach ($facet["values"] as $type) {
            if ($type["val"] == "BUG") {
              $bug = $type["count"];
            }
            if ($type["val"] == "VULNERABILITY") {
              $vulnerability = $type["count"];
            }
            if ($type["val"] == "CODE_SMELL") {
              $codeSmell = $type["count"];
            }
          }
        }
        if ($facet["property"] == "directories") {
          foreach ($facet["values"] as $directory) {
            $file = str_replace($mavenKey . ":", "", $directory["val"]);
            $module = explode("/", $file);

            /* Cas particulier pour l'application RS et DU
             * Le nom du projet ne correspond pas à l'artifactId du module
             * Par exemple la clé maven it.cool:monapplication et un module de
             * type : cool-presentation au lieu de monapplication-presentation
             */
            if ($module[0] == "du-presentation") {
              $frontend = $frontend + $directory["count"];
            }
            if ($module[0] == "rs-presentation") {
              $frontend = $frontend + $directory["count"];
            }
            if ($module[0] == "rs-metier") {
              $backend = $backend + $directory["count"];
            }

            /**
             *  Application Frontend
             */
            if ($module[0] == $app[1] . "-presentation") {
              $frontend = $frontend + $directory["count"];
            }
            // Application : Legacy
            if ($module[0] == $app[1] . "-presentation-commun") {
              $frontend = $frontend + $directory["count"];
            }
            if ($module[0] == $app[1] . "-presentation-ear") {
              $frontend = $frontend + $directory["count"];
            }
            if ($module[0] == $app[1] . "-webapp") {
              $frontend = $frontend + $directory["count"];
            }

            /**
             * Application Backend
             */
            if ($module[0] == $app[1] . "-metier") {
              $backend = $backend + $directory["count"];
            }
            if ($module[0] == $app[1] . "-common") {
              $backend = $backend + $directory["count"];
            }
            if ($module[0] == $app[1] . "-api") {
              $backend = $backend + $directory["count"];
            }
            if ($module[0] == $app[1] . "-dao") {
              $backend = $backend + $directory["count"];
            }
            if ($module[0] == $app[1] . "-metier-ear") {
              $backend = $backend + $directory["count"];
            }
            if ($module[0] == $app[1] . "-service") {
              $backend = $backend + $directory["count"];
            }
            // Application : Legacy
            if ($module[0] == $app[1] . "-serviceweb") {
              $backend = $backend + $directory["count"];
            }
            // Application : Dénormaliser
            if ($module[0] == $app[1] . "-middleoffice") {
              $backend = $backend + $directory["count"];
            }
            // Application : Starter-Kit
            if ($module[0] == $app[1] . "-metier-rest") {
              $backend = $backend + $directory["count"];
            }
            // Application : Legacy
            if ($module[0] == $app[1] . "-entite") {
              $backend = $backend + $directory["count"];
            }
            // Application : Legacy
            if ($module[0] == $app[1] . "-serviceweb-client") {
              $backend = $backend + $directory["count"];
            }

            /**
             * Application Batch et Autres
             */
            if ($module[0] == $app[1] . "-batch") {
              $autre = $autre + $directory["count"];
            }
            if ($module[0] == $app[1] . "-batchs") {
              $autre = $autre + $directory["count"];
            }
            if ($module[0] == $app[1] . "-batch-envoi-dem-aval") {
              $autre = $autre + $directory["count"];
            }
            if ($module[0] == $app[1] . "-batch-import-billets") {
              $autre = $autre + $directory["count"];
            }
            if ($module[0] == $app[1] . "-rdd") {
              $autre = $autre + $directory["count"];
            }
          }
        }
      }
      // Enregistrement dans la table Anomalie
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
      $issue->setListe('TRUE');
      $issue->setDateEnregistrement($date);
      $this->em->persist($issue);
      $this->em->flush();
    }
    $info = "Enregistrement des défauts (" . $nombreAnomalie . ") correctement effectué.";

    $response = new JsonResponse();
    return $response->setData(["info" => $info, Response::HTTP_OK]);
  }


  /**
   * description
   */
  /**
   * projet_anomalie_details
   * Récupère le détails des sévérités pour chaque type
   * https://{URL}/api/issues/search?componentKeys={key}&&facets=severities&types=BUG&ps=1&p=1&statuses=OPEN
   * https://{URL}/api/issues/search?componentKeys={key}&&facets=severities&types=VULNERABILITY&ps=1&p=1&statuses=OPEN
   * https://{URL}/api/issues/search?componentKeys={key}&&facets=severities&types=CODE_SMELLBUG&ps=1&p=1&statuses=OPEN
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/anomalie/details', name: 'projet_anomalie_details', methods: ['GET'])]
  public function projetAnomalieDetails(Request $request): response
  {
    // On bind les variables
    $mavenKey = $request->get('mavenKey');
    $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;

    // on créé un objet JSON
    $response = new JsonResponse();

    // Pour les Bug
    $url1 = "${tempoUrlLong}${mavenKey}&facets=severities&types=BUG&ps=1&p=1&statuses=OPEN";

    // Pour les Vulnérabilités
    $url2 = "${tempoUrlLong}${mavenKey}&facets=severities&types=VULNERABILITY&ps=1&p=1&statuses=OPEN";

    // Pour les mauvaises pratiques
    $url3 = "${tempoUrlLong}${mavenKey}&facets=severities&types=CODE_SMELL&ps=1&p=1&statuses=OPEN";

    // On appel le client http pour les requête 1 à 3
    $result1 = $this->httpClient($url1);
    $result2 = $this->httpClient($url2);
    $result3 = $this->httpClient($url3);

    $total1 = $result1["paging"]["total"];
    $total2 = $result2["paging"]["total"];
    $total3 = $result3["paging"]["total"];

    if ($total1 !== 0 || $total2 !== 0 || $total3 !== 0) {
      // On supprime  l'enregistrement correspondant à la clé
      $sql = "DELETE FROM anomalie_details WHERE maven_key='${mavenKey}'";
      $this->em->getConnection()->prepare($sql)->executeQuery();

      $date = new DateTime();
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

      // On récupère le nom de l'application
      $explode = explode(":", $mavenKey);
      $name = $explode[1];

      // On enregistre en base
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

      // On catch l'erreur sur la clé composite : maven_key, version, date_version
      try {
        $this->em->flush();
      } catch (\Doctrine\DBAL\Exception $e) {
        return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
      }
    }
    return $response->setData(["code" => "OK", Response::HTTP_OK]);
  }

  /**
   * historique_note_ajout
   * Récupère les notes pour la fiabilité, la sécurité et les mauvaises pratiques.
   * http://{url}https://{url}/api/measures/search_history?component={key}}&metrics={type}&ps=1000
   * On récupère que la première page soit 1000 résultat max.
   * Les valeurs possibles pour {type} sont : reliability_rating,security_rating,sqale_rating
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/historique/note', name: 'projet_historique_note', methods: ['GET'])]
  public function historiqueNoteAjout(Request $request): response
  {

    // On bind les variables
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $mavenKey = $request->get('mavenKey');
    $type = $request->get('type');

    $url = "${tempoUrl}/api/measures/search_history?component=${mavenKey}
            &metrics=${type}_rating&ps=1000";

    // on appel le client http
    $result = $this->httpClient(trim(preg_replace("/\s+/u", " ", $url)));

    $date = new DateTime();
    $tempoDate = $date->format(static::$dateFormat);
    $nombre = $result["paging"]["total"];
    $mesures = $result["measures"][0]["history"];

    // Enregistrement des nouvelles valeurs
    foreach ($mesures as $mesure) {
      $tempoMesureDate = $mesure["date"];
      $tempoMesureValue = $mesure["value"];
      $sql = "INSERT OR IGNORE INTO notes (maven_key, type, date, value, date_enregistrement)
              VALUES ('${mavenKey}', '${type}', '${tempoMesureDate}', '${tempoMesureValue}', '${tempoDate}')";
      $this->em->getConnection()->prepare($sql)->executeQuery();
    }

    if ($request->get('type') == "reliability") {
      $type = "Fiabilité";
    }
    if ($request->get('type') == "security") {
      $type = "Sécurité";
    }
    if ($request->get('type') == "sqale") {
      $type = "Mauvaises Pratiques";
    }

    $response = new JsonResponse();
    return $response->setData(["nombre" => $nombre, "type" => $type, Response::HTTP_OK]);
  }

  /**
   * issues_owasp_ajout
   * Récupère le top 10 OWASP
   * http://{url}/api/issues/search?componentKeys={key}&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10
   * Attention une faille peut être comptée deux fois ou plus, cela dépend du tag.
   * Donc il  est possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/issues/owasp', name: 'projet_issues_owasp', methods: ['GET'])]
  public function issuesOwaspAjout(Request $request): response
  {
    // On bind les variables
    $mavenKey = $request->get('mavenKey');
    $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;

    // URL de l'appel
    $url = "${tempoUrlLong}${mavenKey}&facets=owaspTop10
            &owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10";

    // On appel l'API
    $result = $this->httpClient(trim(preg_replace("/\s+/u", " ", $url)));

    $date = new DateTime();
    $owasp = [$result["total"]];
    $effortTotal = $result["effortTotal"];

    for ($a = 0; $a < 10; $a++) {
      switch ($result["facets"][0]["values"][$a]["val"]) {
        case 'a1':
          $owasp[1] = $result["facets"][0]["values"][$a]["count"];
          break;
        case 'a2':
          $owasp[2] = $result["facets"][0]["values"][$a]["count"];
          break;
        case 'a3':
          $owasp[3] = $result["facets"][0]["values"][$a]["count"];
          break;
        case 'a4':
          $owasp[4] = $result["facets"][0]["values"][$a]["count"];
          break;
        case 'a5':
          $owasp[5] = $result["facets"][0]["values"][$a]["count"];
          break;
        case 'a6':
          $owasp[6] = $result["facets"][0]["values"][$a]["count"];
          break;
        case 'a7':
          $owasp[7] = $result["facets"][0]["values"][$a]["count"];
          break;
        case 'a8':
          $owasp[8] = $result["facets"][0]["values"][$a]["count"];
          break;
        case 'a9':
          $owasp[9] = $result["facets"][0]["values"][$a]["count"];
          break;
        case 'a10':
          $owasp[10] = $result["facets"][0]["values"][$a]["count"];
          break;
        default:
          echo "OWASP TOP 10";
          break;
      }
    }

    $a1Blocker = 0;
    $a1Critical = 0;
    $a1Major = 0;
    $a1Info = 0;
    $a1Minor = 0;
    $a2Blocker = 0;
    $a2Critical = 0;
    $a2Major = 0;
    $a2Info = 0;
    $a2Minor = 0;
    $a3Blocker = 0;
    $a3Critical = 0;
    $a3Major = 0;
    $a3Info = 0;
    $a3Minor = 0;
    $a4Blocker = 0;
    $a4Critical = 0;
    $a4Major = 0;
    $a4Info = 0;
    $a4Minor = 0;
    $a5Blocker = 0;
    $a5Critical = 0;
    $a5Major = 0;
    $a5Info = 0;
    $a5Minor = 0;
    $a6Blocker = 0;
    $a6Critical = 0;
    $a6Major = 0;
    $a6Info = 0;
    $a6Minor = 0;
    $a7Blocker = 0;
    $a7Critical = 0;
    $a7Major = 0;
    $a7Info = 0;
    $a7Minor = 0;
    $a8Blocker = 0;
    $a8Critical = 0;
    $a8Major = 0;
    $a8Info = 0;
    $a8Minor = 0;
    $a9Blocker = 0;
    $a9Critical = 0;
    $a9Major = 0;
    $a9Info = 0;
    $a9Minor = 0;
    $a10Blocker = 0;
    $a10Critical = 0;
    $a10Major = 0;
    $a10Info = 0;
    $a10Minor = 0;

    if ($result["total"] != 0) {
      foreach ($result["issues"] as $issue) {
        $severity = $issue["severity"];
        if (
          $issue["status"] == 'OPEN' ||
          $issue["status"] == 'CONFIRMED' ||
          $issue["status"] == 'REOPENED'
        ) {
          $tagMatch = preg_match("/owasp-a/is", var_export($issue["tags"], true));
          if ($tagMatch != 0) {
            foreach ($issue["tags"] as $tag) {
              switch ($tag) {
                case "owasp-a1":
                  if ($severity == 'BLOCKER') {
                    $a1Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a1Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a1Major++;
                  }
                  if ($severity == 'INFO') {
                    $a1Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a1Minor++;
                  }
                  break;
                case "owasp-a2":
                  if ($severity == 'BLOCKER') {
                    $a2Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a2Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a2Major++;
                  }
                  if ($severity == 'INFO') {
                    $a2Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a2Minor++;
                  }
                  break;
                case "owasp-a3":
                  if ($severity == 'BLOCKER') {
                    $a3Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a3Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a3Major++;
                  }
                  if ($severity == 'INFO') {
                    $a3Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a3Minor++;
                  }
                  break;
                case "owasp-a4":
                  if ($severity == 'BLOCKER') {
                    $a4Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a4Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a4Major++;
                  }
                  if ($severity == 'INFO') {
                    $a4Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a4Minor++;
                  }
                  break;
                case "owasp-a5":
                  if ($severity == 'BLOCKER') {
                    $a5Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a5Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a5Major++;
                  }
                  if ($severity == 'INFO') {
                    $a5Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a5Minor++;
                  }
                  break;
                case "owasp-a6":
                  if ($severity == 'BLOCKER') {
                    $a6Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a6Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a6Major++;
                  }
                  if ($severity == 'INFO') {
                    $a6Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a6Minor++;
                  }
                  break;
                case "owasp-a7":
                  if ($severity == 'BLOCKER') {
                    $a7Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a7Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a7Major++;
                  }
                  if ($severity == 'INFO') {
                    $a7Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a7Minor++;
                  }
                  break;
                case "owasp-a8":
                  if ($severity == 'BLOCKER') {
                    $a8Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a8Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a8Major++;
                  }
                  if ($severity == 'INFO') {
                    $a8Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a8Minor++;
                  }
                  break;
                case "owasp-a9":
                  if ($severity == 'BLOCKER') {
                    $a9Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a9Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a9Major++;
                  }
                  if ($severity == 'INFO') {
                    $a9Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a9Minor++;
                  }
                  break;
                case "owasp-a10":
                  if ($severity == 'BLOCKER') {
                    $a10Blocker++;
                  }
                  if ($severity == 'CRITICAL') {
                    $a10Critical++;
                  }
                  if ($severity == 'MAJOR') {
                    $a10Major++;
                  }
                  if ($severity == 'INFO') {
                    $a10Info++;
                  }
                  if ($severity == 'MINOR') {
                    $a10Minor++;
                  }
                  break;
                default:
                  break;
              }
            }
          }
        }
      }
    }

    // On supprime les informations sur le projet
    $sql = "DELETE FROM owasp WHERE maven_key='${mavenKey}'";
    $this->em->getConnection()->prepare($sql)->executeQuery();

    // Enregistre en base
    $owaspTop10 = new Owasp();
    $owaspTop10->setMavenKey($request->get("mavenKey"));
    $owaspTop10->setEffortTotal($effortTotal);
    $owaspTop10->setA1($owasp[1]);
    $owaspTop10->setA2($owasp[2]);
    $owaspTop10->setA3($owasp[3]);
    $owaspTop10->setA4($owasp[4]);
    $owaspTop10->setA5($owasp[5]);
    $owaspTop10->setA6($owasp[6]);
    $owaspTop10->setA7($owasp[7]);
    $owaspTop10->setA8($owasp[8]);
    $owaspTop10->setA9($owasp[9]);
    $owaspTop10->setA10($owasp[10]);

    $owaspTop10->setA1Blocker($a1Blocker);
    $owaspTop10->setA1Critical($a1Critical);
    $owaspTop10->setA1Major($a1Major);
    $owaspTop10->setA1Info($a1Info);
    $owaspTop10->setA1Minor($a1Minor);

    $owaspTop10->setA2Blocker($a2Blocker);
    $owaspTop10->setA2Critical($a2Critical);
    $owaspTop10->setA2Major($a2Major);
    $owaspTop10->setA2Info($a2Info);
    $owaspTop10->setA2Minor($a2Minor);

    $owaspTop10->setA3Blocker($a3Blocker);
    $owaspTop10->setA3Critical($a3Critical);
    $owaspTop10->setA3Major($a3Major);
    $owaspTop10->setA3Info($a3Info);
    $owaspTop10->setA3Minor($a3Minor);

    $owaspTop10->setA4Blocker($a4Blocker);
    $owaspTop10->setA4Critical($a4Critical);
    $owaspTop10->setA4Major($a4Major);
    $owaspTop10->setA4Info($a4Info);
    $owaspTop10->setA4Minor($a4Minor);

    $owaspTop10->setA5Blocker($a5Blocker);
    $owaspTop10->setA5Critical($a5Critical);
    $owaspTop10->setA5Major($a5Major);
    $owaspTop10->setA5Info($a5Info);
    $owaspTop10->setA5Minor($a5Minor);

    $owaspTop10->setA6Blocker($a6Blocker);
    $owaspTop10->setA6Critical($a6Critical);
    $owaspTop10->setA6Major($a6Major);
    $owaspTop10->setA6Info($a6Info);
    $owaspTop10->setA6Minor($a6Minor);

    $owaspTop10->setA7Blocker($a7Blocker);
    $owaspTop10->setA7Critical($a7Critical);
    $owaspTop10->setA7Major($a7Major);
    $owaspTop10->setA7Info($a7Info);
    $owaspTop10->setA7Minor($a7Minor);

    $owaspTop10->setA8Blocker($a8Blocker);
    $owaspTop10->setA8Critical($a8Critical);
    $owaspTop10->setA8Major($a8Major);
    $owaspTop10->setA8Info($a8Info);
    $owaspTop10->setA8Minor($a8Minor);

    $owaspTop10->setA9Blocker($a9Blocker);
    $owaspTop10->setA9Critical($a9Critical);
    $owaspTop10->setA9Major($a9Major);
    $owaspTop10->setA9Info($a9Info);
    $owaspTop10->setA9Minor($a9Minor);

    $owaspTop10->setA10Blocker($a10Blocker);
    $owaspTop10->setA10Critical($a10Critical);
    $owaspTop10->setA10Major($a10Major);
    $owaspTop10->setA10Info($a10Info);
    $owaspTop10->setA10Minor($a10Minor);

    $owaspTop10->setDateEnregistrement($date);
    $this->em->persist($owaspTop10);
    $this->em->flush();

    $response = new JsonResponse();
    return $response->setData(["owasp" => $result["total"], Response::HTTP_OK]);
  }

  /**
   * hotspot_ajout
   * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
   * http://{url}/api/hotspots/search?projectKey={key}&ps=500&p=1
   * On récupère les failles a examiner.
   * Les clés sont uniques (i.e. on ne se base pas sur les tags).
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/hotspot', name: 'projet_hotspot', methods: ['GET'])]
  public function hotspotAjout(Request $request): response
  {
    // On bind les variables
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $mavenKey = $request->get('mavenKey');

    // On construit l'URL
    $url = "${tempoUrl}/api/hotspots/search?projectKey=${mavenKey}&ps=500&p=1";

    // On appel l'Api
    $result = $this->httpClient($url);

    // On créé un objet Date
    $date = new DateTime();
    $niveau = 0;

    // On supprime  les enregistrements correspondant à la clé
    $sql = "DELETE FROM hotspots WHERE maven_key='${mavenKey}'";
    $this->em->getConnection()->prepare($sql)->executeQuery();

    if ($result["paging"]["total"] != 0) {
      foreach ($result["hotspots"] as $value) {
        if ($value["vulnerabilityProbability"] == "HIGH") {
          $niveau = 1;
        }
        if ($value["vulnerabilityProbability"] == "MEDIUM") {
          $niveau = 2;
        }
        if ($value["vulnerabilityProbability"] == "LOW") {
          $niveau = 3;
        }

        $hotspot = new  Hotspots();
        $hotspot->setMavenKey($request->get('mavenKey'));
        $hotspot->setKey($value["key"]);
        $hotspot->setProbability($value["vulnerabilityProbability"]);
        $hotspot->setStatus($value["status"]);
        $hotspot->setNiveau($niveau);
        $hotspot->setDateEnregistrement($date);
        $this->em->persist($hotspot);
        $this->em->flush();
      }
    }

    $response = new JsonResponse();
    return $response->setData(
      ["hotspots" => $result["paging"]["total"], Response::HTTP_OK]
    );
  }

  /**
   * hotspot_owasp_ajout
   * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
   * http://{url}/api/hotspots/search?projectKey={key}{owasp}&ps=500&p=1
   * {key} = la clé du projet
   * {owasp} = le type de faille (a1, a2, etc...)
   * si le paramètre owasp est égale à a0 alors on supprime les enregistrements pour la clé
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/hotspot/owasp', name: 'projet_hotspot_owasp', methods: ['GET'])]
  public function hotspotOwaspAjout(Request $request): response
  {
    // On bind les variables
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $mavenKey = $request->get('mavenKey');
    $owasp = $request->get('owasp');

    $response = new JsonResponse();
    if ($request->get('owasp') == 'a0') {
      // On supprime  les enregistrements correspondant à la clé
      $sql = "DELETE FROM hotspot_owasp
              WHERE maven_key='${mavenKey}'";
      $this->em->getConnection()->prepare($sql)->executeQuery();
      return $response->setData(["info" => "effacement", Response::HTTP_OK]);
    }

    // On construit l'Url
    $url = "${tempoUrl}/api/hotspots/search?projectKey=${mavenKey}
            &owaspTop10=${owasp}&ps=500&p=1";

    // On appel l'URL
    $result = $this->httpClient(trim(preg_replace("/\s+/u", " ", $url)));

    // On créé un objet Date
    $date = new DateTime();
    $niveau = 0;

    // On fleche la vulnérabilité
    if ($result["paging"]["total"] != 0) {
      foreach ($result["hotspots"] as $value) {
        if ($value["vulnerabilityProbability"] == "HIGH") {
          $niveau = 1;
        }
        if ($value["vulnerabilityProbability"] == "MEDIUM") {
          $niveau = 2;
        }
        if ($value["vulnerabilityProbability"] == "LOW") {
          $niveau = 3;
        }

        $hotspot = new  HotspotOwasp();
        $hotspot->setMavenKey($mavenKey);
        $hotspot->setMenace($owasp);
        $hotspot->setProbability($value["vulnerabilityProbability"]);
        $hotspot->setStatus($value["status"]);
        $hotspot->setNiveau($niveau);
        $hotspot->setDateEnregistrement($date);
        $this->em->persist($hotspot);
        $this->em->flush();
      }
    } else {
      $hotspot = new  HotspotOwasp();
      $hotspot->setMavenKey($request->get('mavenKey'));
      $hotspot->setMenace($request->get('owasp'));
      $hotspot->setProbability("NC");
      $hotspot->setStatus("NC");
      $hotspot->setNiveau("0");
      $hotspot->setDateEnregistrement($date);
      $this->em->persist($hotspot);
      $this->em->flush();
    }

    return $response->setData(
      [
        "info" => "enregistrement",
        "hotspots" => $result["paging"]["total"], Response::HTTP_OK
      ]
    );
  }

  /**
   * hotspot_details
   * Fonction privée qui récupère le détail d'un hotspot en fonction de sa clé.
   *
   * @param  mixed $mavenKey
   * @param  mixed $key
   * @return array
   */
  protected function hotspotDetails($mavenKey, $key): Array
  {

    // On bind les variables
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $url = "${tempoUrl}/api/hotspots/show?hotspot=${key}";
    $hotspot = $this->httpClient($url);
    $date = new DateTime();

    // Si le niveau de sévérité n'est pas connu, on lui affecte la valeur MEDIUM.
    if (empty($hotspot["rule"]["vulnerabilityProbability"])) {
      $severity = "MEDIUM";
    } else {
      $severity = $hotspot["rule"]["vulnerabilityProbability"];
    }

    // On affecte le niveau en fonction de la sévérité
    switch ($severity) {
      case "HIGH":
        $niveau = 1;
        break;
      case "MEDIUM":
        $niveau = 2;
        break;
      case "LOW":
        $niveau = 3;
        break;
      default:
        echo "oops ! je suis perdu !!!";
    }
    $frontend = 0;
    $backend = 0;
    $autre = 0;
    // nom du projet
    $app = explode(":", $mavenKey);

    $status = $hotspot["status"];
    $file = str_replace($mavenKey . ":", "", $hotspot["component"]["key"]);
    $module = explode("/", $file);

    /* Cas particulier pour l'application RS et DU
     * Le nom du projet ne correspond pas à l'artifactId du module
     * Par exemple la clé maven it.cool:monapplication et un module de
     * type : cool-presentation au lieu de monapplication-presentation
     */
    if ($module[0] == "du-presentation") {
      $frontend++;
    }
    if ($module[0] == "rs-presentation") {
      $frontend++;
    }
    if ($module[0] == "rs-metier") {
      $backend++;
    }

    /**
     *  Application Frontend
     */
    if ($module[0] == $app[1] . "-presentation") {
      $frontend++;
    }
    if ($module[0] == $app[1] . "-presentation-commun") {
      $frontend++;
    }
    if ($module[0] == $app[1] . "-presentation-ear") {
      $frontend++;
    }
    if ($module[0] == $app[1] . "-webapp") {
      $frontend++;
    }

    /**
     * Application Backend
     */
    if ($module[0] == $app[1] . "-metier") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-common") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-api") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-dao") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-metier-ear") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-service") {
      $backend++;
    }
    // Application : Legacy
    if ($module[0] == $app[1] . "-serviceweb") {
      $backend++;
    }
    if ($module[0] == $app[1] . "-middleoffice") {
      $backend++;
    }
    // Application : Starter-Kit
    if ($module[0] == $app[1] . "-metier-rest") {
      $backend++;
    }
    // Application : Legacy
    if ($module[0] == $app[1] . "-entite") {
      $backend++;
    }
    // Application : Legacy
    if ($module[0] == $app[1] . "-serviceweb-client") {
      $backend++;
    }

    /**
     * Application Batch et Autres
     */
    if ($module[0] == $app[1] . "-batch") {
      $autre++;
    }
    if ($module[0] == $app[1] . "-batch") {
      $autre++;
    }
    if ($module[0] == $app[1] . "-batch-envoi-dem-aval") {
      $autre++;
    }
    if ($module[0] == $app[1] . "-batch-import-billets") {
      $autre++;
    }
    if ($module[0] == $app[1] . "-rdd") {
      $autre++;
    }

    if (empty($hotspot["line"])) {
      $line = 0;
    } else {
      $line = $hotspot["line"];
    }
    $rule = $hotspot["rule"] ? $hotspot["rule"]["name"] : "/";
    $message = $hotspot["message"];
    /**
     * On affiche pas la description, même si on la en base,
     * car on pointe sur le serveur sonarqube directement
     * $description=$hotspot["rule"]["riskDescription"];
     */
    $hotspotKey = $hotspot["key"];
    $dateEnregistrement = $date;

    return [
      "niveau" => $niveau,
      "severity" => $severity, "status" => $status,
      "frontend" => $frontend, "backend" => $backend,
      "autre" => $autre, "file" => $file,
      "line" => $line, "rule" => $rule,
      "message" => $message, "key" => $hotspotKey,
      "date_enregistrement" => $dateEnregistrement
    ];
  }

  /**
   * hotspot_details_ajout
   * Récupère le détails des hotspots et les enregistre dans la table Hotspots_details
   * http://{url}/api/projet/hotspot/details{maven_key};
   * {maven_key} = la clé du projet
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/hotspot/details', name: 'projet_hotspot_details', methods: ['GET'])]
  public function hotspotDetailsAjout(Request $request): response
  {
    $response = new JsonResponse();

    // On bind les variables
    $mavenKey = $request->get('mavenKey');

    // On récupère la liste des hotspots
    $sql = "SELECT * FROM hotspots
            WHERE maven_key='${mavenKey}'
            AND status='TO_REVIEW' ORDER BY niveau";

    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $liste = $r->fetchAllAssociative();

    // On supprime les données de la table hotspots_details pour le projet
    $sql = "DELETE FROM hotspot_details
            WHERE maven_key='${mavenKey}'";
    $this->em->getConnection()->prepare($sql)->executeQuery();


    // Si la liste des vide on envoi un code 406
    if (empty($liste)) {
      return $response->setData(["code" => 406, Response::HTTP_OK]);
    }

    /* On boucle sur les clés pour récupérer le détails du hotspot
     * On envoie la clé du projet et la clé du hotspot
     */
    $ligne = 0;
    foreach ($liste as $elt) {

      $ligne++;
      $key = $this->hotspotDetails($mavenKey, $elt["key"]);
      $details = new  HotspotDetails();
      $details->setMavenKey($mavenKey);
      $details->setSeverity($key["severity"]);
      $details->setNiveau($key["niveau"]);
      $details->setStatus($key["status"]);
      $details->setFrontend($key["frontend"]);
      $details->setBackend($key["backend"]);
      $details->setAutre($key["autre"]);
      $details->setFile($key["file"]);
      $details->setLine($key["line"]);
      $details->setRule($key["rule"]);
      $details->setMessage($key["message"]);
      $details->setKey($key["key"]);
      $details->setDateEnregistrement($key["date_enregistrement"]);
      $this->em->persist($details);
      $this->em->flush();
    }
    return $response->setData(["ligne" => $ligne, Response::HTTP_OK]);
  }

  /**
   * projet_nosonar_ajout
   * On récupère la liste des fichiers ayant fait l'objet d'un
   * @@supresswarning ou d'un noSONAR
   * http://{url}api/issues/search?componentKeys={key}&rules={rules}&ps=500&p=1
   * {key} = la clé du projet
   * {rules} = java:S1309 et java:NoSonar
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/projet/nosonar/details', name: 'projet_nosonar', methods: ['GET'])]
  public function projetNosonarAjout(Request $request): response
  {
    // Bind les données
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $mavenKey = $request->get('mavenKey');

    // On construit l'URL et on appel le WS
    $url = "${tempoUrl}/api/issues/search?componentKeys=${mavenKey}
            &rules=java:S1309,java:NoSonar&ps=500&p=1";

    $result = $this->httpClient(trim(preg_replace("/\s+/u", " ", $url)));
    $date = new DateTime();

    // On supprime les données du projet de la table NoSonar
    $sql = "DELETE FROM no_sonar WHERE maven_key='${mavenKey}'";
    $this->em->getConnection()->prepare($sql)->executeQuery();

    /**
     * Si on a trouvé des @notations de type nosSonar ou SuprressWarning.
     * dans le code alors on les dénombre
     */
    if ($result["paging"]["total"] !== 0) {
      foreach ($result["issues"] as $issue) {
        $nosonar = new NoSonar();
        $nosonar->setMavenKey($request->get('mavenKey'));
        $nosonar->setRule($issue["rule"]);
        $component = str_replace("${mavenKey} :", "", $issue["component"]);
        $nosonar->setComponent($component);
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
      // Il n'y a pas de noSOnar ou de suppressWarning
    }

    $response = new JsonResponse();
    return $response->setData(["nosonar" => $result["paging"]["total"], Response::HTTP_OK]);
  }

  /**
   * enregistrement
   * Enregistrement des données du projet
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/enregistrement', name: 'enregistrement', methods: ['PUT'])]
  public function enregistrement(EntityManagerInterface $em, Request $request): response
  {
    // On décode le body
    $data = json_decode($request->getContent());

    // On créé un objet response pour le retour JSON
    $response = new JsonResponse();

    // On créé un objet date, avec la date courante
    $date = new DateTime();

    // Enregistrement
    $save = new Historique();
    // Informations version
    $save->setMavenKey($data->mavenKey);
    $save->setNomProjet($data->nomProjet);
    $save->setVersionRelease($data->versionRelease);
    $save->setVersionSnapshot($data->versionSnapshot);
    $save->setVersion($data->version);
    $save->setDateVersion($data->dateVersion);

    // Informations sur les exceptions
    $save->setSuppressWarning($data->suppressWarning);
    $save->setNoSonar($data->noSonar);

    // Informations projet
    $save->setNombreLigne($data->nombreLigne);
    $save->setNombreLigneCode($data->nombreLigneDeCode);
    $save->setCouverture($data->couverture);
    $save->setDuplication($data->duplication);
    $save->setTestsUnitaires($data->testsUnitaires);
    $save->setNombreDefaut($data->nombreDefaut);

    // Dette technique
    $save->setDette($data->dette);

    // Nombre de défaut
    $save->setNombreBug($data->nombreBug);
    $save->setNombreVulnerability($data->nombreVulnerability);
    $save->setNombreCodeSmell($data->nombreCodeSmell);

    // répartition par module (Java)
    $save->setFrontend($data->frontend);
    $save->setBackend($data->backend);
    $save->setAutre($data->autre);

    // Répartition par type
    $save->setNombreAnomalieBloquant($data->nombreAnomalieBloquant);
    $save->setNombreAnomalieCritique($data->nombreAnomalieCritique);
    $save->setNombreAnomalieInfo($data->nombreAnomalieInfo);
    $save->setNombreAnomalieMajeur($data->nombreAnomalieMajeur);
    $save->setNombreAnomalieMineur($data->nombreAnomalieMineur);

    // Notes Fiabilité, sécurité, hotspots et mauvaises pratique
    $save->setNoteReliability($data->noteReliability);
    $save->setNoteSecurity($data->noteSecurity);
    $save->setNoteSqale($data->noteSqale);
    $save->setNoteHotspot($data->noteHotspot);

    //répartition des hotspots
    $save->setHotspotHigh($data->hotspotHigh);
    $save->setHotspotMedium($data->hotspotMedium);
    $save->setHotspotLow($data->hotspotLow);
    $save->setHotspotTotal($data->hotspotTotal);

    // Je suis un favori, une verion initiale ?  0 (false) and 1 (true).
    // On récupère 0 ou 1 et non FALSE et TRUE
    $save->setFavori($data->favori);
    $save->setInitial($data->initial);

    // Nombre de défaut par sévérité
    // Les BUG
    $save->setBugBlocker($data->bugBlocker);
    $save->setBugCritical($data->bugCritical);
    $save->setBugMajor($data->bugMajor);
    $save->setBugMinor($data->bugMinor);
    $save->setBugInfo($data->bugInfo);

    // Les VULNERABILITY
    $save->setVulnerabilityBlocker($data->vulnerabilityBlocker);
    $save->setVulnerabilityCritical($data->vulnerabilityCritical);
    $save->setVulnerabilityMajor($data->vulnerabilityMajor);
    $save->setVulnerabilityMinor($data->vulnerabilityMinor);
    $save->setVulnerabilityInfo($data->vulnerabilityInfo);

    // Les CODE SMELL
    $save->setCodeSmellBlocker($data->codeSmellBlocker);
    $save->setCodeSmellCritical($data->codeSmellCritical);
    $save->setCodeSmellMajor($data->codeSmellMajor);
    $save->setCodeSmellMinor($data->codeSmellMinor);
    $save->setCodeSmellInfo($data->codeSmellInfo);

    // On ajoute la date et on enregistre
    $save->setDateEnregistrement($date);
    $em->persist($save);

    // On catch l'erreur sur la clé composite : maven_key, version, date_version
    try {
      $em->flush();
    } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
      // General error: 5 database is locked"
      // General error: 19 violation de clé
      if ($e->getCode() === 19) {
        $code = 19;
      } else {
        $code = $e;
      }
      return $response->setData(["code" => $code, Response::HTTP_OK]);
    }
    // Tout va bien !
    return $response->setData(["code" => "OK", Response::HTTP_OK]);
  }
}
