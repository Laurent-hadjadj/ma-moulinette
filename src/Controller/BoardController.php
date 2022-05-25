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

// Logger
use Psr\Log\LoggerInterface;

class BoardController extends AbstractController
{

  private $client;

  public function __construct(HttpClientInterface $client)
  {
    $this->client = $client;
  }

  public static $strContentType = 'application/json';
  public static $dateFormat = "Y-m-d H:m:s";
  public static $sonarUrl = "sonar.url";

  /**
   * httpClient
   *
   * @param  mixed $url
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
        'auth_basic' => [$user, $password], 'timeout' => 45,
        'headers' => ['Accept' => static::$strContentType, 'Content-Type' => static::$strContentType]
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

    $contentType = $response->getHeaders()['content-type'][0];
    $logger->INFO('** ContentType *** '.isset($contentType));

    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
  }

  /**
   * suivi
   * On remonte les 10 dernières version + la version initiale
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return response
   */
  #[Route('/suivi', name: 'suivi', methods: ['GET'])]
  public function suivi(EntityManagerInterface $em, Request $request): response
  {
    $mavenKey = $request->get('mavenKey');
    // Tableau de suivi principal
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
    FROM historique WHERE maven_key='${mavenKey}' AND initial=TRUE)
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
    WHERE maven_key='${mavenKey}' AND initial=FALSE
    ORDER BY date_version DESC LIMIT 9)";

    $select = $em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
    $dash = $select->fetchAllAssociative();
    // On récupère les anomalies par sévérité
    $sql = "SELECT * FROM
    (SELECT date_version as date,
    nombre_anomalie_bloquant as bloquant,
    nombre_anomalie_critique as critique,
    nombre_anomalie_majeur as majeur,
    nombre_anomalie_mineur as mineur
    FROM historique
    WHERE maven_key='${mavenKey}' AND initial=TRUE)
    UNION SELECT * FROM
    (SELECT date_version as date,
    nombre_anomalie_bloquant as bloquant,
    nombre_anomalie_critique as critique,
    nombre_anomalie_majeur as majeur,
    nombre_anomalie_mineur as mineur
    FROM historique
    WHERE maven_key='${mavenKey}' AND initial=FALSE
    ORDER BY date_version DESC LIMIT 9)";
    $select = $em->getConnection()->prepare($sql)->executeQuery();
    $severite = $select->fetchAllAssociative();

    // On récupère les anomalies par type et sévérité
    $sql = "SELECT date_version as date, version,
    bug_blocker, bug_critical, bug_major, bug_minor,
    bug_info,
    vulnerability_blocker, vulnerability_critical,
    vulnerability_major, vulnerability_minor,
    vulnerability_info,
    code_smell_blocker, code_smell_critical, code_smell_major,
    code_smell_minor, code_smell_info
    FROM historique
    WHERE maven_key='${mavenKey}'
    ORDER BY date_version DESC LIMIT 9";
    $select = $em->getConnection()->prepare($sql)->executeQuery();
    $details = $select->fetchAllAssociative();

    // Graphique
    $sql = "SELECT nombre_bug as bug, nombre_vulnerability as secu,
    nombre_code_smell as code_smell, date_version as date
    FROM historique where maven_key='${mavenKey}'
    GROUP BY date_version ORDER BY date_version ASC";

    $select = $em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
    $graph = $select->fetchAllAssociative();

    // On compte le nombre de résultat
    $nl = count((array)$graph);

    for ($i = 0; $i < $nl; $i++) {
      $bug[$i] = $graph[$i]["bug"];
      $secu[$i] = $graph[$i]["secu"];
      $codeSmell[$i] = $graph[$i]["code_smell"];
      $date[$i] = $graph[$i]["date"];
    }

    // on ajoute une valeur null a la fin de chaque série
    $bug[$nl + 1] = 0;
    $secu[$nl + 1] = 0;
    $codeSmell[$nl + 1] = 0;
    $dd = new DateTime($graph[$nl - 1]["date"]);
    $dd->modify('+1 day');
    $ddd = $dd->format('Y-m-d');
    $date[$nl + 1] = $ddd;

    return $this->render('dash/index.html.twig',
     [
        'dash' => $dash, 'severite' => $severite, 'details' => $details,
        'nom' => $dash[0]["nom"], 'mavenKey' => $mavenKey,
        'data1' => json_encode($bug), 'data2' => json_encode($secu),
        'data3' => json_encode($codeSmell), 'labels' => json_encode($date),
        'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
       ]
    );
  }

  /**
   * liste_version
   * On récupère la liste des projets nom + clé
   * http://{url}}/api/liste/version
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return void
   */
  #[Route('/api/liste/version', name: 'liste_version', methods: ['GET'])]
  public function listeVersion(EntityManagerInterface $em, Request $request)
  {
    $mavenKey = $request->get('mavenKey');

    // On récupère les versions et la date pour la clé du projet
    $sql = "SELECT maven_key, project_version as version, date
            FROM information_projet
            WHERE maven_key='" . $mavenKey . "'";
    $select = $em->getConnection()->prepare($sql)->executeQuery();
    $versions = $select->fetchAllAssociative();

    if (!$versions) {
      throw $this->createNotFoundException('Oops - Il y a un problème.');
    }

    $liste = [];
    $id = 0;
    //objet = { id: clé, text: "blablabla" };
    foreach ($versions as $version) {
      $ts = new DateTime($version['date'], new DateTimeZone('Europe/Paris'));
      $cc = $ts->format("d-m-Y H:i:sO");
      $objet = [
        'id' => $id,
        'text' => $version['version'] . " (" . $cc . ")"
      ];
      array_push($liste, $objet);
      $id++;
    }
    $response = new JsonResponse();
    return $response->setData(["liste" => $liste, Response::HTTP_OK]);
  }

  /**
   * get_version
   * On récupère les données disponibles pour une version données
   * http://{url}}/api/get/version
   *
   * @param  mixed $request
   * @return void
   */
  #[Route('/api/get/version', name: 'get_version', methods: ['PUT'])]
  public function getVersion(Request $request, LoggerInterface $logger)
  {
    // on décode le body
    $data = json_decode($request->getContent());
    $mavenKey = $data->mavenKey;

    // On modifie la date de 11-02-2022 16:02:06 à 2022-02-11 16:02:06
    $d = new Datetime($data->date);
    $dd = $d->format('Y-m-d\TH:i:sO');
    $urlencodeDate=urlencode($dd);
    $urlStatic=$this->getParameter(static::$sonarUrl);

    $url = "${urlStatic}/api/measures/search_history?component=
             ${mavenKey}&metrics=reliability_rating,
             security_rating,sqale_rating,bugs,
             vulnerabilities,code_smells,security_hotspots,
             security_review_rating,lines,ncloc,coverage,
             tests,sqale_index,duplicated_lines_density
             &from=${urlencodeDate}&to=${urlencodeDate}";

    // on appel le client http
    $result = $this->httpClient(trim(preg_replace("/\s+/u", " ", $url)), $logger);

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

      // Sur les versions plus anciennes de sonarqube, il n'y avait pas de hostpots.
      // La valeur 6 corsespond à pas de note  (Z)
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

      // Sur les versions plus anciennes de sonarqube, il n'y avait pas de hostpots
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

      // Sur certains projets il n'y a pas de la couverture fonctionnelle
      if ($data[$i]["metric"] === "coverage" && array_key_exists("value", $data[$i]["history"])) {
        $coverage = $data[$i]["history"][0]["value"];
      } else {
        $coverage = 0;
      }

      // Sur certains projets il n'y a pas de tests fonctionnels
      if ($data[$i]["metric"] === "tests" && array_key_exists("value", $data[$i]["history"])) {
        $tests = intval($data[$i]["history"][0]["value"], 10);
      } else {
        $tests = 0;
      }

      if ($data[$i]["metric"] === "sqale_index") {
        $dette = intval($data[$i]["history"][0]["value"], 10);
      }
    }

    $response = new JsonResponse();
    return $response->setData([
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
   * suivi_mise_a_jour
   * Enregistre une version reconstituée dans la table historique
   * http://{url}}/api/suivi/mise-a-jour
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return void
   */
  #[Route('/api/suivi/mise-a-jour', name: 'suivi_miseajour', methods: ['PUT'])]
  public function suiviMiseAJour(EntityManagerInterface $em, Request $request)
  {
    // on décode le body
    $data = json_decode($request->getContent());
    $dateEnregistrement = new Datetime();
    $dateVersion = new Datetime($data->date);

    // On créé un nouvel objet Json
    $response = new JsonResponse();
    // On bind chaque valeur
    $tempoDateVersion=$dateEnregistrement->format(static::$dateFormat);
    $tempoDateEnregistrement=$dateVersion->format(static::$dateFormat);
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
    $tempoBloquant=$data->bloquant;
    $tempoCritique=$data->critique;
    $tempoMajeur=$data->majeur;
    $tempoMineur=$data->mineur;
    $tempoInfo=$data->info;
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
       vulnerability_blocker, vulnerability_critical, vulnerability_major, vulnerability_minor, vulnerability_info,
       code_smell_blocker, code_smell_critical, code_smell_major,
       code_smell_minor, code_smell_info,
       frontend,backend,autre,
       nombre_anomalie_bloquant,nombre_anomalie_critique,
       nombre_anomalie_majeur,
       nombre_anomalie_mineur,nombre_anomalie_info,
       note_reliability,note_security,
       note_sqale,note_hotspot,hotspot_total,
       hotspot_high,hotspot_medium,hotspot_low,
       favori,initial,date_enregistrement)
       VALUES
       ('${tempoMavenKey}','${tempoVersion}',
        '${tempoDateVersion}','${tempoNom}',0,0,0,0,
         ${tempoLines},${tempoNcloc},
         ${tempoCoverage},${tempoDuplication},${tempoTests},
         ${tempoDefauts},${tempoDette},${tempoBug},
         ${tempoVulnerabilities},${tempoCodeSmell},
         -1,-1,-1,-1,-1,
         -1,-1,-1,-1,-1,
         -1,-1,-1,-1,-1,
         0,0,0,
         ${tempoBloquant},${tempoCritique},${tempoMajeur},
         ${tempoMineur},${tempoInfo},'${tempoNoteReliability}',
         '${tempoNoteSecurity}','${tempoNoteSqale}',
         '${tempoNoteHotspotsReview}',${tempoHotspotsReview},
          0,0,0,FALSE, ${tempoInitial},
         '${tempoDateEnregistrement}')";

    // On exécute la requête
    $con = $em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)));
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }
    return $response->setData(["code" => "OK", Response::HTTP_OK]);
  }

  /**
   * dash_version_liste
   * récupère la liste des projets nom + clé
   * http://{url}}/api/dash/liste/version
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return void
   */
  #[Route('/api/dash/version/liste', name: 'dash_version_liste', methods: ['PUT'])]
  public function dashVersionListe(EntityManagerInterface $em, Request $request)
  {
    // on décode le body
    $data = json_decode($request->getContent());
    $mavenKey = $data->mavenKey;

    // On créé un nouvel objet Json
    $response = new JsonResponse();

    // On récupère les versions et la date pour la clé du projet
    $sql = "SELECT maven_key, version, date_version as date, favori, initial
            FROM historique
            WHERE maven_key='${mavenKey}'
            ORDER BY date_version DESC";

    // On exécute la requête
    $con = $em->getConnection()->prepare($sql);
    try {
      $select = $con->executeQuery();
      $version = $select->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }
    return $response->setData(["code" => "OK", "versions" => $version, Response::HTTP_OK]);
  }

  /**
   * dash_version_favori
   * On ajoute ou on supprime la version favorite
   * http://{url}}/api/dash/version/favori
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return void
   */
  #[Route('/api/dash/version/favori', name: 'dash_version_favori', methods: ['PUT'])]
  public function dashVersionFavori(EntityManagerInterface $em, Request $request)
  {
    // on décode le body
    $data = json_decode($request->getContent());
    $mavenKey = $data->mavenKey;
    $favori = $data->favori;
    $date = $data->date;
    $version = $data->version;
    $dateEnregistrement = new DateTime();

    // On créé un nouvel objet Json
    $response = new JsonResponse();

    // On met à jour l'attribut favori de la table historique
    $sql = "UPDATE historique SET favori=" . $favori . " WHERE maven_key='"
           . $mavenKey ."'  AND version='" . $version
           . "'  AND date_version='" . $date . "'";

    // On exécute la requête
    $con = $em->getConnection()->prepare($sql);
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }

    // On modifie (delete/insert) l'attribut favori de la table favori
    // On supprime l'enregistrement
    $sql = "DELETE FROM favori WHERE maven_key='" . $mavenKey . "'";
    $em->getConnection()->prepare($sql)->executeQuery();
    // On ajoute l'enregistrement
    $sql = "INSERT INTO favori ('maven_key', 'favori', 'date_enregistrement')
    VALUES ('" . $mavenKey . "', " . $favori . ", '" .
      $dateEnregistrement->format(static::$dateFormat) . "')";

    // On exécute la requête et on catch l'erreur
    $con = $em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)));
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }

    return $response->setData(["code" => "OK", Response::HTTP_OK]);
  }

  /**
   * dash_version_reference
   * On ajoute ou on supprime la version de reference
   * http://{url}}/api/dash/version/reference
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return void
   */
  #[Route('/api/dash/version/reference', name: 'dash_version_reference', methods: ['PUT'])]
  public function dashVersionReference(EntityManagerInterface $em, Request $request)
  {
    // on décode le body
    $data = json_decode($request->getContent());
    $mavenKey = $data->mavenKey;
    $reference = $data->reference;
    $date = $data->date;
    $version = $data->version;

    // On créé un nouvel objet Json
    $response = new JsonResponse();

    // On récupère les versions et la date pour la clé du projet
    $sql = "UPDATE historique SET initial=${reference}
            WHERE maven_key='${mavenKey}'
                  AND version='${version}'
                  AND date_version='${date}'";
    // On exécute la requête
    $con = $em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)));
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }

    return $response->setData(["code" => "OK", Response::HTTP_OK]);
  }

  /**
   * dash_version_poubelle
   * On supprime la version de historique
   * http://{url}}/api/dash/version/poubelle
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return void
   */
  #[Route('/api/dash/version/poubelle', name: 'dash_version_poubelle', methods: ['PUT'])]
  public function dashVersionPoubelle(EntityManagerInterface $em, Request $request)
  {
    // on décode le body
    $data = json_decode($request->getContent());
    $mavenKey = $data->mavenKey;
    $date = $data->date;
    $version = $data->version;

    // On créé un nouvel objet Json
    $response = new JsonResponse();

    // On surpprime de la table historique le projet
    $sql = "DELETE FROM historique
            WHERE maven_key='${mavenKey}
                  AND version='${version}'
                  AND date_version='${date}'";

    // On excute la requête
    $con = $em->getConnection()->prepare($sql);
    try {
      $con->executeQuery();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
    }

    return $response->setData(["code" => "OK", Response::HTTP_OK]);
  }
}
