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

use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;
use DateTimeZone;
use Symfony\Component\VarDumper\Cloner\Data;

class BoardController extends AbstractController
{

  private $client;

  public function __construct(HttpClientInterface $client) { $this->client = $client; }

  public static $strContentType = 'application/json';
  public static $dateFormat = "Y-m-d H:m:s";
  public static $sonarUrl= "sonar.url";

 /**
  * description
  * http_client
  */
  protected function http_client($url) {
    if (empty($this->getParameter('sonar.token'))) {
      $user=$this->getParameter('sonar.user');
      $password=$this->getParameter('sonar.password');
    } else {
      $user=$this->getParameter('sonar.token');
      $password='';
    }

    $response = $this->client->request(
      'GET', $url, [ 'auth_basic' => [$user, $password], 'timeout' => 45,
      'headers' => [ 'Accept' => static::$strContentType, 'Content-Type' => static::$strContentType]
     ]);

    if (200 !== $response->getStatusCode()) {
        if ($response->getStatusCode() == 401) {
          throw new \Exception('Erreur d\'Authentification. La clé n\'est pas correcte.');
        }
        else {
          throw new \Exception('Retour de la réponse différent de ce qui est prévu. Erreur '
          .$response->getStatusCode());
        }
      }

    $contentType = $response->getHeaders()['content-type'][0];
    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
    }


  /**
   * description
   * On récupère les résultats Owasp
  */
  #[Route('/suivi', name: 'suivi', methods: ['GET'])]
  public function suivi (EntityManagerInterface $em, Request $request): response {
    $maven_key=$request->get('maven_key');

    // On récupere les failles owasp
    $sql="SELECT nom_projet as nom, version, suppress_warning, no_sonar, nombre_bug as bug, nombre_vulnerability as faille, nombre_code_smell as mauvaise_pratique, hotspot_total as nombre_hotspot, frontend as presentation, backend as metier, batch,
    note_reliability as fiabilite, note_security as securite, note_hotspot,
    note_sqale as maintenabilite FROM historique WHERE maven_key='"
    .$maven_key."' GROUP BY date_version ORDER BY date_version ASC";

    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $dash=$select->fetchAllAssociative();

    // On récupére les anomalies par sévérité
    $sql="SELECT nombre_anomalie_bloquant as bloquant,
    nombre_anomalie_critique as critique, nombre_anomalie_majeur as majeur
    FROM historique where maven_key='"
    .$maven_key."' GROUP BY date_version ORDER BY date_version ASC";

    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $severite=$select->fetchAllAssociative();

    // Graphique
    $sql="SELECT nombre_bug as bug, nombre_vulnerability as secu,
    nombre_code_smell as code_smell, date_version as date
    FROM historique where maven_key='"
    .$maven_key."' GROUP BY date_version ORDER BY date_version ASC";

    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $graph=$select->fetchAllAssociative();

    // On compte le nombre de résultat
    $nl=count((array)$graph);

    for ($i=0; $i<$nl; $i++) {
      $bug[$i]=$graph[$i]["bug"];
      $secu[$i]=$graph[$i]["secu"];
      $code_smell[$i]=$graph[$i]["code_smell"];
      $date[$i]=$graph[$i]["date"];
    }

    // on ajote une valeur null a la fin de chaque serie
    $bug[$nl+1]=0;
    $secu[$nl+1]=0;
    $code_smell[$nl+1]=0;
    $dd = new \DateTime($graph[$nl-1]["date"]);
    $dd->modify('+1 day');
    $ddd=$dd->format('Y-m-d');
    $date[$nl+1]=$ddd;

    return $this->render('dash/index.html.twig',
    [   'dash'=>$dash, 'severite'=>$severite, 'nom'=>$dash[0]["nom"],
        'maven_key'=>$maven_key,
        'data1'=>json_encode($bug), 'data2'=>json_encode($secu),
        'data3'=>json_encode($code_smell), 'labels'=>json_encode($date),
        'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
    ]);
  }

  /*
    * description
    * récupère la liste des projets nom + clé
    * http://{url}}/api/liste/version
  */
  #[Route('/api/liste/version', name: 'liste_version', methods: ['GET'])]
  public function liste_version(EntityManagerInterface $em, Request $request)
  {
    $maven_key=$request->get('maven_key');

    // On récupère les versions et la date pour la clé du projet
    $sql = "SELECT maven_key, project_version as version, date FROM information_projet
    WHERE maven_key='".$maven_key."'";
    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $versions=$select->fetchAllAssociative();

    if (!$versions) {throw $this->createNotFoundException('Oops - Il y a un problème.');}

    $liste=[];
    $id=0;
    //objet = { id: clé, text: "blablabla" };
    foreach ($versions as $version){
      $ts = new DateTime($version['date'], new DateTimeZone('Europe/Paris'));
      $cc=$ts->format("d-m-Y H:i:sO");
      $objet = [ 'id'=> $id,
       'text'=> $version['version']." (".$cc.")"];
      array_push($liste, $objet);
      $id++;
    }
    $response = new JsonResponse();
    return $response->setData(["liste"=>$liste, Response::HTTP_OK]);
  }

 /*
  * description
  * récupère les données d'une version historisée
  * http://{url}}/api/get/version
  */
  #[Route('/api/get/version', name: 'get_version', methods: ['PUT'])]
  public function get_version(EntityManagerInterface $em, Request $request)
  {
    // on décode le body
    $data = json_decode($request->getContent());
    $maven_key=$data->maven_key;
    //on modifiela date de 11-02-2022 16:02:06 à 2022-02-11 16:02:06
    $d=new Datetime($data->date);
    $dd=$d->format('Y-m-d\TH:i:sO');

    $url=$this->getParameter(static::$sonarUrl)."/api/measures/search_history?component=".$maven_key."&metrics=reliability_rating,security_rating,sqale_rating,bugs,vulnerabilities,code_smells,security_hotspots,security_review_rating,".
    "lines,ncloc,coverage,tests,sqale_index,duplicated_lines_density".
    "&from=".urlencode($dd)."&to=".urlencode($dd);

    // on appel le client http
    $result=$this->http_client($url);

  $data=$result["measures"];
  for ($i=0; $i<14; $i++) {
    if ($data[$i]["metric"]==="reliability_rating") {$note_reliability=intval($data[$i]["history"][0]["value"],10);}
    if ($data[$i]["metric"]==="security_rating") {$note_security=intval($data[$i]["history"][0]["value"],10);}
    if ($data[$i]["metric"]==="sqale_rating") {$note_sqale=intval($data[$i]["history"][0]["value"],10);}
    if ($data[$i]["metric"]==="security_review_rating") {$note_hotspots_review=intval($data[$i]["history"][0]["value"],10);}

    if ($data[$i]["metric"]==="bugs") {$bug=intval($data[$i]["history"][0]["value"],10);}
    if ($data[$i]["metric"]==="vulnerabilities") {$vulnerabilities=intval($data[$i]["history"][0]["value"],10);}
    if ($data[$i]["metric"]==="code_smells") {$codesmell=intval($data[$i]["history"][0]["value"],10);}
    if ($data[$i]["metric"]==="security_hotspots") {$hotspots_review=intval($data[$i]["history"][0]["value"],10);}

    if ($data[$i]["metric"]==="lines") {$lines=intval($data[$i]["history"][0]["value"],10);}
    if ($data[$i]["metric"]==="ncloc") {$ncloc=intval($data[$i]["history"][0]["value"],10);}
    if ($data[$i]["metric"]==="duplicated_lines_density") {$duplication=$data[$i]["history"][0]["value"];}
    if ($data[$i]["metric"]==="coverage") {$coverage=$data[$i]["history"][0]["value"];}

    if ($data[$i]["metric"]==="tests" && array_key_exists("value", $data[$i]["history"])) {
      $tests=intval($data[$i]["history"][0]["value"],10); } else { $tests=0;}
    if ($data[$i]["metric"]==="sqale_index") {$dette=intval($data[$i]["history"][0]["value"],10);}
  }

    $response = new JsonResponse();
    return $response->setData([
      'note_reliability'=>$note_reliability,'note_security'=>$note_security,
      'note_sqale'=>$note_sqale, 'note_hotspots_review'=>$note_hotspots_review,
      'bug'=>$bug, 'vulnerabilities'=>$vulnerabilities, 'codesmell'=>$codesmell, 'hotspots_review'=>$hotspots_review, 'lines'=>$lines, 'ncloc'=>$ncloc,
      'duplication'=>$duplication, 'coverage'=>$coverage, 'tests'=>$tests, 'dette'=>$dette,
      Response::HTTP_OK]);
  }

 /*
  * description
  * récupère les données d'une version historisée
  * http://{url}}/api/get/version
  */
  #[Route('/api/suivi/mise-a-jour', name: 'suivi_miseajour', methods: ['PUT'])]
  public function suivi_mise_a_jour(EntityManagerInterface $em, Request $request)
  {
    // on décode le body
    $data = json_decode($request->getContent());
    $date_enregistrement=new Datetime();
    $date_version = new Datetime($data->date);

    // On créé un nouvel objet Json
    $response = new JsonResponse();

    //VALUES ('fr.franceagrimer:hubtiers','4.1.20-RELEASE','05-01-2022 08:41:00','hubtiers',0,0,0,0,50870,25506,0.0,2.6,0,4167,48011,148,7,4012,0,0,0,0,0,0,0,0,'C','C','C','E',5,0,0,0,1,FALSE,'2022-03-27 16:03:21')

    $sql="INSERT OR IGNORE INTO historique
    (maven_key,version,date_version,
    nom_projet,version_release,version_snapshot,suppress_warning,no_sonar,
    nombre_ligne,nombre_ligne_code,couverture,duplication,tests_unitaires,
    nombre_defaut,dette,
    nombre_bug,nombre_vulnerability,nombre_code_smell,
    frontend,backend,batch,
    nombre_anomalie_bloquant,nombre_anomalie_critique,nombre_anomalie_majeur,
    nombre_anomalie_mineur,nombre_anomalie_info,
    note_reliability,note_security,note_sqale,note_hotspot,
    hotspot_total,hotspot_high,hotspot_medium,hotspot_low,
    favori,initial,
    date_enregistrement)
    VALUES ('".$data->maven_key."','".$data->version."','".$date_version->format(static::$dateFormat)."','".
    $data->nom."',0,0,0,0,".
    $data->lines.",".$data->ncloc.",".$data->coverage.",".$data->duplication.",".$data->tests.",".$data->defauts.",".$data->dette.",".
    $data->bug.",".$data->vulnerabilities.",".$data->codesmell.
    ",0,0,0,".
    $data->bloquant.",".$data->critique.",".$data->majeur.",".
    $data->mineur.",".$data->info.",'".
    $data->note_reliability."','".$data->note_security."','".$data->note_sqale."','".$data->note_hotspots_review."',".
    $data->hotspots_review.",0,0,0,".
    "false,".$data->initial.",'".
    $date_enregistrement->format(static::$dateFormat)."')";

    // On excute la requête
    $con=$em->getConnection()->prepare($sql);
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code"=>$e, Response::HTTP_OK]);
    }
    return $response->setData(["code"=>"OK", Response::HTTP_OK]);
  }
}
