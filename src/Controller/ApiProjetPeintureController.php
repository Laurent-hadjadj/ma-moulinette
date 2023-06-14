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
use Symfony\Component\HttpFoundation\JsonResponse;

/** Securité */
use Symfony\Bundle\SecurityBundle\Security;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;

class ApiProjetPeintureController extends AbstractController
{
  public function __construct(
    private EntityManagerInterface $em,
    )
  {
    $this->em = $em;
  }

  public static $erreurMavenKey="La clé maven est vide!";
  const HTTP_ERROR_406 = "                   Vous devez lancer une analyse pour ce projet !!!";
  public static $regex = "/\s+/u";

  /**
   * [Description for isValide]
   *
   * @param mixed $mavenKey
   * Vérification de l'existence du projet dans la table information_projet
   *
   * @return array
   *
   * Created at: 15/12/2022, 21:51:16 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function isValide($mavenKey): array
  {
    /** On regarde si une analyse a été réalisée. */
    $sql = "SELECT * FROM information_projet WHERE maven_key='${mavenKey}' LIMIT 1";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $response = $r->fetchAllAssociative();
    if (empty($response)) {
      return ["code" => 406];
    }
    return ["code" => 200];
  }

  /**
   * [Description for projetMesApplicationsListe]
   * Récupupère la liste des projets que j'ai visité et ceux en favori
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:51:40 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/mes-applications/liste', name: 'projet_mes_applications_liste', methods: ['GET'])]
  public function projetMesApplicationsListe(Security $security): response
  {
   /** On récupère l'objet User du contexte de sécurité */
    $userSecurity=$security->getUser();
    $preference=$security->getUser()->getPreference();

    /** On crée un objet response */
    $response = new JsonResponse();

    /**
     * On récupère la liste des projets ayant déjà fait l'objet d'une analyse.
     * On n'utilise plus le critère liste=TRUE/FALSE car on utilise les préferences
     * de l'utilisateur
     * */
    $sql = "SELECT maven_key as key
            FROM anomalie
            GROUP BY maven_key
            ORDER BY project_name ASC";

    $select = $this->em->getConnection()->prepare($sql)->executeQuery();
    $analyses = $select->fetchAllAssociative();

    /** Si on a pas trouvé d'application. */
    if (empty($analyses)) {
      $type="primary";
      $reference="<strong>[Mes projets]</strong>";
      $message="je n'ai pas trouvé d'analyse. Vous devez lancer une collecte.";
      return $response->setData(["type"=>$type, "reference"=>$reference, "message"=>$message, Response::HTTP_OK]);
    }

    /**
     * Pour chaque projet de la liste de préference,
     * on regarde si le projet a déjà fait l'objet d'une analyse
     * et si le projet est en favori.
     */
    $mesProjets=$preference['projet'];
    $mesFavoris=$preference['favori'];
    $projets=[];
    foreach ($mesProjets as $projet) {
      if (in_array(["key"=>$projet],$analyses)){
        $t=explode(":", $projet);
        array_push($projets,["key"=>$projet, "name"=>$t[1], "favori"=>in_array($projet,$mesFavoris)]);
      }
    }

    return $response->setData([
      "code" => 200,
      "projets" => $projets,
      Response::HTTP_OK]);
  }


  /**
   * [Description for peintureProjetVersion]
   * Récupère les informations sur le projet : type de version, dernière version,
   * date de l'audit
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:53:31 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/peinture/projet/version', name: 'peinture_projet_version', methods: ['GET'])]
  public function peintureProjetVersion(Request $request): response
  {
    $mavenKey = $request->get('mavenKey');
    $response = new JsonResponse();

    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]);
    }

    /** Toutes les versions */
    $sql = "SELECT COUNT(type) AS 'total'
    FROM information_projet
    WHERE maven_key='${mavenKey}'";
    $e=$this->em->getConnection()->prepare($sql)->executeQuery();
    $toutesLesVersions=$e->fetchAllAssociative();

    /** Les releases */
    $sql = "SELECT type, COUNT(type) AS 'total'
    FROM information_projet
    WHERE maven_key='${mavenKey}' AND type='RELEASE'";
    $e=$this->em->getConnection()->prepare($sql)->executeQuery();
    $release=$e->fetchAllAssociative();

    /** Les snapshots */
    $sql = "SELECT type, COUNT(type) AS 'total'
    FROM information_projet
    WHERE maven_key='${mavenKey}' AND type='SNAPSHOT'";
    $e=$this->em->getConnection()->prepare($sql)->executeQuery();
    $snapshot=$e->fetchAllAssociative();

    /** On calcul la valeur pour les autres types de version */
    $lesAutres=$toutesLesVersions[0]['total']-$release[0]['total']-$snapshot[0]['total'];

    /** On récupére le nombre de version par type pour le graphique */
    $sql = "SELECT type, COUNT(type) AS 'total'
            FROM information_projet
            WHERE maven_key='${mavenKey}'
            GROUP BY type";
    $e = $this->em->getConnection()->prepare($sql)->executeQuery();
    $infoVersion = $e->fetchAllAssociativeIndexed();

    $label = [];
    $dataset = [];
    foreach ($infoVersion as $key => $value) {
      array_push($label, $key);
      array_push($dataset, $value["total"]);
    }

    /** On récupère la dernière version et sa date de publication */
    $sql = "SELECT project_version as projet, date
            FROM information_projet
            WHERE maven_key='${mavenKey}'
            ORDER BY date DESC LIMIT 1 ";

    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $infoRelease = $r->fetchAllAssociative();

    /** Contrôle de la valeur des versions release, snapshot et release */
    if (empty($release[0]['total']))
      {
        $release=0;
      } else {
        $release=$release[0]['total'];
      }

    if (empty($snapshot[0]['total']))
    {
      $snapshot=0;
    } else {
      $snapshot=$snapshot[0]['total'];
    }

    return $response->setData(
      [
      "release"=>$release, "snapshot"=>$snapshot,  "autre"=>$lesAutres,
      "label" => $label,
      "dataset" => $dataset, "projet" => $infoRelease[0]["projet"],
      "date" => $infoRelease[0]["date"], Response::HTTP_OK]
    );
  }

  /**
   * [Description for peintureProjetInformation]
   * Récupère les informations sur le projet : type de version, dernière version,
   * date de l'audit
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:53:58 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/peinture/projet/information', name: 'peinture_projet_information', methods: ['GET'])]
  public function peintureProjetInformation(Request $request): response
  {
    $mavenKey = $request->get('mavenKey');
    $response = new JsonResponse();

    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]);
    }

    /** On récupère la dernière version et sa date de publication */
    $sql = "SELECT project_name as name, ncloc, lines, coverage,
            duplication_density as duplication, tests, issues
            FROM mesures
            WHERE maven_key='${mavenKey}'
            ORDER BY date_enregistrement DESC LIMIT 1";

    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $infoProjet = $r->fetchAllAssociative();

    return $response->setData([
      "name" => $infoProjet[0]["name"], "ncloc" => $infoProjet[0]["ncloc"],
      "lines" => $infoProjet[0]["lines"],
      "coverage" => $infoProjet[0]["coverage"],
      "duplication" => $infoProjet[0]["duplication"],
      "tests" => $infoProjet[0]["tests"], "issues" => $infoProjet[0]["issues"],
      Response::HTTP_OK
    ]);
  }

  /**
   * [Description for peintureProjetAnomalie]
   * Récupère les informations sur la dette technique et les anamalies
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:54:30 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/peinture/projet/anomalie', name: 'peinture_projet_anomalie', methods: ['GET'])]
  public function peintureProjetAnomalie(Request $request): response
  {
    /** On bind les variables. */
    $mavenKey = $request->get('mavenKey');

    /** On créé un objet json. */
    $response = new JsonResponse();

    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(
        ["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]
      );
    }

    /** On récupère la dernière version et sa date de publication. */
    $sql = "SELECT * FROM anomalie WHERE maven_key='${mavenKey}'";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $anomalies = $r->fetchAllAssociative();

    /**
     * Dette : Dette total, répartition de la dette en fonction du type.
     * On récupère la valeur en jour, heure, minute pour l'affichage et la valeur en minutes
     * pour la historique (i.e on fera les comparaison sur cette valeur)
    */
    $dette = $anomalies[0]["dette"];
    $detteMinute = $anomalies[0]["dette_minute"];
    $detteReliability = $anomalies[0]["dette_reliability"];
    $detteReliabilityMinute = $anomalies[0]["dette_reliability_minute"];
    $detteVulnerability = $anomalies[0]["dette_vulnerability"];
    $detteVulnerabilityMinute = $anomalies[0]["dette_vulnerability_minute"];
    $detteCodeSmell = $anomalies[0]["dette_code_smell"];
    $detteCodeSmellMinute = $anomalies[0]["dette_code_smell_minute"];

    /** Types */
    $typeBug = $anomalies[0]["bug"];
    $typeVulnerability = $anomalies[0]["vulnerability"];
    $typeCodeSmell = $anomalies[0]["code_smell"];

    /** Severity */
    $severityBlocker = $anomalies[0]["blocker"];
    $severityCritical = $anomalies[0]["critical"];
    $severityMajor = $anomalies[0]["major"];
    $severityInfo = $anomalies[0]["info"];
    $severityMinor = $anomalies[0]["minor"];

    /** Module */
    $frontend = $anomalies[0]["frontend"];
    $backend = $anomalies[0]["backend"];
    $autre = $anomalies[0]["autre"];

    /* On récupère les notes (A-F) */
    $types = ["reliability", "security", "sqale"];
    foreach ($types as $type) {
      $sql = "SELECT type, value FROM notes
              WHERE maven_key='${mavenKey}' AND type='${type}'
              ORDER BY date DESC LIMIT 1";

      $r = $this->em->getConnection()->prepare($sql)->executeQuery();
      $note = $r->fetchAllAssociative();
      if ($type == "reliability") {
        $noteReliability = $note[0]["value"];
      }
      if ($type == "security") {
        $noteSecurity = $note[0]["value"];
      }
      if ($type == "sqale") {
        $noteSqale = $note[0]["value"];
      }
    }

    return $response->setData([
      "dette" => $dette,
      "detteReliability" => $detteReliability,
      "detteVulnerability" => $detteVulnerability,
      "detteCodeSmell" => $detteCodeSmell,
      "detteMinute" => $detteMinute,
      "detteReliabilityMinute" => $detteReliabilityMinute,
      "detteVulnerabilityMinute" => $detteVulnerabilityMinute,
      "detteCodeSmellMinute" => $detteCodeSmellMinute,
      "bug" => $typeBug,
      "vulnerability" => $typeVulnerability,
      "codeSmell" => $typeCodeSmell,
      "blocker" => $severityBlocker,
      "critical" => $severityCritical,
      "info" => $severityInfo,
      "major" => $severityMajor,
      "minor" => $severityMinor,
      "frontend" => $frontend, "backend" => $backend, "autre" => $autre,
      "noteReliability" => $noteReliability,
      "noteSecurity" => $noteSecurity,
      "noteSqale" => $noteSqale, Response::HTTP_OK
    ]);
  }

  /**
   * [Description for peintureProjetAnomalieDetails]
   * Récupère le détails des anomalies pour chaque type
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:56:17 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/peinture/projet/anomalie/details', name: 'peinture_projet_anomalie_details', methods: ['GET'])]
  public function peintureProjetAnomalieDetails(Request $request): response
  {
    $mavenKey = $request->get('mavenKey');
    $response = new JsonResponse();

    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(
        ["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]
      );
    }

    /** On récupère les données pour le projet */
    $sql = "SELECT * FROM anomalie_details WHERE maven_key='$mavenKey'";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $details = $r->fetchAllAssociative();

    $bugBlocker = $details[0]["bug_blocker"];
    $bugCritical = $details[0]["bug_critical"];
    $bugMajor = $details[0]["bug_major"];
    $bugMinor = $details[0]["bug_minor"];
    $bugInfo = $details[0]["bug_info"];

    $vulnerabilityBlocker = $details[0]["vulnerability_blocker"];
    $vulnerabilityCritical = $details[0]["vulnerability_critical"];
    $vulnerabilityMajor = $details[0]["vulnerability_major"];
    $vulnerabilityMinor = $details[0]["vulnerability_minor"];
    $vulnerabilityInfo = $details[0]["vulnerability_info"];

    $codeSmellBlocker = $details[0]["code_smell_blocker"];
    $codeSmellCritical = $details[0]["code_smell_critical"];
    $codeSmellMajor = $details[0]["code_smell_major"];
    $codeSmellMinor = $details[0]["code_smell_minor"];
    $codeSmellInfo = $details[0]["code_smell_info"];

    return $response->setData([
      "message" => 200,
      "bugBlocker" => $bugBlocker,
      "bugCritical" => $bugCritical,
      "bugMajor" => $bugMajor,
      "bugMinor" => $bugMinor,
      "bugInfo" => $bugInfo,
      "vulnerabilityBlocker" => $vulnerabilityBlocker,
      "vulnerabilityCritical" => $vulnerabilityCritical,
      "vulnerabilityMajor" => $vulnerabilityMajor,
      "vulnerabilityMinor" => $vulnerabilityMinor,
      "vulnerabilityInfo" => $vulnerabilityInfo,
      "codeSmellBlocker" => $codeSmellBlocker,
      "codeSmellCritical" => $codeSmellCritical,
      "codeSmellMajor" => $codeSmellMajor,
      "codeSmellMinor" => $codeSmellMinor,
      "codeSmellInfo" => $codeSmellInfo,
      Response::HTTP_OK
    ]);
  }

  /**
   * [Description for peintureProjetHotspots]
   * Récupère les hotspots du projet
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:56:43 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/peinture/projet/hotspots', name: 'peinture_projet_hotspots', methods: ['GET'])]
  public function peintureProjetHotspots(Request $request): response
  {
   /** On Bind les variables. */
    $mavenKey = $request->get('mavenKey');

    /** On créé un objet JSON. */
    $response = new JsonResponse();

    /** On envoi un code 406 si aucun éléments n'est trouvé. */
    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(
        ["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]
      );
    }

    /** On le nombre de hotspot au statut TO_REVIEW */
    $sql="SELECT COUNT(*) as to_review FROM hotspots WHERE maven_key='${mavenKey}' AND status='TO_REVIEW'";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $toReview = $r->fetchAllAssociative();

    /** On le nombre de hotspot au statut REVIEWED */
    $sql = "SELECT COUNT(*) as reviewed FROM hotspots WHERE maven_key='${mavenKey}' AND status='REVIEWED'";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $reviewed = $r->fetchAllAssociative();

    /** On calul la note sonar */
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

    return $response->setData(["note" => $note, Response::HTTP_OK]);
  }

  /**
   * [Description for peintureProjetHotspotDetails]
   * Récupère le détails des hotspots du projet
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:57:40 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/peinture/projet/hotspot/details', name: 'peinture_projet_hotspot_details', methods: ['GET'])]
  public function peintureProjetHotspotDetails(Request $request): response
  {
    /** On bind  les variables. */
    $mavenKey = $request->get('mavenKey');
    $response = new JsonResponse();

    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(
        ["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]
      );
    }

    $high=$medium=$low=0;
    /** On récupère la dernière version et sa date de publication. */
    $sql="SELECT niveau, count(*) as hotspot
          FROM hotspots
          WHERE maven_key='${mavenKey}'
          AND status='TO_REVIEW'
          GROUP BY niveau";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $niveaux = $r->fetchAllAssociative();

    foreach ($niveaux as $niveau) {
      if ($niveau["niveau"] == "1") {
        $high = $niveau["hotspot"];
      }
      if ($niveau["niveau"] == "2") {
        $medium = $niveau["hotspot"];
      }
      if ($niveau["niveau"] == "3") {
        $low = $niveau["hotspot"];
      }
    }
    $total = intval($high) + intval($medium) + intval($low);
    return $response->setData(
      ["code" => 200, "total" => $total, "high" => $high,
        "medium" => $medium, "low" => $low, Response::HTTP_OK]);
  }

  /**
   * [Description for peintureProjetNosonarDetails]
   * Récupère les exclusions nosonar et suppressWarning pour Java
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:58:42 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/peinture/projet/nosonar/details', name: 'peinture_projet_nosonar_details', methods: ['GET'])]
  public function peintureProjetNosonarDetails(Request $request): response
  {

    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $mavenKey = $request->get('mavenKey');
    $mode = $request->get('mode');

    /** On teste si la clé est valide */
    if ($mavenKey==="null" && $mode==="TEST") {
      return $response->setData([
        "mode"=>$mode, "mavenKey"=>$mavenKey,
        "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    $sql = "SELECT rule, count(*) as total FROM no_sonar WHERE maven_key='"
      . $mavenKey . "' GROUP BY rule";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $rules = $r->fetchAllAssociative();

    $S1309=$nosonar=$total=0;

    if (empty($rules) === false) {
      foreach ($rules as $rule) {
        if ($rule["rule"] == "java:S1309") {
          $S1309 = $rule["total"];
        }
        if ($rule["rule"] == "java:NoSonar") {
          $nosonar = $rule["total"];
        }
      }
      $total = intval($S1309,10) + intval($nosonar,10);
    }

    return $response->setData(
      ["total" => $total, "s1309" => $S1309, "nosonar" => $nosonar, Response::HTTP_OK]);
  }

/**
   * [Description for peintureProjetTodoDetails]
   * Récupère les tags todo pour : java, js, ts, html et xml
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 10/04/2023, 17:54:08 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/peinture/projet/todo/details', name: 'peinture_projet_todo_details', methods: ['GET'])]
  public function peintureProjetTodoDetails(Request $request): response
  {
    /** On créé un objet response */
    $response = new JsonResponse();

    /** On bind les variables. */
    $mavenKey = $request->get('mavenKey');
    $mode = $request->get('mode');

    /** On teste si la clé est valide */
    if ($mavenKey==="null" && $mode==="TEST") {
      return $response->setData([
        "mode"=>$mode, "mavenKey"=>$mavenKey,
        "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
    }

    $sql = "SELECT rule, count(*) as total FROM todo WHERE maven_key='$mavenKey' GROUP BY rule";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $rules = $r->fetchAllAssociative();
    $todo=$java=$javascript=$typescript=$html=$xml=0;

    if (empty($rules) === false) {
      /** On récupère le niombre total de Todo par langage */
      foreach ($rules as $rule) {
        if ($rule["rule"] == "java:S1135") {
          $java = $rule["total"];
        }
        if ($rule["rule"] == "javascript:S1135") {
          $javascript = $rule["total"];
        }
        if ($rule["rule"] == "typescript:S1135") {
          $typescript = $rule["total"];
        }
        if ($rule["rule"] == "Web:S1135") {
          $html = $rule["total"];
        }
        if ($rule["rule"] == "xml:S1135") {
          $xml = $rule["total"];
        }
      }
      $todo = intval($java,10) + intval($javascript,10) + intval($typescript,10)+ intval($html,10) + intval($xml,10);
    }

    /** On récupère la liste détaillée. */
    $sql = "SELECT rule, component, line FROM todo WHERE maven_key='$mavenKey' ORDER BY rule";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $details = $r->fetchAllAssociative();

    return $response->setData(
      ["todo" => $todo, "java" => $java, "javascript" => $javascript, "typescript" => $typescript, "html" => $html, "xml" => $xml, "details"=>$details, Response::HTTP_OK]);
  }

}
