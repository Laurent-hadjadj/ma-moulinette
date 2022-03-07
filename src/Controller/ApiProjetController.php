<?php
/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
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
use App\Entity\InformationProjet;
use App\Entity\NoSonar;
use App\Entity\Mesures;
use App\Entity\Anomalie;
use App\Entity\AnomalieDetails;
use App\Entity\TempAnomalie;
use App\Entity\Owasp;
use App\Entity\Hotspots;
use App\Entity\HotspotOwasp;
use App\Entity\HotspotDetails;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use DateTime;

class ApiProjetController extends AbstractController
{

  private $client;

  public function __construct(HttpClientInterface $client)
    {
     $this->client = $client;
    }

  public static $strContentType = 'application/json';
  public static $dateFormat = "Y-m-d H:m:s";
  public static $sonarUrl= "sonar.url";

  /**
  * description
  * Fonction pirvée pour convertir une date au format xxd aah xxmin en minutes
  */
   function date_to_minute($str)
   {
    $jour = 0; $heure = 0; $minute = 0;
    //[2d1h1min]-- >[2] [1h1min]
    $j = explode('d', $str);
    if (count($j) == 1) { $h = explode('h', $j[0]); }
    if (count($j) == 2) { $jour = $j[0]; $h = explode('h',$j[1]); }

    //heure [1], [1min]
    if (count($h) == 1) { $m = explode('min', $h[0]); }
    if (count($h) == 2) { $heure = $h[0]; $m = explode('min', $h[1]); }

    //minute
    if (count($m) == 1) { $m = explode('min', $j[0]); }
    if (count($m) == 2) { $mm =explode('min', $m[0]); $minute = $mm[0]; }

    return ($jour * 24 * 60) + ($heure * 60) + intval($minute);
  }

  /**
   * description
   * Converti les minutes en jours, heures et minutes
 */
  function minutes_to($minutes) {
    $j = (int)($minutes / 1440);
    $h = (int)(($minutes - ($j * 1440)) / 60);
    $m = round($minutes % 60);
    if (empty($h) || is_null($h)) { $h=0; }
    if ($j > 0) { return ($j."d, ".$h."h:".$m."min"); } else { return ($h."h:".$m."min"); }
  }

 /**
   * description
   * http_client
 */
  function http_client($url) {
    $response = $this->client->request(
      'GET', $url, [ 'auth_basic' => [$this->getParameter('sonar.user'), $this->getParameter('sonar.password')], 'timeout' => 45,'headers' => [ 'Accept' => self::$strContentType, 'Content-Type' => self::$strContentType]
    ]);

    if (200 !== $response->getStatusCode()) {
        if ($response->getStatusCode() == 401) {
          throw new \Exception('Erreur d\'Authentification. La clé n\'est pas correcte.');
        } else {
      throw new \Exception('Retour de la réponse différent de ce qui est prévu. Erreur '.
          $response->getStatusCode());
        }
      }

    $contentType = $response->getHeaders()['content-type'][0];
    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
    }


  /**
  * description
  * Fonction qui permet de parser les anomalies par type selon le nombre de page disponible
  * $pageSize = 1 à 500
  * $index = 1 à 20 max ==> 10000 anomalies
  * $type = BUG,VULNERABILITY,CODE_SMELL
  * http://{url}/api/issues/search?componentKeys={key}&statuses=OPEN,CONFIRMED,REOPENED&resolutions=&s=STATUS&asc=no&types={type}&ps={pageSize}&p={index}
  */
   function batch_anomalie($maven_key, $index, $pageSize, $type)
   {
    $issues_search = 'issues/search?componentKeys=';
    $pageSize = '&ps='.$pageSize;
    $pageindex = '&p='.$index;
    $states = '&statuses=OPEN,CONFIRMED,REOPENED&resolutions=&s=STATUS&asc=no';
    $type = '&types='.$type;

    $url=$this->getParameter(self::$sonarUrl)."/api/".$issues_search.$maven_key.$states.$type.$pageSize.$pageindex;
    $response = $this->client->request(
      'GET', $url, [ 'auth_basic' => [$this->getParameter('sonar.user'), $this->getParameter('sonar.password')], 'timeout' => 300,'headers' => [ 'Accept' => self::$strContentType, 'Content-Type' => self::$strContentType]
    ]);

    if (200 !== $response->getStatusCode()) {
        if ($response->getStatusCode() == 401) {
          throw new \Exception('Erreur d\'Authentification. La clé n\'est pas correcte.');
        } else {
      throw new \Exception('Retour de la réponse différent de ce qui est prévu. Erreur '.
          $response->getStatusCode());
        }
      }

    $contentType = $response->getHeaders()['content-type'][0];
    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
   }

  /**
   * description
   * Change le statut du favori pour un projet
  * http://{url}/api/favori{key}
  */
  #[Route('/api/favori', name: 'favori', methods: ['GET'])]
  public function favori(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $statut=$request->get('statut');
    $date= new DateTime();

    //on vérifie si le projet est déja en favori
    $sql="SELECT * FROM favori WHERE maven_key='". $maven_key ."'";
    $select=$em->getConnection()->prepare($sql)->executeQuery();

    //Si on a pas trouvé l'application dans la liste des favori, alors on la rajoute.
    if ( empty($select->fetchAllAssociative()) ){
        $sql="INSERT INTO favori ('maven_key', 'favori', 'date_enregistrement') VALUES ('".$maven_key."', 'TRUE', '".$date->format(self::$dateFormat)."')";
        $em->getConnection()->prepare($sql)->executeQuery();
      }
      else {
        $sql = "UPDATE favori SET favori='".$statut."', date_enregistrement='".$date->format(self::$dateFormat)."' WHERE maven_key='".$maven_key."'";
        $em->getConnection()->prepare($sql)->executeQuery();
      }
    $response = new JsonResponse();
    return $response->setData(["statut"=>$statut, Response::HTTP_OK]);
  }

/**
   * description
   * Récupére le statut d'un favori
   *  le favoriest TRUE ou FALSE ou null
  * http://{url}/api/favori/check={key}
  */
  #[Route('/api/favori/check', name: 'favori_check', methods: ['GET'])]
  public function favori_check(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    //on vérifie si le projet est déja en favori
    $sql="SELECT favori FROM favori WHERE maven_key='". $maven_key ."'";
    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $r=$select->fetchAllAssociative();
    if ( empty($r) ){
      return $response->setData(["statut"=>"null", Response::HTTP_OK]);
     }

    return $response->setData(["favori"=>"TRUE", "statut"=>$r[0]['favori'], Response::HTTP_OK]);
  }

  /*
    * description
    * récupère la liste des projets nom + clé
    * http://{url}}/api/liste_projet/affiche
  */
  #[Route('/api/liste_projet/affiche', name: 'liste_projet_affiche', methods: ['GET'])]
  public function db_liste_projet_affiche(Connection $connection) {

    $sql = "SELECT maven_key, name from 'liste_projet'";
    $rqt = $connection->fetchAllNumeric($sql);

    if (!$rqt)
    {throw $this->createNotFoundException('Oops - Il y a un problème.');}

    $liste=[];
    //objet = { id: clé, text: "blablabla" };
    foreach ($rqt as $value){
          $objet = [ 'id'=> $value[0], 'text'=> $value[1] ];
          array_push($liste, $objet);
      }

    $response = new JsonResponse();
    return $response->setData(["liste"=>$liste, Response::HTTP_OK]);
  }

  /**
   * description
   * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version)
  * http://{url}/api/project_analyses/search?project={key}
  */
  #[Route('/api/projet/analyses', name: 'projet_analyses', methods: ['GET'])]
  public function projet_analyses(EntityManagerInterface $em, Request $request): response
  {
    $url=$this->getParameter(self::$sonarUrl)."/api/project_analyses/search?project=".$request->get('maven_key');

    // on appel le client http
    $result=$this->http_client($url);

    // On récupère le manager de BD
    $date= new DateTime();

    //on supprime les informations sur le projet
    $sql = "DELETE FROM information_projet WHERE maven_key='".$request->get('maven_key')."'";
    $em->getConnection()->prepare($sql)->executeQuery();

    // On ajoute les informations du projets dans la table information_projet.
    $nombreVersion=0;

    foreach ($result["analyses"] as $analyse) {
      $nombreVersion ++;
      $explode=explode("-", $analyse["projectVersion"]);
      if (empty($explode[1])) { $explode[1] = 'N.C'; }

      $informationProjet = new InformationProjet();
      $informationProjet->setMavenKey($request->get('maven_key'));
      $informationProjet->setAnalyseKey($analyse["key"]);
      $informationProjet->setDate(new DateTime($analyse["date"]));
      $informationProjet->setProjectVersion($analyse["projectVersion"]);
      $informationProjet->setType(strtoupper($explode[1]));
      $informationProjet->setDateEnregistrement($date);
      $em->persist($informationProjet);
      $em->flush();
    }
    $response = new JsonResponse();
    return $response->setData(["nombreVersion"=>$nombreVersion, Response::HTTP_OK]);
  }

  /**
   * description
   * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version)
  * http://{url}/api/components/app?component={key}
  */
  #[Route('/api/projet/mesures', name: 'projet_mesures', methods: ['GET'])]
  public function projet_mesures(EntityManagerInterface $em, Request $request): response
  {
    $url=$this->getParameter(self::$sonarUrl)."/api/components/app?component=".$request->get('maven_key');

    // on appel le client http
    $result=$this->http_client($url);

    // On récupère le manager de BD
    $date= new DateTime();

    // On ajoute les mesures dans la table mesures.
    if (intval($result["measures"]["lines"])){$lines=intval($result["measures"]["lines"]);} else {$lines=0;}
    if ($result["measures"]["coverage"]){$coverage=$result["measures"]["coverage"];} else {$coverage=0;}
    if ($result["measures"]["duplicationDensity"]){$duplicationDensity=$result["measures"]["duplicationDensity"];} else {$duplicationDensity=0;}

    if (array_key_exists("tests", $result["measures"] ))
          {
            $tests=intval($result["measures"]["tests"]);
          } else { $tests=0; }

    if (intval($result["measures"]["issues"])){$issues=intval($result["measures"]["issues"]);} else {$issues=0;}

      $mesure = new Mesures();
      $mesure->setMavenKey($request->get("maven_key"));
      $mesure->setProjectName($result["projectName"]);
      $mesure->setLines($lines);
      $mesure->setCoverage($coverage);
      $mesure->setDuplicationDensity($duplicationDensity);
      $mesure->setTests(intval($tests));
      $mesure->setIssues(intval($issues));
      $mesure->setDateEnregistrement($date);
      $em->persist($mesure);
      $em->flush();

    $response = new JsonResponse();
    return $response->setData([Response::HTTP_OK]);
  }

/**
   * description
   * Initialisation de la table temp_anomalie et récupération du dernier setup ;
   *
  */
  #[Route('/api/projet/anomalies/setup', name: 'projet_anomalies_setup', methods: ['GET'])]
  public function projet_anomalies_setup(EntityManagerInterface $em): response
  {
    $date= new DateTime();

    //On ajoute un nouvelle analyse
    $sql = "select * FROM 'temp_anomalie' ORDER BY ID DESC LIMIT 1";
    $result_set=$em->getConnection()->prepare($sql)->executeQuery();
    $result_fetch=$result_set->fetchAllAssociative();
    if (empty($result_fetch))
      {
        $sql = "INSERT INTO 'temp_anomalie' ('maven_key', 'debt', 'debt_minute', 'rule', 'type', 'severity', 'setup', 'date_enregistrement') VALUES ('N.C', '0', 1, 'N.C', 'N.C', 'N.C', 0, '".$date->format(self::$dateFormat)."')";
        $em->getConnection()->prepare($sql)->executeQuery();
        $setup=1;
      }
      else { $setup=$result_fetch[0]["setup"]+1;}

      $response = new JsonResponse();
      return $response->setData(["setup"=> $setup, Response::HTTP_OK]);
   }

  /**
   * description
   * Récupère le total des anomalies, avec un filtre par répertoire, severité et types.
   * https://{URL}/api/issues/search?componentKeys={maven_key}&facets=directories,types,severities&p=1&ps=1&statuses=OPEN
   * https://{URL}/api/issues/search?componentKeys={maven_key}&types={type}&p=1&ps=1
   */
  #[Route('/api/projet/anomalie', name: 'projet_anomalie', methods: ['GET'])]
  public function projet_anomalie(EntityManagerInterface $em, Request $request): response
  {
    $url1=$this->getParameter(self::$sonarUrl)."/api/issues/search?componentKeys=".$request->get('maven_key')."&facets=directories,types,severities&p=1&ps=1&statuses=OPEN";

    // On récupère le total de la Dette technique pour les BUG
    $url2=$this->getParameter(self::$sonarUrl)."/api/issues/search?componentKeys=".$request->get('maven_key')."&types=BUG&p=1&ps=1";

    // On récupère le total de la Dette technique pour les VULNERAVILITY
    $url3=$this->getParameter(self::$sonarUrl)."/api/issues/search?componentKeys=".$request->get('maven_key')."&types=VULNERABILITY&p=1&ps=1";

    // On récupère le total de la Dette technique pour les CODE_SMELL
    $url4=$this->getParameter(self::$sonarUrl)."/api/issues/search?componentKeys=".$request->get('maven_key')."&types=CODE_SMELL&p=1&ps=1";

    // on appel le client http
    $result1=$this->http_client($url1);
    $result2=$this->http_client($url2);
    $result3=$this->http_client($url3);
    $result4=$this->http_client($url4);

    $date= new DateTime();
    $maven_key=$request->get('maven_key');

    if ($result1["paging"]["total"]!=0){
      // On supprime  les enregistrement correspondant à la clé
      $sql = "DELETE FROM anomalie WHERE maven_key='".$maven_key."'";
      $em->getConnection()->prepare($sql)->executeQuery();

      // nom du projet
      $app=explode(":",$maven_key);

      $anomalie_total=$result1["total"];
      $dette_minute=$result1["effortTotal"];
      $dette=$this->minutes_to($dette_minute);
      $dette_reliability_minute=$result2["effortTotal"];
      $dette_reliability=$this->minutes_to($dette_reliability_minute);
      $dette_vulnerability_minute=$result3["effortTotal"];
      $dette_vulnerability=$this->minutes_to($dette_vulnerability_minute);
      $dette_code_smell_minute=$result4["effortTotal"];
      $dette_code_smell=$this->minutes_to($dette_code_smell_minute);

      $facets=$result1["facets"];
      // modules
      $frontend=0; $backend=0; $batch=0;
      foreach($facets as $facet)
      {
        if ($facet["property"]=="severities"){
          foreach( $facet["values"] as $severity) {
              if ($severity["val"]=="BLOCKER") { $blocker=$severity["count"]; }
              if ($severity["val"]=="CRITICAL") { $critical=$severity["count"]; }
              if ($severity["val"]=="MAJOR") { $major=$severity["count"]; }
              if ($severity["val"]=="INFO") { $info=$severity["count"]; }
              if ($severity["val"]=="MINOR") { $minor=$severity["count"]; }
             }
         }
         if ($facet["property"]=="types"){
          foreach( $facet["values"] as $type) {
              if ($type["val"]=="BUG") { $bug=$type["count"]; }
              if ($type["val"]=="VULNERABILITY") { $vulnerability=$type["count"]; }
              if ($type["val"]=="CODE_SMELL") { $code_smell=$type["count"]; }
             }
         }
         if ($facet["property"]=="directories"){
          foreach( $facet["values"] as $directory) {

            $file=str_replace($maven_key.":", "", $directory["val"]);
            $module=explode("/", $file);

            // Application Frontend
            if ($module[0]==$app[1]."-presentation"){$frontend=$frontend+$directory["count"];}
            if ($module[0]==$app[1]."-presentation-commun") {$frontend=$frontend+$directory["count"];}
            if ($module[0]==$app[1]."-presentation-ear") {$frontend=$frontend+$directory["count"];}
            if ($module[0]==$app[1]."-webapp") {$frontend=$frontend+$directory["count"];}

            // Application Backend
            if ($module[0]==$app[1]."-metier") {$backend=$backend+$directory["count"];}
            if ($module[0]==$app[1]."-common") {$backend=$backend+$directory["count"];}
            if ($module[0]==$app[1]."-api") {$backend=$backend+$directory["count"];}
            if ($module[0]==$app[1]."-dao") {$backend=$backend+$directory["count"];}
            if ($module[0]==$app[1]."-metier-ear") {$backend=$backend+$directory["count"];}
            if ($module[0]==$app[1]."-service") {$backend=$backend+$directory["count"];}
            if ($module[0]==$app[1]."-serviceweb") {$backend=$backend+$directory["count"];}
            if ($module[0]==$app[1]."-middleoffice") {$backend=$backend+$directory["count"];}

            // Application Batch
            if ($module[0]==$app[1]."-batchs") {$batch=$batch+$directory["count"]; }
          }
      }
      }

      // Enregitremet dans la table Anomalie
      $issue = new Anomalie();
      $issue->setMavenKey($maven_key);
      $issue->setProjectName($app[1]);
      $issue->setAnomalieTotal($anomalie_total);
      $issue->setDette($dette);
      $issue->setDetteMinute($dette_minute);
      $issue->setDetteReliability($dette_reliability);
      $issue->setDetteReliabilityMinute($dette_reliability_minute);
      $issue->setDetteVulnerability($dette_vulnerability);
      $issue->setDetteVulnerabilityMinute($dette_vulnerability_minute);
      $issue->setDetteCodeSmell($dette_code_smell);
      $issue->setDetteCodeSmellMinute($dette_code_smell_minute);
      $issue->setFrontend($frontend);
      $issue->setBackend($backend);
      $issue->setBatch($batch);
      $issue->setBlocker($blocker);
      $issue->setCritical($critical);
      $issue->setMajor($major);
      $issue->setInfo($info);
      $issue->setMinor($minor);
      $issue->setBug($bug);
      $issue->setVulnerability($vulnerability);
      $issue->setCodeSmell($code_smell);
      $issue->setDateEnregistrement($date);
      $em->persist($issue);
      $em->flush();
    }
    $info= "Enregistrement correctement effectué.";

    $response = new JsonResponse();
    return $response->setData(["info"=>$info, Response::HTTP_OK]);
  }


  /**
   * description
   * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version)
  *
  */
  #[Route('/api/projet/anomalies/details', name: 'projet_anomalies_details', methods: ['GET'])]
  public function projet_anomalies_details(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $type=$request->get('type');
    $setup=intVal($request->get('setup'));

    $date= new DateTime();

    $result=$this->batch_anomalie($maven_key, 1, 1, $type );
    $i = 1;
    while (!empty($result["issues"]) && $i<21) {
        $result=$this->batch_anomalie($maven_key, $i, 500, $type );
        foreach ($result["issues"] as $issue) {
          //La clé debt peut ne pas être présente (un bug sonarqube !)
          if (array_key_exists("debt", $issue ))
            {
              $debt=$issue["debt"];
              $debtMinute=$this->date_to_minute($debt);
            } else {
              $debt="0min";
              $debtMinute=0;
            }
          $rule=$issue["rule"];
          $type=$issue["type"];
          $severity=$issue["severity"];
          $component=$issue["component"];

          $issue = new TempAnomalie();
          $issue->setMavenKey($request->get("maven_key"));
          $issue->setDebt($debt);
          $issue->setDebtMinute($debtMinute);
          $issue->setRule($rule);
          $issue->setComponent($component);
          $issue->setType($type);
          $issue->setSeverity($severity);
          $issue->setSetup($setup);
          $issue->setDateEnregistrement($date);
          $em->persist($issue);
          $em->flush();
        }
        $i++;
    }
    if ($result["total"]>10000) { $info="Le nombre d'anomalie est supèrieur à 10 000 !!:"; }
      else { $info= "Enregistrement correctement effectué."; }

    $response = new JsonResponse();
    return $response->setData(["nombre"=>$result["total"], "info"=>$info, Response::HTTP_OK]);
  }

    /**
   * description
   * Consolidation des statitsiques pour les défauts
  *
  */
  #[Route('/api/projet/anomalies/consolidation', name: 'projet_anomalies_consolidation', methods: ['GET'])]
  public function projet_anomalies_consolidation(EntityManagerInterface $em, Request $request): response
  {
    // On récupère les informations depuis l'URL
    $maven_key=$request->get('maven_key');
    $setup=intVal($request->get('setup'));

    $date= new DateTime();
    // On récupère le nom du projet et la version
    $sql = "select * FROM information_projet WHERE maven_key='". $maven_key ."' ORDER BY date DESC LIMIT 1";
    $result_set=$em->getConnection()->prepare($sql)->executeQuery();
    $result_projet=$result_set->fetchAllAssociative();

    $explode=explode(":", $maven_key);
    $project_name=$explode[1];
    $project_analyse=$result_projet[0]["analyse_key"];
    $project_version=$result_projet[0]["project_version"];
    $project_date=$result_projet[0]["date"];

   // On calcul le nombre de BUG, VULNERAIBILITY et de CODE_SMELL
  $types=['BUG', 'VULNERABILITY', 'CODE_SMELL'];
  $severities=["BLOCKER","CRITICAL","INFO","MAJOR","MINOR"];
  foreach ($types as $type) {
     foreach ($severities as $severity) {
        $sql="SELECT severity, COUNT(*) as Total FROM temp_anomalie WHERE type='".$type."' AND severity='".$severity."' AND maven_key='".$maven_key."' AND setup='".$setup."'";
        $result_set=$em->getConnection()->prepare($sql)->executeQuery();
        $count=$result_set->fetchAllAssociative();

        if ( $type=="BUG") {
          switch ($severity) {
              case "BLOCKER":
                $nbBugBlocker=$count[0]["Total"];
                break;
              case "CRITICAL":
                $nbBugCritical=$count[0]["Total"];
                break;
              case "INFO":
                $nbBugInfo=$count[0]["Total"];
                break;
              case "MAJOR":
                $nbBugMajor=$count[0]["Total"];
                break;
              case "MINOR":
                $nbBugMinor=$count[0]["Total"];
                break;
              default: break;
         }
        }
        if ( $type=="VULNERABILITY") {
          switch ($severity) {
              case "BLOCKER":
                $nbVulnerabilityBlocker=$count[0]["Total"];
                break;
              case "CRITICAL":
                $nbVulnerabilityCritical=$count[0]["Total"];
                break;
              case "INFO":
                $nbVulnerabilityInfo=$count[0]["Total"];
                break;
              case "MAJOR":
                $nbVulnerabilityMajor=$count[0]["Total"];
                break;
              case "MINOR":
                $nbVulnerabilityMinor=$count[0]["Total"];
                break;
              default: break;
           }
        }

        if ( $type=="CODE_SMELL") {
          switch ($severity) {
              case "BLOCKER":
                $nbCodeSmellBlocker=$count[0]["Total"];
                break;
              case "CRITICAL":
                $nbCodeSmellCritical=$count[0]["Total"];
                break;
              case "INFO":
                $nbCodeSmellInfo=$count[0]["Total"];
                break;
              case "MAJOR":
                $nbCodeSmellMajor=$count[0]["Total"];
                break;
              case "MINOR":
                $nbCodeSmellMinor=$count[0]["Total"];
                break;
              default: break;
           }
        }
      }
    }
    // on calcul la dette technique totale et par type
   $sql="SELECT sum(debt) as total FROM temp_anomalie";
   $result_set=$em->getConnection()->prepare($sql)->executeQuery();
   $result_response=$result_set->fetchAllAssociative();
   $result_debt=$this->minutes_to(intVal($result_response[0]['total']));

   $sql="SELECT sum(debt) as total FROM temp_anomalie WHERE type='BUG'";
   $result_set=$em->getConnection()->prepare($sql)->executeQuery();
   $result_response=$result_set->fetchAllAssociative();
   $result_debt_bug=$this->minutes_to(intVal($result_response[0]['total']));

   $sql="SELECT sum(debt) as total FROM temp_anomalie WHERE type='VULNERABILITY'";
   $result_set=$em->getConnection()->prepare($sql)->executeQuery();
   $result_response=$result_set->fetchAllAssociative();
   $result_debt_vulnerability=$this->minutes_to(intVal($result_response[0]['total']));

   $sql="SELECT sum(debt) as total FROM temp_anomalie WHERE type='CODE_SMELL'";
   $result_set=$em->getConnection()->prepare($sql)->executeQuery();
   $result_response=$result_set->fetchAllAssociative();
   $result_debt_code_smell=$this->minutes_to(intVal($result_response[0]['total']));

   $issue = new AnomalieDetails();
   $issue->setMavenKey($request->get("maven_key"));
   $issue->setSetup($setup);
   $issue->setProjectName($project_name);
   $issue->setProjectAnalyse($project_analyse);
   $issue->setProjectVersion($project_version);
   $issue->setProjectDate($project_date);

   $issue->setTotalDebt($result_debt);
   $issue->setTotalDebtBug($result_debt_bug);
   $issue->setTotalDebtVulnerability($result_debt_vulnerability);
   $issue->setTotalDebtCodeSmell($result_debt_code_smell);

   $issue->setBugBlocker($nbBugBlocker);
   $issue->setBugCritical($nbBugCritical);
   $issue->setBugInfo($nbBugInfo);
   $issue->setBugMajor($nbBugMajor);
   $issue->setBugMinor($nbBugMinor);
   $issue->setVulnerabilityBlocker($nbVulnerabilityBlocker);
   $issue->setVulnerabilityCritical($nbVulnerabilityCritical);
   $issue->setVulnerabilityInfo($nbVulnerabilityInfo);
   $issue->setVulnerabilityMajor($nbVulnerabilityMajor);
   $issue->setVulnerabilityMinor($nbVulnerabilityMinor);
   $issue->setCodeSmellBlocker($nbCodeSmellBlocker);
   $issue->setCodeSmellCritical($nbCodeSmellCritical);
   $issue->setCodeSmellInfo($nbCodeSmellInfo);
   $issue->setCodeSmellMajor($nbCodeSmellMajor);
   $issue->setCodeSmellMinor($nbCodeSmellMinor);

   $issue->setDateEnregistrement($date);
   $em->persist($issue);
   $em->flush();

   $response = new JsonResponse();
   return $response->setData(["info"=>"Consolidation terminée.", Response::HTTP_OK]);
  }

/**
* description
* Récupère les notes pour la fiabilité, la sécurité et les mauvaises pratiques.
* http://{url}https://{url}/api/measures/search_history?component={key}}&metrics={type}&ps=1000
* On récupère que la première page soit 1000 résultat max.
* Les valeurs possibles pour {type} sont : reliability_rating,security_rating,sqale_rating
*/
#[Route('/api/projet/historique/note', name: 'projet_historique_note', methods: ['GET'])]
  public function historique_note_ajout(EntityManagerInterface $em, Request $request): response
  {

    $url=$this->getParameter(self::$sonarUrl)."/api/measures/search_history?component=".$request->get('maven_key')."&metrics=".$request->get('type')."_rating&ps=1000";

    // on appel le client http
    $result=$this->http_client($url);

    $date= new DateTime();
    $nombre=$result["paging"]["total"];
    $mesures=$result["measures"][0]["history"];

    // Enregistrement des nouvelles valeurs
    foreach($mesures as $mesure ) {
      $sql="INSERT OR IGNORE INTO notes (maven_key, type, date, value, date_enregistrement)  VALUES ('".$request->get('maven_key')."', '".$request->get('type') ."', '".$mesure["date"] ."', '". $mesure["value"] ."', '".$date->format(self::$dateFormat)."')";
      $em->getConnection()->prepare($sql)->executeQuery();
    }

   if ($request->get('type')=="reliability"){$type="Fiabilité";}
   if ($request->get('type')=="security"){$type="Sécurité";}
   if ($request->get('type')=="sqale"){$type="Mauvaises Pratiques";}

   $response = new JsonResponse();
   return $response->setData(["nombre"=>$nombre, "type"=>$type, Response::HTTP_OK]);
  }

  /**
  * description
  * Récupère le top 10 OWASP
  * http://{url}/api/issues/search?componentKeys={key}&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10
  * Attention une faille peut être comptée deux fois ou plus, cela dépend du tag. Donc il  est possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10
  */
  #[Route('/api/projet/issues/owasp', name: 'projet_issues_owasp', methods: ['GET'])]
  public function issues_owasp_ajout(EntityManagerInterface $em, Request $request): response
    {
      $url=$this->getParameter(self::$sonarUrl)."/api/issues/search?componentKeys=".$request->get('maven_key')."&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10";

      $result=$this->http_client($url);
      $date= new DateTime();
      $owasp=[$result["total"]];
      $effortTotal=$result["effortTotal"];

      for ($a=0; $a < 10; $a++)
        {
          switch ($result["facets"][0]["values"][$a]["val"]) {
            case 'a1': $owasp[1] = $result["facets"][0]["values"][$a]["count"];
              break;
            case 'a2': $owasp[2] = $result["facets"][0]["values"][$a]["count"];
              break;
            case 'a3': $owasp[3] = $result["facets"][0]["values"][$a]["count"];
              break;
            case 'a4': $owasp[4] = $result["facets"][0]["values"][$a]["count"];
              break;
            case 'a5': $owasp[5] = $result["facets"][0]["values"][$a]["count"];
              break;
            case 'a6': $owasp[6] = $result["facets"][0]["values"][$a]["count"];
              break;
            case 'a7': $owasp[7] = $result["facets"][0]["values"][$a]["count"];
              break;
            case 'a8': $owasp[8] = $result["facets"][0]["values"][$a]["count"];
              break;
            case 'a9': $owasp[9] = $result["facets"][0]["values"][$a]["count"];
              break;
            case 'a10': $owasp[10] = $result["facets"][0]["values"][$a]["count"];
              break;
            default : echo "OWASP TOP 10"; break;
            }
        }

      $a1_blocker = 0; $a1_critical = 0; $a1_major = 0; $a1_info = 0; $a1_minor = 0;
      $a2_blocker = 0; $a2_critical = 0; $a2_major = 0; $a2_info = 0; $a2_minor = 0;
      $a3_blocker = 0; $a3_critical = 0; $a3_major = 0; $a3_info = 0; $a3_minor = 0;
      $a4_blocker = 0; $a4_critical = 0; $a4_major = 0; $a4_info = 0; $a4_minor = 0;
      $a5_blocker = 0; $a5_critical = 0; $a5_major = 0; $a5_info = 0; $a5_minor = 0;
      $a6_blocker = 0; $a6_critical = 0; $a6_major = 0; $a6_info = 0; $a6_minor = 0;
      $a7_blocker = 0; $a7_critical = 0; $a7_major = 0; $a7_info = 0; $a7_minor = 0;
      $a8_blocker = 0; $a8_critical = 0; $a8_major = 0; $a8_info = 0; $a8_minor = 0;
      $a9_blocker = 0; $a9_critical = 0; $a9_major = 0; $a9_info = 0; $a9_minor = 0;
      $a10_blocker = 0; $a10_critical = 0; $a10_major = 0; $a10_info = 0; $a10_minor = 0;

      if ($result["total"]!=0)
      {
        foreach ($result["issues"] as $issue)
        {
          $severity = $issue["severity"];
          if ($issue["status"] == 'OPEN' || $issue["status"] == 'CONFIRMED' || $issue["status"] == 'REOPENED') {
            if (preg_match("/owasp-a/is", var_export($issue["tags"], true)) !=0)
              {
                foreach ($issue["tags"] as $tag) {
                  switch ($tag) {
                    case "owasp-a1":
                      if ($severity == 'BLOCKER') { $a1_blocker++; }
                      if ($severity == 'CRITICAL') { $a1_critical++; }
                      if ($severity == 'MAJOR') { $a1_major++; }
                      if ($severity == 'INFO') { $a1_info++; }
                      if ($severity == 'MINOR') { $a1_minor++; }
                      break;
                    case "owasp-a2":
                      if ($severity == 'BLOCKER') { $a2_blocker++; }
                      if ($severity == 'CRITICAL') { $a2_critical++; }
                      if ($severity == 'MAJOR') { $a2_major++; }
                      if ($severity == 'INFO') { $a2_info++; }
                      if ($severity == 'MINOR') { $a2_minor++; }
                      break;
                    case "owasp-a3":
                      if ($severity == 'BLOCKER') { $a3_blocker++; }
                      if ($severity == 'CRITICAL') { $a3_critical++; }
                      if ($severity == 'MAJOR') { $a3_major++; }
                      if ($severity == 'INFO') { $a3_info++; }
                      if ($severity == 'MINOR') { $a3_minor++; }
                      break;
                    case "owasp-a4":
                      if ($severity == 'BLOCKER') { $a4_blocker++; }
                      if ($severity == 'CRITICAL') { $a4_critical++; }
                      if ($severity == 'MAJOR') { $a4_major++; }
                      if ($severity == 'INFO') { $a4_info++; }
                      if ($severity == 'MINOR') { $a4_minor++; }
                      break;
                    case "owasp-a5":
                      if ($severity == 'BLOCKER') { $a5_blocker++; }
                      if ($severity == 'CRITICAL') { $a5_critical++; }
                      if ($severity == 'MAJOR') { $a5_major++; }
                      if ($severity == 'INFO') { $a5_info++; }
                      if ($severity == 'MINOR') { $a5_minor++; }
                      break;
                    case "owasp-a6":
                      if ($severity == 'BLOCKER') { $a6_blocker++; }
                      if ($severity == 'CRITICAL') { $a6_critical++; }
                      if ($severity == 'MAJOR') { $a6_major++; }
                      if ($severity == 'INFO') { $a6_info++; }
                      if ($severity == 'MINOR') { $a6_minor++; }
                      break;
                    case "owasp-a7":
                      if ($severity == 'BLOCKER') { $a7_blocker++; }
                      if ($severity == 'CRITICAL') { $a7_critical++; }
                      if ($severity == 'MAJOR') { $a7_major++; }
                      if ($severity == 'INFO') { $a7_info++; }
                      if ($severity == 'MINOR') { $a7_minor++; }
                      break;
                    case "owasp-a8":
                      if ($severity == 'BLOCKER') { $a8_blocker++; }
                      if ($severity == 'CRITICAL') { $a8_critical++; }
                      if ($severity == 'MAJOR') { $a8_major++; }
                      if ($severity == 'INFO') { $a8_info++; }
                      if ($severity == 'MINOR') { $a8_minor++; }
                      break;
                    case "owasp-a9":
                      if ($severity == 'BLOCKER') { $a9_blocker++; }
                      if ($severity == 'CRITICAL') { $a9_critical++; }
                      if ($severity == 'MAJOR') { $a9_major++; }
                      if ($severity == 'INFO') { $a9_info++; }
                      if ($severity == 'MINOR') { $a9_minor++; }
                      break;
                    case "owasp-a10":
                      if ($severity == 'BLOCKER') { $a10_blocker++; }
                      if ($severity == 'CRITICAL') { $a10_critical++; }
                      if ($severity == 'MAJOR') { $a10_major++; }
                      if ($severity == 'INFO') { $a10_info++; }
                      if ($severity == 'MINOR') { $a10_minor++; }
                      break;
                    default : break;
                  }
                }
              }
            }
          }
        }

    //on supprime les informations sur le projet
    $sql = "DELETE FROM owasp WHERE maven_key='".$request->get('maven_key')."'";
    $em->getConnection()->prepare($sql)->executeQuery();

    // Enregistre en base
    $owaspTop10 = new Owasp();
    $owaspTop10->setMavenKey($request->get("maven_key"));
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

    $owaspTop10->setA1Blocker($a1_blocker);
    $owaspTop10->setA1Critical($a1_critical);
    $owaspTop10->setA1Major($a1_major);
    $owaspTop10->setA1Info($a1_info);
    $owaspTop10->setA1Minor($a1_minor);

    $owaspTop10->setA2Blocker($a2_blocker);
    $owaspTop10->setA2Critical($a2_critical);
    $owaspTop10->setA2Major($a2_major);
    $owaspTop10->setA2Info($a2_info);
    $owaspTop10->setA2Minor($a2_minor);

    $owaspTop10->setA3Blocker($a3_blocker);
    $owaspTop10->setA3Critical($a3_critical);
    $owaspTop10->setA3Major($a3_major);
    $owaspTop10->setA3Info($a3_info);
    $owaspTop10->setA3Minor($a3_minor);

    $owaspTop10->setA4Blocker($a4_blocker);
    $owaspTop10->setA4Critical($a4_critical);
    $owaspTop10->setA4Major($a4_major);
    $owaspTop10->setA4Info($a4_info);
    $owaspTop10->setA4Minor($a4_minor);

    $owaspTop10->setA5Blocker($a5_blocker);
    $owaspTop10->setA5Critical($a5_critical);
    $owaspTop10->setA5Major($a5_major);
    $owaspTop10->setA5Info($a5_info);
    $owaspTop10->setA5Minor($a5_minor);

    $owaspTop10->setA6Blocker($a6_blocker);
    $owaspTop10->setA6Critical($a6_critical);
    $owaspTop10->setA6Major($a6_major);
    $owaspTop10->setA6Info($a6_info);
    $owaspTop10->setA6Minor($a6_minor);

    $owaspTop10->setA7Blocker($a7_blocker);
    $owaspTop10->setA7Critical($a7_critical);
    $owaspTop10->setA7Major($a7_major);
    $owaspTop10->setA7Info($a7_info);
    $owaspTop10->setA7Minor($a7_minor);

    $owaspTop10->setA8Blocker($a8_blocker);
    $owaspTop10->setA8Critical($a8_critical);
    $owaspTop10->setA8Major($a8_major);
    $owaspTop10->setA8Info($a8_info);
    $owaspTop10->setA8Minor($a8_minor);

    $owaspTop10->setA9Blocker($a9_blocker);
    $owaspTop10->setA9Critical($a9_critical);
    $owaspTop10->setA9Major($a9_major);
    $owaspTop10->setA9Info($a9_info);
    $owaspTop10->setA9Minor($a9_minor);

    $owaspTop10->setA10Blocker($a10_blocker);
    $owaspTop10->setA10Critical($a10_critical);
    $owaspTop10->setA10Major($a10_major);
    $owaspTop10->setA10Info($a10_info);
    $owaspTop10->setA10Minor($a10_minor);

    $owaspTop10->setDateEnregistrement($date);
    $em->persist($owaspTop10);
    $em->flush();

    $response = new JsonResponse();
    return $response->setData(["owasp"=>$result["total"], Response::HTTP_OK]);
  }

/**
* description
* Traitement des hotspot de type owasp pour sonarqube 8.9 et >
* http://{url}/api/hotspots/search?projectKey={key}&ps=500&p=1
* On récupère les failles a examiner. Les clés sont uniques (i.e. on ne se base pas sur les tags).
*/
#[Route('/api/projet/hotspot', name: 'projet_hotspot', methods: ['GET'])]
  public function hotspot_ajout(EntityManagerInterface $em, Request $request): response
    {
      $url=$this->getParameter(self::$sonarUrl)."/api/hotspots/search?projectKey=".$request->get('maven_key')."&ps=500&p=1";

      $result=$this->http_client($url);
      $date= new DateTime();
      $niveau=0;

      if ($result["paging"]["total"]!=0){
        // On supprime  les enregistrement correspondant à la clé
        $sql = "DELETE FROM hotspots WHERE maven_key='".$request->get('maven_key')."'";
        $em->getConnection()->prepare($sql)->executeQuery();

        foreach ( $result["hotspots"] as $value)
         {
          if ($value["vulnerabilityProbability"] == "HIGH") { $niveau=1; }
          if ($value["vulnerabilityProbability"] == "MEDIUM") { $niveau=2; }
          if ($value["vulnerabilityProbability"] == "LOW") { $niveau=3; }

          $hotspot= new  Hotspots();
          $hotspot->setMavenKey($request->get('maven_key'));
          $hotspot->setKey($value["key"]);
          $hotspot->setProbability($value["vulnerabilityProbability"]);
          $hotspot->setStatus($value["status"]);
          $hotspot->setNiveau($niveau);
          $hotspot->setDateEnregistrement($date);
          $em->persist($hotspot);
          $em->flush();
         }
      }

      $response = new JsonResponse();
      return $response->setData(["hotspots"=>$result["paging"]["total"], Response::HTTP_OK]);
    }

  /**
  * description
  * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
  * http://{url}/api/hotspots/search?projectKey={key}{owasp}&ps=500&p=1
  * {key} = la clé du projet
  * {owasp} = le type de faille (a1, a2, etc...)
  * si le paramétre owasp est égale à a0 alors on supprime les enregistrements pour la clé
  */
  #[Route('/api/projet/hotspot/owasp', name: 'projet_hotspot_owasp', methods: ['GET'])]
   public function hotspot_owasp_ajout(EntityManagerInterface $em, Request $request): response
    {
      $response = new JsonResponse();
      if ($request->get('owasp')=='a0') {
         // On supprime  les enregistrements correspondant à la clé
         $sql = "DELETE FROM hotspot_owasp WHERE maven_key='".$request->get('maven_key')."'";
         $em->getConnection()->prepare($sql)->executeQuery();
         return $response->setData(["info"=>"effacement", Response::HTTP_OK]);
        }

      $url=$this->getParameter(self::$sonarUrl)."/api/hotspots/search?projectKey=".$request->get('maven_key')."&owaspTop10=".$request->get('owasp')."&ps=500&p=1";

      $result=$this->http_client($url);
      $date= new DateTime();
      $niveau=0;

      if ($result["paging"]["total"]!=0){
        foreach ( $result["hotspots"] as $value) {
          if ($value["vulnerabilityProbability"] == "HIGH") { $niveau=1; }
          if ($value["vulnerabilityProbability"] == "MEDIUM") { $niveau=2; }
          if ($value["vulnerabilityProbability"] == "LOW") { $niveau=3; }

          $hotspot= new  HotspotOwasp();
          $hotspot->setMavenKey($request->get('maven_key'));
          $hotspot->setMenace($request->get('owasp'));
          $hotspot->setProbability($value["vulnerabilityProbability"]);
          $hotspot->setStatus($value["status"]);
          $hotspot->setNiveau($niveau);
          $hotspot->setDateEnregistrement($date);
          $em->persist($hotspot);
          $em->flush();
         }
       }
      else {
          $hotspot= new  HotspotOwasp();
          $hotspot->setMavenKey($request->get('maven_key'));
          $hotspot->setMenace($request->get('owasp'));
          $hotspot->setProbability("NC");
          $hotspot->setStatus("NC");
          $hotspot->setNiveau("0");
          $hotspot->setDateEnregistrement($date);
          $em->persist($hotspot);
          $em->flush();
        }

      return $response->setData(["info"=>"enregistrement","hotspots"=>$result["paging"]["total"], Response::HTTP_OK]);
    }


  /**
  * description
  * Fonction privée qui récupère le détail d'un hotspot en fonction de sa clé
  */
  function hotspot_details($maven_key, $key)
  {
    $url=$this->getParameter(self::$sonarUrl)."/api/hotspots/show?hotspot=".$key;

    $hotspot=$this->http_client($url);
    $date= new DateTime();

    // Si le niveau de sévérité n'est pos connu, on lui affecte la valeur MAJOR.
    if (empty($hotspot["rule"]["vulnerabilityProbability"]))
      { $severity = "MAJOR"; }
      else
      {
        $severity = $hotspot["rule"]["vulnerabilityProbability"];
      }

    $frontend=0; $backend=0; $batch=0;
    // nom du projet
    $app=explode(":",$maven_key);

    $status=$hotspot["status"];
    $file=str_replace($maven_key.":", "", $hotspot["component"]["key"]);
    $module=explode("/", $file);

    // Application Frontend
    if ($module[0]==$app[1]."-presentation") {$frontend++; }         //Legacy
    if ($module[0]==$app[1]."-presentation-commun") {$frontend++; }  //Legacy
    if ($module[0]==$app[1]."-presentation-ear") {$frontend++; }     //NUDLe | Legacy
    if ($module[0]==$app[1]."-webapp") {$frontend++; }               //NUDLe | Legacy

    // Application Backend
    if ($module[0]==$app[1]."-metier") {$backend++; }                //Legacy
    if ($module[0]==$app[1]."-common") {$backend++; }                //Legacy | NUDLe
    if ($module[0]==$app[1]."-api") {$backend++; }                   //NUDLe
    if ($module[0]==$app[1]."-dao") {$backend++; }                   //NUDLe
    if ($module[0]==$app[1]."-metier-ear") {$backend++; }            //Legacy | NUDLe
    if ($module[0]==$app[1]."-service") {$backend++; }               //NUDLe
    if ($module[0]==$app[1]."-serviceweb") {$backend++; }            //NUDLe
    if ($module[0]==$app[1]."-middleoffice") {$backend++; }          //NUDLe

    // Application Batch
    if ($module[0]==$app[1]."-batchs") {$batch++; }                  //Legacy | NUDLe

    if (empty($hotspot["line"])) {$line=0;} else {$line=$hotspot["line"];}
    $rule = $hotspot["rule"] ? $hotspot["rule"]["name"] : "/";
    $message=$hotspot["message"];
    // On affiche pas la description, même si on la en base, car on pointe sur le serveur sonarqube directement
    //$description=$hotspot["rule"]["riskDescription"];
    $key=$hotspot["key"];
    $date_enregistrement=$date;

    return ["severity"=>$severity, "status"=>$status, "frontend"=>$frontend, "backend"=>$backend, "batch"=>$batch, "file"=>$file, "line"=>$line, "rule"=>$rule, "message"=>$message, "key"=>$key, "date_enregistrement"=>$date_enregistrement];
  }

/**
  * description
  * Récupère le détails des hotspots et les enregistre dans la table Hotspots_details
  * http://{url}/api/hotspot/details{maven_key};
  * {maven_key} = la clé du projet
  */
  #[Route('/api/projet/hotspot/details', name: 'projet_hotspot_details', methods: ['GET'])]
  public function hotspot_details_ajout(EntityManagerInterface $em, Request $request): response
   {
    $response = new JsonResponse();

    // On réfcupre la liste des hotspots
    $sql = "SELECT * FROM hotspots WHERE maven_key='".$request->get('maven_key')."' AND status='TO_REVIEW' ORDER BY niveau";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $liste=$r->fetchAllAssociative();
    if (empty($liste)){ return$response->setData(["code"=>406, Response::HTTP_OK]); }

    // on efface la table hotspots_details
    // On supprime les données de la table hotspots_details pour le projet
    $sql = "DELETE FROM hotspot_details WHERE maven_key='".$request->get('maven_key')."'";
    $em->getConnection()->prepare($sql)->executeQuery();

    /* On boucle sur les clés pour récupérer le détails du hotspot
     * On envoie la clé du projet et la clé du hotspot
     */
    foreach($liste as $elt) {
        $key=$this->hotspot_details($request->get('maven_key') ,$elt["key"]);
        $details= new  HotspotDetails();
        $details->setMavenKey($request->get('maven_key'));
        $details->setSeverity($key["severity"]);
        $details->setStatus($key["status"]);
        $details->setFrontend($key["frontend"]);
        $details->setBackend($key["backend"]);
        $details->setBatch($key["batch"]);
        $details->setFile($key["file"]);
        $details->setLine($key["line"]);
        $details->setRule($key["rule"]);
        $details->setMessage($key["message"]);
        $details->setDescription($key["description"]);
        $details->setKey($key["key"]);
        $details->setDateEnregistrement($key["date_enregistrement"]);
        $em->persist($details);
        $em->flush();
       }

    return $response->setData([Response::HTTP_OK]);
   }

  /**
  * description
  * On récupère la liste des fichiers ayant fait l'objet d'un @@supresswarning ou d'un noSONAR
  * http://{url}api/issues/search?componentKeys={key}&rules={rules}&ps=500&p=1
  * {key} = la clé du projet
  * {rules} = java:S1309 et java:NoSonar
  */
  #[Route('/api/projet/nosonar/details', name: 'projet_nosonar', methods: ['GET'])]
   public function projet_nosonar_ajout(EntityManagerInterface $em, Request $request): response
    {
      $url=$this->getParameter(self::$sonarUrl)."/api/issues/search?componentKeys=".$request->get('maven_key')."&rules=java:S1309,java:NoSonar&ps=500&p=1";

      $result=$this->http_client($url);
      $date= new DateTime();

      // On supprime les données du projet de la table NoSonar
      $sql = "DELETE FROM no_sonar WHERE maven_key='".$request->get('maven_key')."'";
      $em->getConnection()->prepare($sql)->executeQuery();

      if ($result["paging"]["total"]!=0){
        foreach ( $result["issues"] as $issue) {
          $nosonar= new NoSonar();
          $nosonar->setMavenKey($request->get('maven_key'));
          $nosonar->setRule($issue["rule"]);
          $component=str_replace($request->get('maven_key').":", "", $issue["component"]);
          $nosonar->setComponent($component);
          if (empty($issue["line"])) { $line=0; } else { $line=$issue["line"]; }
          $nosonar->setLine($line);
          $nosonar->setLine($issue["line"]);
          $nosonar->setDateEnregistrement($date);
          $em->persist($nosonar);
          $em->flush();
         }
       }

      $response = new JsonResponse();
      return $response->setData(["nosonar"=>$result["paging"]["total"], Response::HTTP_OK]);
    }

  /**
  * description
  * Enregistremnt des données du projet
  */
  #[Route('/api/enregstrement', name: 'enregistrement', methods: ['PUT'])]
  public function enregistrement(EntityManagerInterface $em, Request $request): response
   {
    $date= new DateTime();

    $save= new historique();
    $save->setMavenKey($request->get('maven_key'));
    $save->setNomProjet($request->get('nom_projet'));
    $save->setClefProjet($request->get('clef_projet'));
    $save->setVersionRelease($request->get('version_release'));
    $save->setVersionSnaphot($request->get('version_snaphot'));
    $save->setVersion($request->get('version'));
    $save->setDateVersion($request->get('date_version'));
    $save->setSupressWarning($request->get('supress_warning'));
    $save->setNoSonar($request->get('no_sonar'));
    $save->setNombreLigne($request->get('nombre_ligne'));
    $save->setCouverture($request->get('couverture'));
    $save->setDuplication($request->get('duplication'));
    $save->setTestsUnitaires($request->get('tests_unitaires'));
    $save->setNombreDefaut($request->get('nombre_defaut'));
    $save->setDette($request->get('dette'));
    $save->setDetteBug($request->get('dette_bug'));
    $save->setDetteVulnerability($request->get('dette_vulnerability'));
    $save->setDetteCode_smell($request->get('dette_code_smell'));
    $save->setNombreBug($request->get('nombre_bug'));
    $save->setNombreVulnerability($request->get('nombre_vulnerability'));
    $save->setNombreCode_smell($request->get('nombre_code_smell'));
    $save->setBugBloquant($request->get('bug_bloquant'));
    $save->bug_critique($request->get('bug_critique'));
    $save->setBugInfo($request->get('bug_info'));
    $save->setBugMajeur($request->get('bug_majeur'));
    $save->setBugMineur($request->get('bug_mineur'));
    $save->setVulnerabiliteBloquante($request->get('vulnerabilite_bloquante')); $save->setVulnerabiliteCritique($request->get('vulnerabilite_critique'));
    $save->setVulnerabiliteInfo($request->get('vulnerabilite_info'));
    $save->setVulnerabiliteMajeure($request->get('vulnerabilite_majeure'));
    $save->setVulnerabiliteMineure($request->get('vulnerabilite_mineure'));
    $save->setCode_smellBloquant($request->get('code_smell_bloquant'));
    $save->setCode_smellCritical($request->get('code_smell_critical'));
    $save->setCode_smellInfo($request->get('code_smell_info'));
    $save->setCode_smellMajeur($request->get('code_smell_majeur'));
    $save->setCode_smellMineur($request->get('code_smell_mineur'));
    $save->setNoteReliability($request->get('note_reliability'));
    $save->setNoteSecurity($request->get('note_security'));
    $save->setNoteSqale($request->get('note_sqale'));
    $save->setNoteHotspot($request->get('note_hotspot'));
    $save->setHotspotHigh($request->get('hotspot_high'));
    $save->setHotpostMedium($request->get('hotspot_medium'));
    $save->setHotspotLow($request->get('hotspot_low'));
    $save->setHotspotTotal($request->get('hotspot_total'));
    $save->setDateEnregistrement($date);
    $em->persist($save);
    $em->flush();

    $response = new JsonResponse();
    return $response->setData(["info"=>"KO", Response::HTTP_OK]);

  }
}
