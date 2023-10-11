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

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;

/** Accès aux tables SLQLite */
use Doctrine\ORM\EntityManagerInterface;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description SuiviController]
 */
class SuiviController extends AbstractController
{
  /** Définition des constantes */
  public static $strContentType = 'application/json';
  public static $dateFormat = "Y-m-d H:i:s";
  public static $sonarUrl = "sonar.url";
  public static $europeParis = "Europe/Paris";
  public static $regex = "/\s+/u";
  public static $erreurMavenKey="La clé maven est vide!";

  /**
   * [Description for __construct]
   *
   * Created at: 15/12/2022, 22:34:06 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct(
    private EntityManagerInterface $em,
    private LoggerInterface $logger
    )
  {
    $this->em = $em;
    $this->logger = $logger;
  }

  /**
   * [Description for suivi]
   * On remonte les 10 dernières version + la version initiale
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 22:34:25 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/suivi', name: 'suivi', methods: ['GET'])]
  public function suivi(Request $request): response
  {
    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    /** On on vérifie si on a activé le mode test */
    if (is_null($request->get('mode'))) {
      $mode="null";
    } else {
      $mode = $request->get('mode');
    }

    /** On récupère la clé du projet */
    $mavenKey = $request->get('mavenKey');

    /** On teste si la clé est valide */
    if (is_null($mavenKey) && $mode==="TEST") {
      return $response->setData(["mode"=>$mode, "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    /** Tableau de suivi principal */
    $sql = "SELECT * FROM
    (SELECT nom_projet as nom, date_version as date, version,
    suppress_warning, no_sonar, nombre_bug as bug,
    nombre_vulnerability as faille,
    nombre_code_smell as mauvaise_pratique,
    hotspot_total as nombre_hotspot,
    frontend as presentation, backend as metier, autre,
    note_reliability as fiabilite,
    note_security as securite, note_hotspot,
    note_sqale as maintenabilite, initial
    FROM historique
    WHERE maven_key='$mavenKey' AND initial=TRUE)
    UNION SELECT * FROM
    (SELECT nom_projet as nom, date_version as date,
    version, suppress_warning, no_sonar, nombre_bug as bug,
    nombre_vulnerability as faille,
    nombre_code_smell as mauvaise_pratique,
    hotspot_total as nombre_hotspot,
    frontend as presentation, backend as metier,
    autre, note_reliability as fiabilite,
    note_security as securite, note_hotspot,
    note_sqale as maintenabilite, initial
    FROM historique
    WHERE maven_key='$mavenKey' AND initial=FALSE
    ORDER BY date_version DESC LIMIT 9)";

    $select = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();
    $suivi = $select->fetchAllAssociative();

    /** On récupère les anomalies par sévérité */
    $sql = "SELECT * FROM
    (SELECT date_version as date,
    nombre_anomalie_bloquant as bloquant,
    nombre_anomalie_critique as critique,
    nombre_anomalie_majeur as majeur,
    nombre_anomalie_mineur as mineur
    FROM historique
    WHERE maven_key='$mavenKey' AND initial=TRUE)
    UNION SELECT * FROM
    (SELECT date_version as date,
    nombre_anomalie_bloquant as bloquant,
    nombre_anomalie_critique as critique,
    nombre_anomalie_majeur as majeur,
    nombre_anomalie_mineur as mineur
    FROM historique
    WHERE maven_key='$mavenKey' AND initial=FALSE
    ORDER BY date_version DESC LIMIT 9)";

    $select = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();
    $severite = $select->fetchAllAssociative();

    /** On récupère les anomalies par type et sévérité. */
    $sql = "SELECT * FROM
    (SELECT date_version as date, version,
    bug_blocker, bug_critical, bug_major,
    bug_minor, bug_info,
    vulnerability_blocker, vulnerability_critical,
    vulnerability_major, vulnerability_minor,
    vulnerability_info,
    code_smell_blocker, code_smell_critical,
    code_smell_major, code_smell_minor,
    code_smell_info, initial
    FROM historique
    WHERE maven_key='$mavenKey' AND initial=TRUE)
    UNION SELECT * FROM
    (SELECT date_version as date, version,
    bug_blocker, bug_critical, bug_major,
    bug_minor, bug_info,
    vulnerability_blocker, vulnerability_critical,
    vulnerability_major, vulnerability_minor,
    vulnerability_info,
    code_smell_blocker, code_smell_critical,
    code_smell_major, code_smell_minor,
    code_smell_info, initial
    FROM historique
    WHERE maven_key='$mavenKey' AND initial=FALSE
    ORDER BY date_version DESC LIMIT 9)";

    $select = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();
    $details = $select->fetchAllAssociative();

    /** Graphique */
    $sql = "SELECT nombre_bug as bug, nombre_vulnerability as secu,
    nombre_code_smell as code_smell, date_version as date
    FROM historique WHERE maven_key='$mavenKey'
    GROUP BY date_version ORDER BY date_version ASC";

    $select = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();
    $graph = $select->fetchAllAssociative();

    /** On compte le nombre de résultat */
    $nl = count((array)$graph);

    for ($i = 0; $i < $nl; $i++) {
      $bug[$i] = $graph[$i]["bug"];
      $secu[$i] = $graph[$i]["secu"];
      $codeSmell[$i] = $graph[$i]["code_smell"];
      $date[$i] = $graph[$i]["date"];
    }

    /** On ajoute une valeur null a la fin de chaque série. */
    $bug[$nl + 1] = 0;
    $secu[$nl + 1] = 0;
    $codeSmell[$nl + 1] = 0;
    $dd = new DateTime($graph[$nl - 1]["date"]);
    $dd->modify('+1 day');
    $ddd = $dd->format('Y-m-d');
    $date[$nl + 1] = $ddd;

    $render= [
      'suivi' => $suivi, 'severite' => $severite, 'details' => $details,
      'nom' => $suivi[0]["nom"], 'mavenKey' => $mavenKey,
      'data1' => json_encode($bug), 'data2' => json_encode($secu),
      'data3' => json_encode($codeSmell), 'labels' => json_encode($date),
      'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y'),
      Response::HTTP_OK
    ];

    if ($mode==="TEST") {
      return $response->setData($render);
    }

    return $this->render('suivi/index.html.twig', $render);
  }

  /**
   * [Description for listeVersion]
   * On récupère la liste des projets nom + clé
   * http://{url}}/api/liste/version
   *
   * @param Request $request
   *
   * @return Response
   *
   * Created at: 15/12/2022, 22:35:41 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/liste/version', name: 'liste_version', methods: ['GET'])]
  public function listeVersion(Request $request): Response
  {
    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    /** On vérifie si on a activé le mode test */
    if (is_null($request->get('mode'))) {
      $mode="null";
    } else {
      $mode = $request->get('mode');
    }

    /** On récupère la clé du projet */
    $mavenKey = $request->get('mavenKey');
    $exception = $request->get('exception');

    /** On teste si la clé est valide */
    if (is_null($mavenKey) && $mode==="TEST") {
      return $response->setData(["mode"=>$mode, "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    $versions=[];
    /** On récupère les versions et la date pour la clé du projet */
    $sql = "SELECT maven_key, project_version as version, date
            FROM information_projet
            WHERE maven_key='$mavenKey'";
    $select = $this->em->getConnection()->prepare($sql)->executeQuery();

    if (is_null($exception)){
      $versions = $select->fetchAllAssociative();
    }

    if (!$versions) {
      $response->setData([Response::HTTP_NOT_FOUND]);
      throw $this->createNotFoundException('Oops - Il y a un problème.');
    }

    $liste = [];
    $id = 0;
    /** objet = { id: clé, text: "blablabla" }; */
    foreach ($versions as $version) {
      $ts = new DateTime($version['date'], new DateTimeZone(static::$europeParis));
      $cc = $ts->format("d-m-Y H:i:sO");
      $objet = [
        'id' => $id,
        'text' => $version['version'] . " (" . $cc . ")"
      ];
      array_push($liste, $objet);
      $id++;
    }

    if ($mode==="TEST") {
      $httpResponse= $response->setData(['mode'=>'TEST','versions'=>$versions, 'liste' => $liste, Response::HTTP_OK]);
    } else {  $httpResponse = $response->setData(["liste" => $liste, Response::HTTP_OK]); }

    return $httpResponse;
  }

  /**
   * [Description for getVersion]
   * On récupère les données disponibles pour une version données
   * http://{url}}/api/get/version
   *
   * @param Request $request
   *
   * @return Response
   *
   * Created at: 15/12/2022, 22:36:17 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/get/version', name: 'get_version', methods: ['GET','POST'])]
  public function getVersion(Client $client, Request $request): Response
  {
    /** On décode le body */
    $data = json_decode($request->getContent());
    $mavenKey=$data->mavenKey;
    $mode=$data->mode;

    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    /** Réponse HTTP */
    $message=200;

    /** On teste si la clé est valide */
    if ($mavenKey==="TEST" && $mode==="TEST") {
      return $response->setData(["message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    /**  On modifie la date de 11-02-2022 16:02:06 à 2022-02-11 16:02:06 */
    if ($mode==='TEST') {
      $d = new Datetime("11-02-2022 16:02:06");
    } else {
      $d = new Datetime($data->date);
    }

    $dd = $d->format('Y-m-d\TH:i:sO');
    $urlencodeDate=urlencode($dd);
    $urlStatic=$this->getParameter(static::$sonarUrl);

    $url = "$urlStatic/api/measures/search_history?component=
            $mavenKey&metrics=reliability_rating,
            security_rating,sqale_rating,bugs,
            vulnerabilities,code_smells,security_hotspots,
            security_review_rating,lines,ncloc,coverage,
            tests,sqale_index,duplicated_lines_density
            &from=$urlencodeDate&to=$urlencodeDate";

    /** On appel le client http */
    if ($mode!="TEST") {
      $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));
    }

    if ($mode==="TEST") {
      $result=["measures"=>[
        ["metric"=> "lines", "history"=> [["date"=> "2022-04-10T00:00:01+0200", "value"=> 20984]]],
        ["metric"=>"duplicated_lines_density", "history"=>[["date"=> "2022-04-10T00:00:01+0200","value"=>2.6]]],
        ["metric"=>"vulnerabilities", "history"=>[["date"=>"2022-04-10T00:00:02+0200","value"=>0]]],
        ["metric"=>"sqale_index","history"=>[["date"=> "2022-04-10T00:00:03+0200","value"=>15596]]],
        ["metric"=>"reliability_rating","history"=>[["date"=>"2022-04-10T00:00:04+0200","value"=> 3.0]]] ,
        ["metric"=>"code_smells","history"=>[["date"=>"2022-04-10T00:00:05+0200","value"=>3080]]],
        ["metric"=>"bugs","history"=>[["date"=>"2022-04-10T00:00:06+0200", "value"=>43]]],
        ["metric"=>"ncloc", "history"=>[[ "date"=>"2022-04-10T00:07:00+0200", "value"=>17312]]],
        ["metric"=>"security_hotspots", "history"=>[["date"=>"2022-04-10T00:00:08+0200", "value"=>2]]],
        ["metric"=>"sqale_rating", "history"=>[["date"=>"2022-04-10T00:00:09+0200","value"=>3.0]]],
        ["metric"=>"security_rating","history"=>[["date"=>"2022-04-10T00:00:10+0200","value"=>1.0]]],
        ["metric"=>"tests", "history"=> [["date"=>"2022-04-10T00:00:11+0200", "value"=>134]]],
        ["metric"=>"coverage", "history"=>[["date"=>"2022-04-10T00:00:12+0200","value"=>50]]],
        ["metric"=>"security_review_rating", "history"=>[["date"=>"2022-04-10T00:00:13+0200","value"=>5.0]]],
        ]
      ];
    }

    /** Si on récupère un message alors on a un problème. */
    if (array_key_exists("message", $result)){
      return $response->setData(['message' => 404]);
    }

    $data = $result["measures"];
    for ($i = 0; $i < 14; $i++) {
      if ($data[$i]["metric"] === "reliability_rating") {
        $noteReliability = intval($data[$i]["history"][0]["value"], 10);
      }
      if ($data[$i]["metric"] === "security_rating") {
        $noteSecurity = intval($data[$i]["history"][0]["value"], 10);
      }
      if ($data[$i]["metric"] === "sqale_rating") {
        $noteSqale = intval($data[$i]["history"][0]["value"], 10);
      }

      /** Sur les versions plus anciennes de sonarqube, il n'y avait pas de hostpots. */
      /** La valeur 6 corsespond à pas de note  (Z) */
      if ($data[$i]["metric"] === "security_review_rating" &&
          array_key_exists("value", $data[$i]["history"][0])) {
          $noteHotspotsReview = intval($data[$i]["history"][0]["value"], 10);
      }

      if ($data[$i]["metric"] === "security_review_rating" &&
          array_key_exists("value", $data[$i]["history"][0])===false) {
        $noteHotspotsReview = 6;
      }

      if ($data[$i]["metric"] === "bugs") {
        $bug = intval($data[$i]["history"][0]["value"], 10);
      }
      if ($data[$i]["metric"] === "vulnerabilities") {
        $vulnerabilities = intval($data[$i]["history"][0]["value"], 10);
      }
      if ($data[$i]["metric"] === "code_smells") {
        $codeSmell = intval($data[$i]["history"][0]["value"], 10);
      }

      /**  Sur les versions plus anciennes de sonarqube, il n'y avait pas de hostpots */
      if ($data[$i]["metric"] === "security_hotspots" &&
        array_key_exists("value", $data[$i]["history"][0])) {
        $hotspotsReview = intval($data[$i]["history"][0]["value"], 10);
      }
      if ($data[$i]["metric"] === "security_hotspots" &&
        array_key_exists("value", $data[$i]["history"][0])===false) {
        $hotspotsReview = -1;
      }

      if ($data[$i]["metric"] === "lines") {
        $lines = intval($data[$i]["history"][0]["value"], 10);
      }
      if ($data[$i]["metric"] === "ncloc") {
        $ncloc = intval($data[$i]["history"][0]["value"], 10);
      }
      if ($data[$i]["metric"] === "duplicated_lines_density") {
        $duplication = $data[$i]["history"][0]["value"];
      }

      /**  Sur certains projets il n'y a pas de la couverture fonctionnelle */
      if ($data[$i]["metric"] === "coverage" &&
        array_key_exists("value", $data[$i]["history"][0])) {
        $coverage = $data[$i]["history"][0]["value"];
      }

      if ($data[$i]["metric"] === "coverage" &&
      array_key_exists("value", $data[$i]["history"][0])===false) {
        $coverage = 0;
      }

      /**  Sur certains projets il n'y a pas de tests fonctionnels */
      if ($data[$i]["metric"] === "tests" &&
      array_key_exists("value", $data[$i]["history"][0])) {
        $tests = intval($data[$i]["history"][0]["value"], 10);
      }

      if ($data[$i]["metric"] === "tests" &&
      array_key_exists("value", $data[$i]["history"][0])===false) {
        $tests = 0;
      }

      if ($data[$i]["metric"] === "sqale_index") {
        $dette = intval($data[$i]["history"][0]["value"], 10);
      }
    }

  return $response->setData([
        'message'=>$message,
        'noteReliability' => $noteReliability, 'noteSecurity' => $noteSecurity,
        'noteSqale' => $noteSqale, 'noteHotspotsReview' => $noteHotspotsReview,
        'bug' => $bug, 'vulnerabilities' => $vulnerabilities,
        'codeSmell' => $codeSmell, 'hotspotsReview' => $hotspotsReview,
        'lines' => $lines, 'ncloc' => $ncloc,
        'duplication' => $duplication, 'coverage' => $coverage, 'tests' => $tests,
        'dette' => $dette, Response::HTTP_OK
      ]);
  }

  /**
   * [Description for suiviMiseAJour]
   * Enregistre une version reconstituée dans la table historique
   * http://{url}}/api/suivi/mise-a-jour
   *
   * @param Request $request
   *
   * @return Response
   *
   * Created at: 15/12/2022, 22:37:32 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/suivi/mise-a-jour', name: 'suivi_mise_a_jour', methods: ['PUT'])]
  public function suiviMiseAJour(Request $request): Response
  {
    /** On décode le body */
    $data = json_decode($request->getContent());
    $dateEnregistrement = new Datetime();
    $dateEnregistrement->setTimezone(new DateTimeZone(static::$europeParis));
    $dateVersion = new Datetime($data->date);
    $mode=$data->mode;

    /** On créé un nouvel objet Json. */
    $response = new JsonResponse();

    /** On bind chaque valeur. */
    $tempoDateVersion=$dateVersion->format(static::$dateFormat);
    $tempoDateEnregistrement=$dateEnregistrement->format(static::$dateFormat);
    $tempoMavenKey=$data->mavenKey;
    $tempoVersion=$data->version;
    $tempoNom=$data->nom;
    $tempoLines=$data->lines;
    $tempoNcloc=$data->ncloc;
    $tempoCoverage=$data->coverage;
    $tempoDuplication=$data->duplication;
    $tempoTests=$data->tests;
    $tempoDefauts=$data->defauts;
    $tempoDette=$data->dette;
    $tempoBug=$data->bug;
    $tempoVulnerabilities=$data->vulnerabilities;
    $tempoCodeSmell=$data->codeSmell;
    $tempoNoteReliability=$data->noteReliability;
    $tempoNoteSecurity=$data->noteSecurity;
    $tempoNoteSqale=$data->noteSqale;
    $tempoNoteHotspotsReview=$data->noteHotspotsReview;
    $tempoHotspotsReview=$data->hotspotsReview;
    $tempoInitial=$data->initial;

    $sql = "INSERT OR IGNORE INTO historique
      (maven_key,version,date_version,
        nom_projet,version_release,version_snapshot,
        suppress_warning,no_sonar,nombre_ligne,
        nombre_ligne_code,couverture,
        duplication,tests_unitaires,nombre_defaut,dette,
        nombre_bug,nombre_vulnerability,nombre_code_smell,
        bug_blocker, bug_critical, bug_major, bug_minor, bug_info,
        vulnerability_blocker, vulnerability_critical, vulnerability_major,
        vulnerability_minor, vulnerability_info,
        code_smell_blocker, code_smell_critical, code_smell_major,
        code_smell_minor, code_smell_info,
        frontend,backend,autre,
        nombre_anomalie_bloquant,nombre_anomalie_critique,
        nombre_anomalie_majeur,
        nombre_anomalie_mineur,nombre_anomalie_info,
        note_reliability,note_security,
        note_sqale,note_hotspot,hotspot_total,
        hotspot_high,hotspot_medium,hotspot_low,
        initial,date_enregistrement)
      VALUES
      ('$tempoMavenKey','$tempoVersion',
        '$tempoDateVersion','$tempoNom',-1,-1,-1,-1,
        $tempoLines,$tempoNcloc,
        $tempoCoverage,$tempoDuplication,$tempoTests,
        $tempoDefauts,$tempoDette,$tempoBug,
        $tempoVulnerabilities,$tempoCodeSmell,
        -1,-1,-1,-1,-1,
        -1,-1,-1,-1,-1,
        -1,-1,-1,-1,-1,
        -1,-1,-1,
        -1,-1,-1,-1,-1,
        '$tempoNoteReliability','$tempoNoteSecurity','$tempoNoteSqale',
        '$tempoNoteHotspotsReview',$tempoHotspotsReview,
        -1,-1,-1, $tempoInitial,
        '$tempoDateEnregistrement')";

    // On exécute la requête
    $con = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)));
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }
    return $response->setData(["mode"=>$mode, "code" => "OK", Response::HTTP_OK]);
  }

  /**
   * [Description for suiviVersionListe]
   * récupère la liste des projets nom + clé
   * http://{url}}/api/suivi/liste/version
   *
   * @param Request $request
   *
   * @return Response
   *
   * Created at: 15/12/2022, 22:38:29 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/suivi/version/liste', name: 'suivi_version_liste', methods: ['POST'])]
  public function suiviVersionListe(Request $request): Response
  {
    /** On décode le body */
    $data = json_decode($request->getContent());
    $mavenKey = $data->mavenKey;
    $mode=$data->mode;

    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    /** On teste si la clé est valide */
    if ($mavenKey==="null" && $mode==="TEST") {
      return $response->setData([
        "mode"=>$mode, "mavenKey"=>$mavenKey,
        "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    /**  On créé un nouvel objet Json. */
    /**  On récupère les versions et la date pour la clé du projet. */
    $sql = "SELECT maven_key, version, date_version as date, initial
            FROM historique
            WHERE maven_key='$mavenKey'
            ORDER BY date_version DESC";

    /** On exécute la requête. */
    $con = $this->em->getConnection()->prepare($sql);
    try {
      $select = $con->executeQuery();
      $version = $select->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }
    return $response->setData(["code" => "OK", "versions" => $version, Response::HTTP_OK]);
  }

  /**
   * [Description for suiviVersionFavori]
   * On ajoute ou on supprime la version favorite
   * http://{url}}/api/suivi/version/favori
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 22:39:11 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/suivi/version/favori', name: 'suivi_version_favori', methods: ['PUT'])]
  public function suiviVersionFavori(Request $request):response
  {
    /** on décode le body */
    $data = json_decode($request->getContent());
    $mavenKey = $data->mavenKey;
    $favori = $data->favori;
    $date = $data->date;
    $version = $data->version;
    $mode = $data->mode;

    $dateEnregistrement = new DateTime();
    $dateEnregistrement->setTimezone(new DateTimeZone(static::$europeParis));
    $strDateEnregistrement=$dateEnregistrement->format(static::$dateFormat);

    /** On créé un nouvel objet Json */
    $response = new JsonResponse();

    /** On met à jour l'attribut favori de la table historique */
    $sql = "UPDATE historique SET favori=$favori
            WHERE maven_key='$mavenKey'
            AND version='$version'
            AND date_version='$date'";

    /** On exécute la requête */
    $con = $this->em->getConnection()->prepare($sql);
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code"=>$e->getCode(), Response::HTTP_OK]);
    }

    /** On modifie (delete/insert) l'attribut favori de la table favori */
    /** On supprime l'enregistrement */
    $sql = "DELETE FROM favori WHERE maven_key='$mavenKey'";
    $this->em->getConnection()->prepare($sql)->executeQuery();
    /** On ajoute l'enregistrement */
    $sql = "INSERT INTO favori ('maven_key', 'favori', 'date_enregistrement')
    VALUES ('$mavenKey', $favori, '$strDateEnregistrement')";

    /** On exécute la requête et on catch l'erreur */
    $con = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)));
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }

    return $response->setData(["mode"=>$mode, "code" => "OK", Response::HTTP_OK]);
  }

  /**
   * [Description for suiviVersionReference]
   * On ajoute ou on supprime la version de reference
   * http://{url}}/api/suivi/version/reference
   *
   * @param Request $request
   *
   * @return [type]
   *
   * Created at: 15/12/2022, 22:40:34 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/suivi/version/reference', name: 'suivi_version_reference', methods: ['PUT'])]
  public function suiviVersionReference(Request $request)
  {
    // on décode le body
    $data = json_decode($request->getContent());
    $mavenKey = $data->mavenKey;
    $reference = $data->reference;
    $date = $data->date;
    $version = $data->version;
    $mode=$data->mode;

    // On créé un nouvel objet Json
    $response = new JsonResponse();

    // On récupère les versions et la date pour la clé du projet
    $sql = "UPDATE historique SET initial=$reference
            WHERE maven_key='$mavenKey'
                  AND version='$version'
                  AND date_version='$date'";
    // On exécute la requête
    $con = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)));
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }

    return $response->setData(["mode"=>$mode, "code" => "OK", Response::HTTP_OK]);
  }

  /**
   * [Description for suiviVersionPoubelle]
   * On supprime la version de historique
   * On fait PUT pour un DELETE. (i.e on bloque la methode DELETE)
   * http://{url}}/api/suivi/version/poubelle
   *
   * @param Request $request
   *
   * @return [type]
   *
   * Created at: 15/12/2022, 22:41:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/suivi/version/poubelle', name: 'suivi_version_poubelle', methods: ['PUT'])]
  public function suiviVersionPoubelle(Request $request)
  {
    /** on décode le body */
    $data = json_decode($request->getContent());
    $mavenKey = $data->mavenKey;
    $date = $data->date;
    $version = $data->version;
    $mode= $data->mode;

    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    /** On teste si la clé est valide */
    if ($mavenKey==="null" && $mode==="TEST") {
      return $response->setData([
        "mode"=>$mode, "mavenKey"=>$mavenKey,
        "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    /** On surprime de la table historique le projet */
    $sql = "DELETE FROM historique
            WHERE maven_key='$mavenKey'
            AND version='$version'
            AND date_version='$date'";

    /**  On exécute la requête */
    $con = $this->em->getConnection()->prepare($sql);
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["mode"=>$mode, "code" => $e->getCode(), Response::HTTP_OK]);
    }

    return $response->setData(["code" => "OK", "mode"=>$mode, Response::HTTP_OK]);
  }
}
