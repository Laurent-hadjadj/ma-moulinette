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

/** Gestion de accès aux API */
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Accès aux tables SLQLite */
use App\Entity\Secondary\Repartition;
use Doctrine\Persistence\ManagerRegistry;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Logger */
use Psr\Log\LoggerInterface;

/**
 * [Description ApiProjetRepartitionController]
 */
class ApiProjetRepartitionController extends AbstractController
{

  /**
   * [Description for __construct]
   *
   * @param  private
   * @param  private
   * @param  private
   *
   * Created at: 04/12/2022, 09:00:38 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct(
    private ManagerRegistry $doctrine,
    private HttpClientInterface $client,
    private LoggerInterface $logger
    )
  {
    $this->client = $client;
    $this->doctrine = $doctrine;
    $this->logger = $logger;
  }

  public static $sonarUrl = "sonar.url";
  public static $strContentType = 'application/json';
  public static $apiIssuesSearch = "/api/issues/search?componentKeys=";


  /**
   * [Description for batch_Analyse]
   *
   * @param mixed $elements
   * @param mixed $mavenKey
   *
   * @return ['frontend'=>$frontend, 'backend'=>$backend, 'autre'=>$autre];
   *
   * Created at: 04/12/2022, 09:00:59 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   *
   */
  protected function batch_Analyse($elements, $mavenKey)
  {
    $frontend = 0;
    $backend = 0;
    $autre = 0;
    $erreur=0;

    /** nom du projet */
    $app = explode(":", $mavenKey);
    foreach ($elements as $element) {
      $file = str_replace($mavenKey . ":", "", $element->getComponent());
      $module = explode("/", $file);

      switch ($module[0]) {
        case "du-presentation" || "rs-presentation" : $frontend = $frontend + 1;
              break;
        case  $app[1] . "-presentation" ||
              $app[1] . "-presentation-commun" ||
              $app[1] . "-presentation-ear" ||
              $app[1] . "-webapp": $frontend = $frontend + 1;
              break;
        case "rs-metier" : $backend = $backend + 1;
              break;
        case  $app[1] . "-metier" ||
              $app[1] . "-common" ||
              $app[1] . "-api" ||
              $app[1] . "-dao": $backend = $backend + 1;
              break;
        case  $app[1] . "-metier-ear" ||
              $app[1] . "-service" ||
              $app[1] . "-serviceweb" ||
              $app[1] . "-middleoffice": $backend = $backend + 1;
              break;
        case  $app[1] . "-metier-rest" ||
              $app[1] . "-entite" ||
              $app[1] . "-serviceweb-client": $backend = $backend + 1;
              break;
        case  $app[1] . "-batch" ||
              $app[1] . "-batchs" ||
              $app[1] . $app[1] . "-batch-envoi-dem-aval" ||
              $app[1] . "-batch-import-billets": $autre = $autre + 1;
              break;
        case  $app[1] . $app[1] . "-rdd" : $autre = $autre + 1;
              break;
        default:
            $erreur=$erreur+1;
      }
    }

    return ['erreur'=>$erreur, 'frontend'=>$frontend, 'backend'=>$backend, 'autre'=>$autre];
  }

  /**
   * [Description for httpClient]
   * httpClient
   * @param mixed $url
   *
   * @return array
   *
   * Created at: 04/12/2022, 09:01:28 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function httpClient($url): array
  {
    if (empty($this->getParameter('sonar.token'))) {
      $user = $this->getParameter('sonar.user');
      $password = $this->getParameter('sonar.password');
    } else {
      $user = $this->getParameter('sonar.token');
      $password = '';
    }

    $ciphers= "DH-RSA-AES128-SHA DH-RSA-AES256-SHA DHE-DSS-AES128-SHA DHE-DSS-AES256-SHA
                DHE-RSA-AES128-SHA DHE-RSA-AES256-SHA DH-AES128-SHA ADH-AES256-SHA";
    $response = $this->client->request('GET', $url,
      [
        'ciphers' => trim(preg_replace(static::$regex, " ", $ciphers)),
        'auth_basic' => [$user, $password], 'timeout' => 45,
        'headers' => ['Accept' => static::$strContentType,
        'Content-Type' => static::$strContentType]
      ]
    );

    if (200 !== $response->getStatusCode()) {
      if ($response->getStatusCode() == 401) {
        throw new \UnexpectedValueException('Erreur d\'Authentification. La clé n\'est pas correcte.');
      } else {
        throw new \UnexpectedValueException('Retour de la réponse différent de ce qui est prévu. Erreur '
          . $response->getStatusCode());
      }
    }

    /**
     * La variable n'est pas utilisé, elle permet de collecter
     *  les données et de rendre la main.
     */
    $contentType = $response->getHeaders()['content-type'][0];
    $this->logger->INFO('** ContentType *** '.isset($contentType));

    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
  }

  /**
   * [Description for batch_anomalie]
   * Fonction qui permet de parser les anomalies par type selon le nombre
   * de page disponible.
   * $pageSize = 1 à 500
   * $index = 1 à 20 max ==> 10000 anomalies
   * $type = BUG,VULNERABILITY,CODE_SMELL
   * $severite = INFO,MINOR,MAJOR,CRITICAL,BLOCKER
   * http://{url}/api/issues/search?componentKeys={key}&statuses=OPEN,CONFIRMED,REOPENED&
   * resolutions=&s=STATUS&asc=no&types={type}&severities={severite}=&ps={pageSize}&p=
   *
   * @param mixed $mavenKey
   * @param mixed $index
   * @param mixed $pageSize
   * @param mixed $type
   * @param mixed $severity
   *
   * @return [type]
   *
   * Created at: 04/12/2022, 09:02:29 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function batch_anomalie($mavenKey, $index, $pageSize, $type, $severity)
  {

    /** On bind les variables */
    $tempoPageSize = "&ps=${pageSize}";
    $tempoPageindex = "&p=${index}";
    $tempoStates = "&statuses=OPEN,CONFIRMED,REOPENED&resolutions=&s=STATUS&asc=no";
    $tempoType = "&types=${type}";
    $tempoSeverity= "&severities=${severity}";

    /** On bind les variables */
    $tempoUrl=$this->getParameter(static::$sonarUrl);
    $tempoApi=static::$apiIssuesSearch;

    /** On construit l'URL */
    $url1 = "${tempoUrl}${tempoApi}${mavenKey}${tempoStates}${tempoType}";
    $url2 = "${tempoSeverity}${tempoPageSize}${tempoPageindex}";
    /** On appel l'Api et on renvoie le résultat */
    return $this->httpClient($url1.$url2);
  }

  /**
   * [Description for projetRepartitionDetails]
   * Récupère le total des anomalies par severité.
   *
   * @param Request $request
   *
   * @return response
   * INFO,MINOR,MAJOR,CRITICAL,BLOCKER
   *
   * Created at: 04/12/2022, 09:03:46 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/repartition/details', name: 'projet_repartition_details', methods: ['GET'])]
  public function projetRepartitionDetails(Request $request): response
  {
    $mavenKey=$request->get('mavenKey');
    $type=$request->get('type');

    $response = new JsonResponse();
    /** On teste si la clé est valide */
    if (is_null($mavenKey)) {
      return $response->setData(["message" => "la clé maven est vide!", Response::HTTP_BAD_REQUEST]);
    }

    /** On récupère le nombre d'anomalie pour le type */
    $severity=['INFO','MINOR','MAJOR','CRITICAL','BLOCKER'];
    $total=0;
    foreach ($severity as $value) {
      $result=$this->batch_anomalie($mavenKey, 1, 1, $type, $value);
      if ( $value==='INFO' ) {
        $info=$result['total'];
      }
      if ( $value==='MINOR' ) {
        $minor=$result['total'];
      }
      if ( $value==='MAJOR' ) {
        $major=$result['total'];
      }
      if ( $value==='CRITICAL' ) {
        $critical=$result['total'];
      }
      if ( $value==='BLOCKER' ) {
        $blocker=$result['total'];
      }
      $total=$total+$result['total'];
    }

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
   * [Description for projetRepartitionCollecte]
   * Calcul la répartition entre front, back et autre pour tout les type et les sévérités.
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 04/12/2022, 09:04:35 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/repartition/collecte', name: 'projet_repartition_collecte', methods: ['PUT'])]
  public function projetRepartitionCollecte(Request $request): response
  {
    /** On décode le body */
    $data = json_decode($request->getContent());

    /** On bind les variables */
    $mavenKey = $data->mavenKey;
    $type = $data->type;
    $severity = $data->severity;
    $setup = $data->setup;

    /** nom du projet */
    $name = explode(":", $mavenKey);
    $date= new DateTime();
    $date->setTimezone(new DateTimeZone('Europe/Paris'));

    /** On récupère le nombre d'anomalie pour le type */
    $result=$this->batch_anomalie($mavenKey, 1, 1, $type, $severity);
    $i = 1;
    $date1=time();
    while (!empty($result["issues"]) && $i<21) {
        $result=$this->batch_anomalie($mavenKey, $i, 500, $type, $severity);
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

          $manager = $this->doctrine->getManager('secondary');
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
   * [Description for projetRepartitionClear]
   *
   * @param Request $request
   *
   * @return Response
   *
   * Created at: 04/12/2022, 09:05:01 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/repartition/clear', name: 'projet_repartition_clear', methods: ['GET'])]
  public function projetRepartitionClear(Request $request): Response
  {
    $mavenKey = $request->get('mavenKey');

    /** On créé un nouvel objet Json */
    $response = new JsonResponse();

    /** On surprime de la table historique le projet */
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
   * [Description for projetRepartitionAnalyse]
   *
   * @param Request $request
   *
   * @return Response
   * ["code" => "OK", "repartition"=>$result, Response::HTTP_OK]
   *
   * Created at: 04/12/2022, 09:05:20 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/repartition/analyse', name: 'projet_repartition_analyse', methods: ['PUT'])]
  public function projetRepartitionAnalyse(Request $request): Response
  {
    /** On décode le body */
    $data = json_decode($request->getContent());

    /** On bind les variables */
    $mavenKey = $data->mavenKey;
    $type = $data->type;
    $severity = $data->severity;
    $setup = $data->setup;

    /** On créé un nouvel objet Json */
    $response = new JsonResponse();

    /** On récupère la liste des bugs */
    $liste = $this->doctrine
      ->getManager('secondary')
      ->getRepository(Repartition::class)
      ->findBy(
        ['mavenKey' => $mavenKey,
          'type' => $type,
          'severity' => $severity,
          'setup' => $setup]);
    /** on appelle le service d'analyse */
    $result=$this->batch_analyse($liste, $mavenKey);
    return $response->setData(["code" => "OK", "repartition"=>$result, Response::HTTP_OK]);
  }

}
