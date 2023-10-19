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
use Symfony\Component\HttpFoundation\JsonResponse;

/** Securité */
use Symfony\Bundle\SecurityBundle\Security;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

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
use App\Entity\Main\Todo;

use Doctrine\ORM\EntityManagerInterface;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

class ApiProjetController extends AbstractController
{

  /** Définition des constantes */
  public static $strContentType = 'application/json';
  public static $sonarUrl = "sonar.url";
  public static $dateFormat = "Y-m-d H:i:s";
  public static $europeParis = "Europe/Paris";
  public static $apiIssuesSearch = "/api/issues/search?componentKeys=";
  public static $regex = "/\s+/u";
  public static $erreurMavenKey="La clé maven est vide!";
  public static $reference="<strong>[PROJET-002]</strong>";
  public static $message="Vous devez avoir le rôle COLLECTE pour réaliser cette action.";
  public static $statuses="OPEN,REOPENED,TO_REVIEW,IN_REVIEW";
  public static $statusesMin = "OPEN,CONFIRMED,REOPENED,RESOLVED";
  public static $statusesAll = "OPEN,CONFIRMED,REOPENED,RESOLVED,TO_REVIEW,IN_REVIEW";

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
   * [Description for date_to_minute]
   * Fonction privée pour convertir une date au format xxd aah xxmin en minutes
   *
   * @param mixed $str
   *
   * return ($jour * 24 * 60) + ($heure * 60) + intval($minute)
   *
   * Created at: 15/12/2022, 21:25:32 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function date_to_minute($str)
  {
    $jour=$heure=$minute = 0;
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
   * [Description for minutesTo]
   * Converti les minutes en jours, heures et minutes
   *
   * @param mixed $minutes
   *
   * @return string
   *
   * Created at: 15/12/2022, 21:26:17 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
      return $j . "d, " . $h . "h:" . $m . "min";
    } else {
      return $h . "h:" . $m . "min";
    }
  }

  /**
   * [Description for favori]
   * Change le statut du favori pour un projet
   * http://{url}/api/favori?{key}
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:27:08 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/favori', name: 'favori', methods: ['GET'])]
  public function favori(Security $security, Request $request): response
  {
    /** On récupère l'objet User du contexte de sécurité */
    $preference=$security->getUser()->getPreference();
    $courriel=$security->getUser()->getCourriel();

    /** oN créé un objet réponse */
    $response = new JsonResponse();

    /** On on vérifie si on a activé le mode test */
    if (is_null($request->get('mode'))) {
      $mode="null";
    } else {
      $mode = $request->get('mode');
    }

    /** On bind les variables */
    $mavenKey = $request->get('mavenKey');

    /** On teste si la clé est valide */
    if ($mavenKey==="null" && $mode==="TEST") {
      return $response->setData([
        "mode"=>$mode, "mavenKey"=>$mavenKey,
        "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    /** On regarde si la projet est dans les favoris */
    $isFavori=in_array($mavenKey, $preference['favori']);

    /**
     * On le supprime de la liste des favoris s'il exsite dans les préferences
     * Sinon on l'ajoute
     */

     /** On récupéres les préférences */
    $statut=$preference['statut'];
    $projet=$preference['projet'];
    $favori=$preference['favori'];
    $bookmark=$preference['bookmark'];

    if ($isFavori){
      /** on supprime le projet de la liste */
      $nouvelleListeFavori = array_diff($favori, [$mavenKey]);

      $statut['favori']=false;

      /** On met à jour l'objet. */
      $jarray=json_encode([
        'statut'=>$statut,
        'projet'=>$projet,
        'favori'=>$nouvelleListeFavori,
        'bookmark'=>$bookmark
      ]);

      /** On met à jour les préférences. */
      $sql = "UPDATE utilisateur
        SET preference = '$jarray'
        WHERE courriel='$courriel';";
        $trim=trim(preg_replace(static::$regex, " ", $sql));
        $exec=$this->em->getConnection()->prepare($trim)->executeQuery();
      if ($mode!=='TEST'){
          $exec->fetchAllAssociative();
      }
      $statut=0;
    } else {
      /** On ajoute le projet à la liste */
      array_push($preference['favori'], $mavenKey);
      $statut['favori']=true;

      /** On met à jour l'objet. */
      $jarray=json_encode([
        'statut'=>$statut,
        'projet'=>$projet,
        'favori'=>$preference['favori'],
        'bookmark'=>$bookmark
      ]);

      /** On met à jour les préférences. */
      $sql = "UPDATE utilisateur
        SET preference = '$jarray'
        WHERE courriel='$courriel';";
        $trim=trim(preg_replace(static::$regex, " ", $sql));
        $exec=$this->em->getConnection()->prepare($trim)->executeQuery();
      if ($mode!=='TEST'){
          $exec->fetchAllAssociative();
      }
      $statut=1;
    }

    return $response->setData(["mode"=>$mode, "statut"=>$statut, Response::HTTP_OK]);
  }

  /**
   * [Description for favoriCheck]
   * Récupère le statut d'un favori. Le
   * favori est TRUE ou FALSE ou null
   * http://{url}/api/favori/check={key}
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:28:07 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/favori/check', name: 'favori_check', methods: ['GET'])]
  public function favoriCheck(Security $security, Request $request): response
  {
    /** On récupère l'objet User du contexte de sécurité */
    $preference=$security->getUser()->getPreference();

    /** oN créé un objet réponse */
    $response = new JsonResponse();
    $mavenKey = $request->get('mavenKey');

    $favori=in_array($mavenKey, $preference['favori']);
    return $response->setData(["favori" => $favori, Response::HTTP_OK]);
  }

  /**
   * [Description for liste_projet]
   * Récupère la liste des projets nom + clé pour une équipe
   * http://{url}}/api/liste/projet
   *
   * @param Security $security
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:28:51 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/liste/projet', name: 'liste_projet', methods: ['GET'])]
  public function liste_projet(Security $security): response
  {
    /** On créé un objet response */
    $response = new JsonResponse();

    /* On bind les informations utilisateur */
    $equipes=$security->getUser()->getEquipe();

    /** Si l'utilisateur n'est pas rattaché à une équipe on ne charge rien */
    if (empty($equipes)){
      /** On envoi un message à l'utilisateur */
      $reference="<strong>[PROJET-003]</strong>";
      $message="Vous devez être rattaché à une équipe.";
      $type="alert";
      return $response->setData(["reference" => $reference, "message"=>$message,
        "type"=>$type, Response::HTTP_OK]);
    }

    /** On recherche les projets pour les équipes rattaché à l'utilisateur */
    $in='';
    foreach ($equipes as $equipe)
    {
      if ($equipe!=='@TEST' || $equipe!=='null') {
        /** On met en minuscule */
        $minus=trim(strtolower($equipe));
        /** On construit la clause in et on remplace les espaces par des tirets  */
        $in=$in."json_each.value LIKE '".preg_replace('/\s+/', '-', $minus)."%' OR ";
      }
    }

    /** On supprime le dernier OR */
    $inTrim= rtrim($in," OR ");

    /** On construit la requête de selection des projets en fonction de(s) (l')équipes */
    $sql="SELECT DISTINCT liste_projet.maven_key as id, liste_projet.name as text
          FROM liste_projet, json_each(liste_projet.tags)
          WHERE $inTrim";
    $trim=trim(preg_replace(static::$regex, " ", $sql));
    $exec=$this->em->getConnection()->prepare($trim)->executeQuery();
    $projets=$exec->fetchAllAssociative();

    /** j'ai pas trouvé de projet pour cette équipe. */
    if (empty($projets)) {
      $reference="<strong>[PROJET-004]</strong>";
      $message="Je n'ai pas trouvé de projets pour ton équipe.";
      $type="warning";
      return $response->setData(["reference" => $reference, "message"=>$message,
        "type"=>$type, Response::HTTP_OK]);
    }

    return $response->setData(["projet" => $projets, Response::HTTP_OK]);
  }

  /**
   * [Description for projetAnalyses]
   * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version).
   * http://{url}/api/project_analyses/search?project={key}
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:29:13 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/analyses', name: 'projet_analyses', methods: ['GET'])]
  public function projetAnalyses(Request $request, Client $client): response
  {
    /** oN créé un objet réponse */
    $response = new JsonResponse();

    /** On on vérifie si on a activé le mode test */
    if (is_null($request->get('mode'))) {
      $mode="null";
    } else {
      $mode = $request->get('mode');
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

    $url = $this->getParameter(static::$sonarUrl) .
      "/api/project_analyses/search?project=" . $request->get('mavenKey');

    /** On appel le client http */
    $result = $client->http($url);
    /** On récupère le manager de BD */
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));
    $mavenKey = $request->get('mavenKey');

    /** On supprime les informations sur le projet */
    $sql = "DELETE FROM information_projet WHERE maven_key='$mavenKey'";
    if ($mode!="TEST") {
      $this->em->getConnection()->prepare($sql)->executeQuery();
    }

    /** On ajoute les informations du projets dans la table information_projet. */
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
      if ($mode!="TEST"){
        $this->em->flush();
      }
    }

    return $response->setData(["mode"=>$mode ,"nombreVersion" => $nombreVersion, Response::HTTP_OK]);
  }

  /**
   * [Description for projetMesures]
   * Récupère les indicateurs de mesures
   * http://{url}/api/components/app?component={key}
   * http://{URL}/api/measures/component?component={key}&metricKeys=ncloc
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:29:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/mesures', name: 'projet_mesures', methods: ['GET'])]
  public function projetMesures(Request $request, Client $client): response
  {
    /** oN créé un objet réponse */
    $response = new JsonResponse();

    /** On on vérifie si on a activé le mode test */
    if (is_null($request->get('mode'))) {
      $mode="null";
    } else {
      $mode = $request->get('mode');
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

    /** On bind les variables */
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $mavenKey = $request->get('mavenKey');

    /** mesures globales */
    $url1 = "$tempoUrl/api/components/app?component=$mavenKey";

    /** on appel le client http */
    $result1 = $client->http($url1);
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));

    /** On ajoute les mesures dans la table mesures. */
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

    /** Warning: Undefined array key "measures" */
    if (array_key_exists("tests", $result1["measures"])) {
      $tests = intval($result1["measures"]["tests"]);
    } else {
      $tests = 0;
    }

    /** Warning: Undefined array key "issues" */
    if (array_key_exists("issues", $result1["measures"])) {
      $issues = intval($result1["measures"]["issues"]);
    } else {
      $issues = 0;
    }

    /** On récupère le nombre de ligne de code */
    $url2 = "$tempoUrl/api/measures/component?component=$mavenKey&metricKeys=ncloc";
    $result2 = $client->http($url2);

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
    if ($mode!="TEST") {
      $this->em->flush();
    }

    if ($mode="TEST"){
        $mesures=['coverage'=>$coverage,
                  'duplicationDensity'=>$duplicationDensity,
                  'tests'=>$tests, 'issues'=>$issues, 'ncloc'=>$ncloc];
      return $response->setData(["mode"=>$mode, 'mesures'=>$mesures, Response::HTTP_OK]);
    }

    return $response->setData([Response::HTTP_OK]);
  }

  /**
   * [Description for projetAnomalie]
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
  public function projetAnomalie(Request $request, Client $client): response
  {
    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;
    $mavenKey = $request->get('mavenKey');
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

    /** On créé un objet date. */
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));

    /** 
     * On choisi le type de status des anomalies : ouvert, reouvert, fermé, corrigé,... 
     * Type : statuses, statusesMin et statusesAll
     */
    $typeStatuses=static::statuses;
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
      $dette = $this->minutesTo($detteMinute);
      $detteReliabilityMinute = $result2["effortTotal"];
      $detteReliability = $this->minutesTo($detteReliabilityMinute);
      $detteVulnerabilityMinute = $result3["effortTotal"];
      $detteVulnerability = $this->minutesTo($detteVulnerabilityMinute);
      $detteCodeSmellMinute = $result4["effortTotal"];
      $detteCodeSmell = $this->minutesTo($detteCodeSmellMinute);

      $facets = $result1["facets"];
      /** Modules. */
      $frontend=$backend=$autre=$erreur=$nombreAnomalie= 0;
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
                default:
                $this->logger->INFO("Référentiel severité !");
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
            default:
              $this->logger->INFO("Référentiel Type !");
            }
          }
        }
        /** On récupère le nombre de signalement par module. */
        if ($facet["property"] == "directories") {
          foreach ($facet["values"] as $directory) {
            $file = str_replace($mavenKey . ":", "", $directory["val"]);
            $module = explode("/", $file);
            if ($module[0]==="du-presentation" ||
                $module[0]==="rs-presentation"){
                $frontend = $frontend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-presentation" ||
                $module[0]===$app[1] . "-presentation-commun" ||
                $module[0]===$app[1] . "-presentation-ear" ||
                $module[0]===$app[1] . "-webapp"){
                  $frontend = $frontend + 1;
            }
            if ($module[0]==="rs-metier"){
              $backend = $backend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-metier" ||
                $module[0]===$app[1] . "-common" ||
                $module[0]===$app[1] . "-api" ||
                $module[0]===$app[1] . "-dao"){
                  $backend = $backend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-metier-ear" ||
                $module[0]===$app[1] . "-service" ||
                $module[0]===$app[1] . "-serviceweb" ||
                $module[0]===$app[1] . "-middleoffice"){
                  $backend = $backend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-metier-rest" ||
                $module[0]===$app[1] . "-entite" ||
                $module[0]===$app[1] . "-serviceweb-client"){
                  $backend = $backend + $directory["count"];
            }
            if ($module[0]===$app[1] . "-batch" ||
                $module[0]===$app[1] . "-batchs" ||
                $module[0]===$app[1] . "-batch-envoi-dem-aval" ||
                $module[0]===$app[1] . "-batch-import-billets"){
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

  /**
   * [Description for historiqueNoteAjout]
   * Récupère les notes pour la fiabilité, la sécurité et les mauvaises pratiques.
   * http://{url}https://{url}/api/measures/search_history?component={key}}&metrics={type}&ps=1000
   * On récupère que la première page soit 1000 résultat max.
   * Les valeurs possibles pour {type} sont : reliability_rating,security_rating,sqale_rating
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:36:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/historique/note', name: 'projet_historique_note', methods: ['GET'])]
  public function historiqueNoteAjout(Client $client, Request $request): response
  {

    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $mavenKey = $request->get('mavenKey');
    $type = $request->get('type');
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

    $url = "$tempoUrl/api/measures/search_history?component=$mavenKey
            &metrics=$type"."_rating&ps=1000";

    /** On appel le client http. */
    $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));
    $tempoDate = $date->format(static::$dateFormat);
    $nombre = $result["paging"]["total"];
    $mesures = $result["measures"][0]["history"];

    /** Enregistrement des nouvelles valeurs. */
    foreach ($mesures as $mesure) {
      $tempoMesureDate = $mesure["date"];
      $tempoMesureValue = $mesure["value"];
      $sql = "INSERT OR IGNORE INTO notes (maven_key, type, date, value, date_enregistrement)
              VALUES ('$mavenKey', '$type', '$tempoMesureDate', '$tempoMesureValue', '$tempoDate')";
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

    return $response->setData(["mode"=>$mode, "nombre" => $nombre, "type" => $type, Response::HTTP_OK]);
  }

  /**
   * [Description for issuesOwaspAjout]
   * Récupère le top 10 OWASP
   * http://{url}/api/issues/search?componentKeys={key}&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10
   * Attention une faille peut être comptée deux fois ou plus, cela dépend du tag.
   * Donc il  est possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:37:54 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/issues/owasp', name: 'projet_issues_owasp', methods: ['GET'])]
  public function issuesOwaspAjout(Request $request, Client $client): response
  {

    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $mavenKey = $request->get('mavenKey');
    $mode = $request->get('mode');
    $tempoUrlLong = $this->getParameter(static::$sonarUrl).static::$apiIssuesSearch;

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

    /** URL de l'appel. */
    $url = "$tempoUrlLong$mavenKey&facets=owaspTop10
            &owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10";

    /** On appel l'API. */
    $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));
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
          $this->logger->NOTICE("HoneyPot : Référentiel OWASP !");
      }
    }

    $a1Blocker=$a1Critical=$a1Major=$a1Info=$a1Minor=0;
    $a2Blocker=$a2Critical=$a2Major=$a2Info=$a2Minor=0;
    $a3Blocker=$a3Critical=$a3Major=$a3Info=$a3Minor=0;
    $a4Blocker=$a4Critical=$a4Major=$a4Info=$a4Minor=0;
    $a5Blocker=$a5Critical=$a5Major=$a5Info=$a5Minor=0;
    $a6Blocker=$a6Critical=$a6Major=$a6Info=$a6Minor=0;
    $a7Blocker=$a7Critical=$a7Major=$a7Info=$a7Minor=0;
    $a8Blocker=$a8Critical=$a8Major=$a8Info=$a8Minor=0;
    $a9Blocker=$a9Critical=$a9Major=$a9Info=$a9Minor=0;
    $a10Blocker=$a10Critical=$a10Major=$a10Info=$a10Minor=0;

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
                  $this->logger->NOTICE("HoneyPot : Référentiel OWASP !");
              }
            }
          }
        }
      }
    }

    /** On supprime les informations sur le projet. */
    $sql = "DELETE FROM owasp WHERE maven_key='$mavenKey'";
    if ($mode!=="TEST") {
      $this->em->getConnection()->prepare($sql)->executeQuery();
    }
    /** Enregistre en base. */
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
    if ($mode!=="TEST")
    {
      $this->em->flush();
    }

    return $response->setData(["mode"=>$mode,"owasp" => $result["total"], Response::HTTP_OK]);
  }

  /**
   * [Description for hotspotAjout]
   * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
   * http://{url}/api/hotspots/search?projectKey={key}&ps=500&p=1
   * On récupère les failles a examiner.
   * Les clés sont uniques (i.e. on ne se base pas sur les tags).
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:39:21 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/hotspot', name: 'projet_hotspot', methods: ['GET'])]
  public function hotspotAjout(Request $request, Client $client): response
  {
    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $mode = $request->get('mode');
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $mavenKey = $request->get('mavenKey');

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

    /** On construit l'URL */
    $url = "$tempoUrl/api/hotspots/search?projectKey=$mavenKey&ps=500&p=1";

    /** On appel l'Api */
    $result = $client->http($url);

    /** On créé un objet Date */
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));
    $niveau = 0;

    /** On supprime  les enregistrements correspondant à la clé */
    $sql = "DELETE FROM hotspots WHERE maven_key='$mavenKey'";
    if ($mode!="TEST") {
      $this->em->getConnection()->prepare($sql)->executeQuery();
    }

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

        if ($mode!=="TEST"){
          $this->em->persist($hotspot);
          $this->em->flush();
        }
      }
    }

    return $response->setData(
      ["mode"=>$mode,"hotspots" => $result["paging"]["total"], Response::HTTP_OK]
    );
  }

  /**
   * [Description for hotspotOwaspAjout]
   * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
   * http://{url}/api/hotspots/search?projectKey={key}{owasp}&ps=500&p=1
   * {key} = la clé du projet
   * {owasp} = le type de faille (a1, a2, etc...)
   * si le paramètre owasp est égale à a0 alors on supprime les enregistrements pour la clé
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:39:44 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/hotspot/owasp', name: 'projet_hotspot_owasp', methods: ['GET'])]
  public function hotspotOwaspAjout(Request $request, Client $client): response
  {
    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $mode = $request->get('mode');
    $mavenKey = $request->get('mavenKey');
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $owasp = $request->get('owasp');

    /** On teste si la clé est valide */
    if ($mavenKey==="null" && $mode==="TEST") {
      return $response->setData([
        "owasp"=>$owasp,
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

    if ($request->get('owasp') == 'a0') {
      /** On supprime  les enregistrements correspondant à la clé. */
      $sql = "DELETE FROM hotspot_owasp
              WHERE maven_key='$mavenKey'";
      $this->em->getConnection()->prepare($sql)->executeQuery();
      return $response->setData(["info" => "effacement", Response::HTTP_OK]);
    }

    /** On construit l'Url. */
    $url = "$tempoUrl/api/hotspots/search?projectKey=$mavenKey
            &owaspTop10=$owasp&ps=500&p=1";

    /** On appel l'URL. */
    $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));

    /** On créé un objet Date. */
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));
    $niveau = 0;

    /** On fleche la vulnérabilité */
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
        if ($mode!=="TEST"){
          $this->em->flush();
        }
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
      if ($mode!=="TEST"){
        $this->em->flush();
      }
    }

    return $response->setData(
      [ "mode"=>$mode,
        "info" => "enregistrement",
        "hotspots" => $result["paging"]["total"], Response::HTTP_OK
      ]
    );
  }

  /**
   * [Description for hotspotDetails]
   * Fonction privée qui récupère le détail d'un hotspot en fonction de sa clé.
   *
   * @param mixed $mavenKey
   * @param mixed $key
   *
   * @return array
   *
   * Created at: 15/12/2022, 21:40:47 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function hotspotDetails($mavenKey, $key, Client $client): array
  {

    /** On bind les variables. */
    $tempoUrl = $this->getParameter(static::$sonarUrl);
    $url = "$tempoUrl/api/hotspots/show?hotspot=$key";
    $hotspot = $client->http($url);
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));

    /** Si le niveau de sévérité n'est pas connu, on lui affecte la valeur MEDIUM. */
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
        $this->logger->NOTICE("HoneyPot : Liste des sévérités !");
    }
    $frontend=$backend=$autre = 0;
    /** nom du projet. */
    $app = explode(":", $mavenKey);

    $status = $hotspot["status"];
    $file = str_replace($mavenKey . ":", "", $hotspot["component"]["key"]);
    $module = explode("/", $file);

    /**
     * Cas particulier pour l'application RS et DU
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
   * [Description for hotspotDetailsAjout]
   * Récupère le détails des hotspots et les enregistre dans la table Hotspots_details
   * http://{url}/api/projet/hotspot/details{maven_key};
   * {maven_key} = la clé du projet
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:41:45 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/hotspot/details', name: 'projet_hotspot_details', methods: ['GET'])]
  public function hotspotDetailsAjout(Request $request, Client $client): response
  {
    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $mode = $request->get('mode');
    $mavenKey = $request->get('mavenKey');

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

    /** On récupère la liste des hotspots */
    $sql = "SELECT * FROM hotspots
            WHERE maven_key='$mavenKey'
            AND status='TO_REVIEW' ORDER BY niveau";

    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $liste = $r->fetchAllAssociative();

    // On supprime les données de la table hotspots_details pour le projet
    $sql = "DELETE FROM hotspot_details
            WHERE maven_key='$mavenKey'";
    if ($mode!="TEST") {
      $this->em->getConnection()->prepare($sql)->executeQuery();
    }

    /** Si la liste des vide on envoi un code 406 */
    if (empty($liste)) {
      return $response->setData(["mode"=>$mode, "code" => 406, Response::HTTP_OK]);
    }

    /**
     * On boucle sur les clés pour récupérer le détails du hotspot.
     * On envoie la clé du projet et la clé du hotspot.
     */
    $ligne = 0;
    foreach ($liste as $elt) {

      $ligne++;
      $key = $this->hotspotDetails($mavenKey, $elt["key"], $client);
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
      if ($mode!="TEST") {
        $this->em->flush();
      }
    }
    return $response->setData(["mode"=>$mode,"ligne" => $ligne, Response::HTTP_OK]);
  }

  /**
   * [Description for projetNosonarAjout]
   * On récupère la liste des fichiers ayant fait l'objet d'un
   * @@supresswarning ou d'un noSONAR
   * http://{url}api/issues/search?componentKeys={key}&rules={rules}&ps=500&p=1
   * {key} = la clé du projet
   * {rules} = java:S1309 et java:NoSonar
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:42:59 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/nosonar/details', name: 'projet_nosonar', methods: ['GET'])]
  public function projetNosonarAjout(Client $client, Request $request): response
  {
    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $mode = $request->get('mode');
    $mavenKey = $request->get('mavenKey');
    $tempoUrl = $this->getParameter(static::$sonarUrl);

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

    /** On construit l'URL et on appel le WS. */
    $url = "$tempoUrl/api/issues/search?componentKeys=$mavenKey
            &rules=java:S1309,java:NoSonar&ps=500&p=1";

    $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));

    /** On supprime les données du projet de la table NoSonar. */
    $sql = "DELETE FROM no_sonar WHERE maven_key='$mavenKey'";
    if($mode!=="TEST"){
      $this->em->getConnection()->prepare($sql)->executeQuery();
    }

    /**
     * Si on a trouvé des @notations de type nosSonar ou SuprressWarning.
     * dans le code alors on les dénombre
     */
    if ($result["paging"]["total"] !== 0) {
      foreach ($result["issues"] as $issue) {
        $nosonar = new NoSonar();
        $nosonar->setMavenKey($request->get('mavenKey'));
        $nosonar->setRule($issue["rule"]);
        $component = str_replace("$mavenKey :", "", $issue["component"]);
        $nosonar->setComponent($component);
        if (empty($issue["line"])) {
          $line = 0;
        } else {
          $line = $issue["line"];
        }
        $nosonar->setLine($line);
        $nosonar->setDateEnregistrement($date);

        $this->em->persist($nosonar);
        if ($mode!="TEST") {
          $this->em->flush();
        }
      }
    } else {
      /** Il n'y a pas de noSOnar ou de suppressWarning */
    }

    return $response->setData(["mode"=>$mode,"nosonar" => $result["paging"]["total"], Response::HTTP_OK]);
  }

  /**
   * [Description for projetNosonarAjout]
   * On récupère la liste des fichiers ayant fait l'objet d'un Todo
   * http://{url}api/issues/search?componentKeys={key}&rules={rules}&ps=500&p=1
   * {key} = la clé du projet
   * {rules} = javascript:S1135, xml:S1135, typescript:S1135, Web:S1135, java:S1135
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 10/04/2023, 15:18:45 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/todo/details', name: 'projet_todo', methods: ['GET'])]
  public function projetTodoAjout(Client $client, Request $request): response
  {
    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $mode = $request->get('mode');
    $mavenKey = $request->get('mavenKey');
    $tempoUrl = $this->getParameter(static::$sonarUrl);

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

    /** On construit l'URL et on appel le WS. */
    $url = "$tempoUrl/api/issues/search?componentKeys=$mavenKey
            &rules=javascript:S1135,xml:S1135,typescript:S1135,Web:S1135,java:S1135&ps=500&p=1";

    $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));

    /** On supprime les données du projet de la table todo. */
    $sql = "DELETE FROM todo WHERE maven_key='$mavenKey'";
    if($mode!=="TEST"){
      $this->em->getConnection()->prepare($sql)->executeQuery();
    }

    /**
     * Si on a trouvé des todo dans le code alors on les dénombre
     */
    if ($result["paging"]["total"] !== 0) {
      foreach ($result["issues"] as $issue) {
        $nosonar = new Todo();
        $nosonar->setMavenKey($request->get('mavenKey'));
        $nosonar->setRule($issue["rule"]);
        $component = str_replace("$mavenKey :", "", $issue["component"]);
        $nosonar->setComponent($component);
        if (empty($issue["line"])) {
          $line = 0;
        } else {
          $line = $issue["line"];
        }
        $nosonar->setLine($line);
        $nosonar->setDateEnregistrement($date);

        $this->em->persist($nosonar);
        if ($mode!="TEST") {
          $this->em->flush();
        }
      }
    } else {
      /** Il n'y a pas de todo */
    }

    return $response->setData(["mode"=>$mode,"todo" => $result["paging"]["total"], Response::HTTP_OK]);
  }

  /**
   * [Description for enregistrement]
   * Enregistrement des données du projet
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:44:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/enregistrement', name: 'enregistrement', methods: ['PUT'])]
  public function enregistrement(Request $request): response
  {
    /** On décode le body. */
    $data = json_decode($request->getContent());
    /** On créé un objet response pour le retour JSON. */
    $response = new JsonResponse();

    /** On créé un objet date, avec la date courante. */
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));

    /** Enregistrement */
    $save = new Historique();

    /** Informations version. */
    $save->setMavenKey($data->mavenKey);
    $save->setNomProjet($data->nomProjet);
    $save->setVersionRelease($data->versionRelease);
    $save->setVersionSnapshot($data->versionSnapshot);
    $save->setVersionAutre($data->versionAutre);
    $save->setVersion($data->version);
    $save->setDateVersion($data->dateVersion);

    /** Informations sur les exceptions. */
    $save->setSuppressWarning($data->suppressWarning);
    $save->setNoSonar($data->noSonar);

    /** Informations projet. */
    $save->setNombreLigne($data->nombreLigne);
    $save->setNombreLigneCode($data->nombreLigneDeCode);
    $save->setCouverture($data->couverture);
    $save->setDuplication($data->duplication);
    $save->setTestsUnitaires($data->testsUnitaires);
    $save->setNombreDefaut($data->nombreDefaut);

    /** Dette technique. */
    $save->setDette($data->dette);

    /** Nombre de défaut. */
    $save->setNombreBug($data->nombreBug);
    $save->setNombreVulnerability($data->nombreVulnerability);
    $save->setNombreCodeSmell($data->nombreCodeSmell);

    /** répartition par module (Java). */
    $save->setFrontend($data->frontend);
    $save->setBackend($data->backend);
    $save->setAutre($data->autre);

    /** Répartition par type. */
    $save->setNombreAnomalieBloquant($data->nombreAnomalieBloquant);
    $save->setNombreAnomalieCritique($data->nombreAnomalieCritique);
    $save->setNombreAnomalieInfo($data->nombreAnomalieInfo);
    $save->setNombreAnomalieMajeur($data->nombreAnomalieMajeur);
    $save->setNombreAnomalieMineur($data->nombreAnomalieMineur);

    /** Notes Fiabilité, sécurité, hotspots et mauvaises pratique. */
    $save->setNoteReliability($data->noteReliability);
    $save->setNoteSecurity($data->noteSecurity);
    $save->setNoteSqale($data->noteSqale);
    $save->setNoteHotspot($data->noteHotspot);

    /** Répartition des hotspots. */
    $save->setHotspotHigh($data->hotspotHigh);
    $save->setHotspotMedium($data->hotspotMedium);
    $save->setHotspotLow($data->hotspotLow);
    $save->setHotspotTotal($data->hotspotTotal);

    /** Je suis une verion initiale ?  0 (false) and 1 (true). */
    /** On récupère 0 ou 1 et non FALSE et TRUE */
    $save->setInitial($data->initial);

    /** Nombre de défaut par sévérité. */
    /** Les BUG. */
    $save->setBugBlocker($data->bugBlocker);
    $save->setBugCritical($data->bugCritical);
    $save->setBugMajor($data->bugMajor);
    $save->setBugMinor($data->bugMinor);
    $save->setBugInfo($data->bugInfo);

    /** Les VULNERABILITY. */
    $save->setVulnerabilityBlocker($data->vulnerabilityBlocker);
    $save->setVulnerabilityCritical($data->vulnerabilityCritical);
    $save->setVulnerabilityMajor($data->vulnerabilityMajor);
    $save->setVulnerabilityMinor($data->vulnerabilityMinor);
    $save->setVulnerabilityInfo($data->vulnerabilityInfo);

    /** Les CODE SMELL. */
    $save->setCodeSmellBlocker($data->codeSmellBlocker);
    $save->setCodeSmellCritical($data->codeSmellCritical);
    $save->setCodeSmellMajor($data->codeSmellMajor);
    $save->setCodeSmellMinor($data->codeSmellMinor);
    $save->setCodeSmellInfo($data->codeSmellInfo);

    /** On ajoute la date et on enregistre. */
    $save->setDateEnregistrement($date);
    $this->em->persist($save);

    // On catch l'erreur sur la clé composite : maven_key, version, date_version
      try {
          $this->em->flush();
      } catch (\Doctrine\DBAL\Exception\UniqueConstraintViolationException $e) {
        /** General error: 5 database is locked" */
        /** General error: 19 violation de clé */
        if ($e->getCode() === 19) {
          $code = 19;
        } else {
          $code = $e;
        }
        return $response->setData(["code" => $code, Response::HTTP_OK]);
      }
    /** Tout va bien ! */
    return $response->setData(["code" => "OK", Response::HTTP_OK]);
  }
}
