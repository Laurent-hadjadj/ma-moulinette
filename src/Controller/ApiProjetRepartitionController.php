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
use App\Entity\Secondary\Repartition;
use Doctrine\Persistence\ManagerRegistry;
use DateTime;

// Logger
use Psr\Log\LoggerInterface;

class ApiProjetRepartitionController extends AbstractController
{

  private $client;

  public function __construct(HttpClientInterface $client)
  {
    $this->client = $client;
  }

  public static $strContentType = 'application/json';
  public static $dateFormat = "Y-m-d H:m:s";
  public static $sonarUrl = "sonar.url";
  public static $apiIssuesSearch = "/api/issues/search?componentKeys=";

  /**
   * batch_Analyse
   *
   * @param  mixed $elements
   * @param  mixed $mavenKey
   * @return void
   */
  protected function batch_Analyse($elements, $mavenKey)
  {
    $frontend = 0;
    $backend = 0;
    $autre = 0;
    // nom du projet
    $app = explode(":", $mavenKey);
    foreach ($elements as $element) {
      $file = str_replace($mavenKey . ":", "", $element->getComponent());
      $module = explode("/", $file);

      if ($module[0] == "du-presentation") {
        $frontend = $frontend + 1;
      }
      if ($module[0] == "rs-presentation") {
        $frontend = $frontend + 1;
      }
      if ($module[0] == "rs-metier") {
        $backend = $backend + 1;
      }

      /**
       *  Application Frontend
       */
      if ($module[0] == $app[1] . "-presentation") {
        $frontend = $frontend + 1;
      }
      // Application : Legacy
      if ($module[0] == $app[1] . "-presentation-commun") {
        $frontend = $frontend + 1;
      }
      if ($module[0] == $app[1] . "-presentation-ear") {
        $frontend = $frontend + 1;
      }
      if ($module[0] == $app[1] . "-webapp") {
        $frontend = $frontend + 1;
      }

      /**
       * Application Backend
       */
      if ($module[0] == $app[1] . "-metier") {
        $backend = $backend + 1;
      }
      if ($module[0] == $app[1] . "-common") {
        $backend = $backend + 1;
      }
      if ($module[0] == $app[1] . "-api") {
        $backend = $backend + 1;
      }
      if ($module[0] == $app[1] . "-dao") {
        $backend = $backend + 1;
      }
      if ($module[0] == $app[1] . "-metier-ear") {
        $backend = $backend + 1;
      }
      if ($module[0] == $app[1] . "-service") {
        $backend = $backend + 1;
      }
      // Application : Legacy
      if ($module[0] == $app[1] . "-serviceweb") {
        $backend = $backend + 1;
      }
      // Application : Dénormaliser
      if ($module[0] == $app[1] . "-middleoffice") {
        $backend = $backend + 1;
      }
      // Application : Starter-Kit
      if ($module[0] == $app[1] . "-metier-rest") {
        $backend = $backend + 1;
      }
      // Application : Legacy
      if ($module[0] == $app[1] . "-entite") {
         $backend = $backend + 1;
      }
      // Application : Legacy
      if ($module[0] == $app[1] . "-serviceweb-client") {
        $backend = $backend + 1;
     }

      /**
       * Application Batch et Autres
       */
      if ($module[0] == $app[1] . "-batch") {
        $autre = $autre + 1;
      }
       if ($module[0] == $app[1] . "-batchs") {
        $autre = $autre + 1;
      }
      if ($module[0] == $app[1] . "-batch-envoi-dem-aval") {
        $autre = $autre + 1;
      }
      if ($module[0] == $app[1] . "-batch-import-billets") {
        $autre = $autre + 1;
      }
      if ($module[0] == $app[1] . "-rdd") {
        $autre = $autre + 1;
      }
    }
    return ['frontend'=>$frontend, 'backend'=>$backend, 'autre'=>$autre];
  }

  /**
   * httpClient
   *
   * @param  mixed $url
   * @param  mixed $logger
   * @return void
   */
  protected function httpClient($url, LoggerInterface $logger)
  {
    if (empty($this->getParameter('sonar.token'))) {
      $user = $this->getParameter('sonar.user');
      $password = $this->getParameter('sonar.password');
    } else {
      $user = $this->getParameter('sonar.token');
      $password = '';
    }

    $response = $this->client->request('GET', $url,
      [
        'ciphers' => 'AES128-SHA AES256-SHA DH-DSS-AES128-SHA DH-DSS-AES256-SHA DH-RSA-AES128-SHA DH-RSA-AES256-SHA DHE-DSS-AES128-SHA DHE-DSS-AES256-SHA DHE-RSA-AES128-SHA DHE-RSA-AES256-SHA ADH-AES128-SHA ADH-AES256-SHA',
        'auth_basic' => [$user, $password], 'timeout' => 45,
        'headers' => ['Accept' => static::$strContentType,
        'Content-Type' => static::$strContentType]
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
    $logger->INFO('** ContentType *** '.isset($contentType));

    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
  }

  /**
  * description
  * Fonction qui permet de parser les anomalies par type selon le nombre de page disponible
  * $pageSize = 1 à 500
  * $index = 1 à 20 max ==> 10000 anomalies
  * $type = BUG,VULNERABILITY,CODE_SMELL
  * $severite = INFO,MINOR,MAJOR,CRITICAL,BLOCKER
  * http://{url}/api/issues/search?componentKeys={key}&statuses=OPEN,CONFIRMED,REOPENED&resolutions=&s=STATUS&asc=no&types={type}&severities={severite}=&ps={pageSize}&p={index}
  */
  protected function batch_anomalie($mavenKey, $index, $pageSize, $type, $severity,LoggerInterface $logger)
  {

    // On bind les variables
    $tempoPageSize = "&ps=${pageSize}";
    $tempoPageindex = "&p=${index}";
    $tempoStates = "&statuses=OPEN,CONFIRMED,REOPENED&resolutions=&s=STATUS&asc=no";
    $tempoType = "&types=${type}";
    $tempoSeverity= "&severities=${severity}";

    // On bind les variables
    $tempoUrl=$this->getParameter(static::$sonarUrl);
    $tempoApi=static::$apiIssuesSearch;

    // On construit l'URL
    $url = "${tempoUrl}${tempoApi}${mavenKey}${tempoStates}${tempoType}${tempoSeverity}${tempoPageSize}${tempoPageindex}";
    // On appel l'Api et on renvoie le résultat
    return $this->httpClient($url, $logger);
  }

  /**
   * projetRepartitionDetails
   * Rcéupère le total des anomalies par severité.
   *
   * @param  mixed $request
   * @param  mixed $logger
   * @return response
   * INFO,MINOR,MAJOR,CRITICAL,BLOCKER
   */
  #[Route('/api/projet/repartition/details', name: 'projet_repartition_details', methods: ['GET'])]
  public function projetRepartitionDetails(Request $request, LoggerInterface $logger): response
  {
    $mavenKey=$request->get('mavenKey');
    $type=$request->get('type');

    // On récupère le nombre d'anomalie pour le type
    $severity=['INFO','MINOR','MAJOR','CRITICAL','BLOCKER'];
    $total=0;
    foreach ($severity as $value) {
      $result=$this->batch_anomalie($mavenKey, 1, 1, $type, $value, $logger);
      if ($value==='INFO') { $info=$result['total'];}
      if ($value==='MINOR') { $minor=$result['total'];}
      if ($value==='MAJOR') { $major=$result['total'];}
      if ($value==='CRITICAL') { $critical=$result['total'];}
      if ($value==='BLOCKER') { $blocker=$result['total'];}
      $total=$total+$result['total'];
    }

    $response = new JsonResponse();
     return $response->setData(
      ["total" => $total,
       "type" => $type,
       "blocker"=> $blocker,
       "critical"=> $critical,
       "major"=> $major,
       "minor"=> $minor,
       "info"=> $info,
       Response::HTTP_OK]);
  }

   /**
   * projetRepartitionCollecte
   * Calcul la répartition entre front, back et autre pour tout les type et les sévérités.
   *
   * @param  mixed $em
   * @param  mixed $request
   * @param  mixed $logger
   * @return response
   *
   */
  #[Route('/api/projet/repartition/collecte', name: 'projet_repartition_collecte', methods: ['PUT'])]
  public function projetRepartitionCollecte(ManagerRegistry $doctrine, Request $request, LoggerInterface $logger): response
  {
    // On décode le body
    $data = json_decode($request->getContent());

    // On bind les variables
    $mavenKey = $data->mavenKey;
    $type = $data->type;
    $severity = $data->severity;
    $setup = $data->setup;

    // nom du projet
    $name = explode(":", $mavenKey);
    $date= new DateTime();

    // On récupère le nombre d'anomalie pour le type
    $result=$this->batch_anomalie($mavenKey, 1, 1, $type, $severity, $logger);
    $i = 1;
    $date1=time();
    while (!empty($result["issues"]) && $i<21) {
        $result=$this->batch_anomalie($mavenKey, $i, 500, $type, $severity, $logger );
        foreach ($result["issues"] as $issue) {
          $type=$issue["type"];
          $severity=$issue["severity"];
          $component=$issue["component"];


          $issue = new Repartition();
          $issue->setMavenKey($mavenKey);
          $issue->setName($name[1]);
          $issue->setComponent($component);
          $issue->setType($type);
          $issue->setSeverity($severity);
          $issue->setSetup($setup);
          $issue->setDateEnregistrement($date);

          $manager = $doctrine->getManager('secondary');
          $manager->persist($issue);
          $manager->flush();
        }
        $i++;
    }
    $date2=time();
    $response = new JsonResponse();
    return $response->setData(
      ["total" => $result["total"],
       "type" => $type,
       "severity"=> $severity,
       "setup"=> $setup,
       "temps" => abs($date1 - $date2)+2,
      Response::HTTP_OK]);
  }

  /**
   * projetRepartitionClear
   *
   * @param  mixed $dm
   * @param  mixed $request
   * @return Response
   */
  #[Route('/api/projet/repartition/clear', name: 'projet_repartition_clear', methods: ['GET'])]
  public function projetRepartitionClear(Request $request): Response
  {
    $mavenKey = $request->get('mavenKey');

    // On créé un nouvel objet Json
    $response = new JsonResponse();

    // On surprime de la table historique le projet
    $sql = "DELETE FROM repartition WHERE maven_key='${mavenKey}'";
    $conn = \Doctrine\DBAL\DriverManager::getConnection(['url' => $this->getParameter('sqlite.secondary.path')]);
    try {
      $conn->prepare($sql)->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }
    return $response->setData(["code" => "OK", Response::HTTP_OK]);
  }

  /**
   * projetRepartitionAnalyse
   *
   * @param  mixed $coctrine
   * @param  mixed $request
   * @return Response
   */
  #[Route('/api/projet/repartition/analyse', name: 'projet_repartition_analyse', methods: ['PUT'])]
  public function projetRepartitionAnalyse(ManagerRegistry $doctrine, Request $request): Response
  {
    // On décode le body
    $data = json_decode($request->getContent());

    // On bind les variables
    $mavenKey = $data->mavenKey;
    $type = $data->type;
    $severity = $data->severity;
    $setup = $data->setup;

    // On créé un nouvel objet Json
    $response = new JsonResponse();

    // On récupère la liste des bugs
    $liste = $doctrine->getManager('secondary')
                        ->getRepository(Repartition::class)
                        ->findBy(
                          ['maven_key' => $mavenKey,
                           'type' => $type,
                           'severity' => $severity,
                           'setup' => $setup]);
    // on appelle le service d'analyse
    $result=$this->batch_analyse($liste, $mavenKey);
    return $response->setData(["code" => "OK", "repartition"=>$result, Response::HTTP_OK]);
  }


}
