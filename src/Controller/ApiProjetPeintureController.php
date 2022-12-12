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

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;

class ApiProjetPeintureController extends AbstractController
{

  private $em;

  public function __construct(
    EntityManagerInterface $em,
    )
  {
    $this->em = $em;
  }

  const HTTP_ERROR_406 = "                   Vous devez lancer une analyse pour ce projet !!!";

  /**
   * isValide
   * Vérification de l'existence du projet dans la table information_projet
   *
   * @param  mixed $mavenKey
   * @return array
   */
  protected function isValide($mavenKey): array
  {
    // On regarde si une analyse a été réalisée.
    $sql = "SELECT * FROM information_projet WHERE maven_key='${mavenKey}' LIMIT 1";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $response = $r->fetchAllAssociative();
    if (empty($response)) {
      return ["code" => 406];
    }
    return ["code" => 200];
  }

  /**
   * projet_mes_applictaions_liste
   * Récupupère la liste de mes projets et ceux en favoris
   *
   * @return response
   */
  #[Route('/api/projet/mes-applications/liste', name: 'projet_mesapplications_liste', methods: ['GET'])]
  public function projetMesApplicationsListe(): response
  {
    $response = new JsonResponse();
    //On récupère la liste des projets ayant déjà fait l'objet d'une analyse.
    $sql = "SELECT project_name as name, maven_key AS key
            FROM anomalie
            WHERE liste = TRUE
            GROUP BY maven_key
            ORDER BY project_name ASC";

    $select = $this->em->getConnection()->prepare($sql)->executeQuery();
    $listeProjet = $select->fetchAllAssociative();

    // Si on a pas trouvé d'application.
    if (empty($listeProjet)) {
      return $response->setData(["code" => 406, Response::HTTP_OK]);
    }

    //On récupère la liste des projets favori.
    //SQLite : 0 (false) and 1 (true).
    $sql = "SELECT maven_key AS key FROM favori WHERE favori=1";
    $select = $this->em->getConnection()->prepare($sql)->executeQuery();
    $favori = $select->fetchAllAssociative();

    // Si on a pas trouvé de favori.
    if (empty($favori)) {
      $listeFavori = ["vide"];
    } else {
      $listeFavori = $favori;
    }

    return $response->setData(["code" => 200, "liste" => $listeProjet,
    "favori" => $listeFavori, Response::HTTP_OK]);
  }

    /**
   * projet_mes_applictaions_liste
   * Désactive l'affichage du projet dans la liste des projets déjà analysés.
   *
   * @return response
   */
  #[Route('/api/projet/mes-applications/delete', name: 'projet_mesapplications_delete', methods: ['GET'])]
  public function projetMesApplicationsDelete(Request $request): response
  {
    // On bind la variables
    $mavenKey = $request->get('mavenKey');

    $response = new JsonResponse();
    //On récupère la liste des projets ayant déjà fait l'objet d'une analyse.
    $sql = "UPDATE anomalie
            SET liste = FALSE
            WHERE maven_key='${mavenKey}';";

    $this->em->getConnection()->prepare($sql)->executeQuery();

    return $response->setData(["code" => 200, Response::HTTP_OK]);
  }

  /**
   * peinture_projet_version
   * Récupère les informations sur le projet : type de version, dernière version,
   * date de l'audit
   *
   * @param  mixed $request
   * @return response
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

    // On récupere le nombre de version par type
    $sql = "SELECT type, COUNT(type) AS 'total'
            FROM information_projet
            WHERE maven_key='${mavenKey}'
            GROUP BY type";

    $list = $this->em->getConnection()->prepare($sql)->executeQuery();
    $infoVersion = $list->fetchAllAssociativeIndexed();
    $label = [];
    $dataset = [];
    foreach ($infoVersion as $key => $value) {
      array_push($label, $key);
      array_push($dataset, $value["total"]);
    }

    // On récupère la dernière version et sa date de publication
    $sql = "SELECT project_version as projet, date
            FROM information_projet
            WHERE maven_key='${mavenKey}'
            ORDER BY date DESC LIMIT 1 ";

    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $infoRelease = $r->fetchAllAssociative();

    return $response->setData(
      ["version" => $infoVersion, "label" => $label,
      "dataset" => $dataset, "projet" => $infoRelease[0]["projet"],
      "date" => $infoRelease[0]["date"], Response::HTTP_OK]
    );
  }

  /**
   * peinture_projet_information
   * Récupère les informations sur le projet : type de version, dernière version,
   * date de l'audit

   *
   * @param  mixed $request
   * @return response
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

    // On récupère la dernière version et sa date de publication
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
   * peinture_projet_anomalie
   * Récupère les informations sur la dette technique et les anamalies
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/peinture/projet/anomalie', name: 'peinture_projet_anomalie', methods: ['GET'])]
  public function peintureProjetAnomalie(Request $request): response
  {
    // On bind les variables
    $mavenKey = $request->get('mavenKey');

    // On créé un objet json
    $response = new JsonResponse();

    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(
        ["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]
      );
    }

    // On récupère la dernière version et sa date de publication
    $sql = "SELECT * FROM anomalie WHERE maven_key='${mavenKey}'";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $anomalies = $r->fetchAllAssociative();

    /* Dette : Dette total, répartition de la dette en fonction du type.
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

    // Types
    //$anomalie_total=$anomalies[0]["anomalie_total"];
    $typeBug = $anomalies[0]["bug"];
    $typeVulnerability = $anomalies[0]["vulnerability"];
    $typeCodeSmell = $anomalies[0]["code_smell"];

    // Severity
    $severityBlocker = $anomalies[0]["blocker"];
    $severityCritical = $anomalies[0]["critical"];
    $severityMajor = $anomalies[0]["major"];
    $severityInfo = $anomalies[0]["info"];
    $severityMinor = $anomalies[0]["minor"];

    // Module
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
   * peinture_projet_anomalie_details
   * Récupère le détails des anomalies pour chaque type
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/peinture/projet/anomalie/details', name: 'peinture_projet_anomalie_details', methods: ['GET'])]
  public function peintureProjetAnomalie_details(Request $request): response
  {
    $mavenKey = $request->get('mavenKey');
    $response = new JsonResponse();

    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(
        ["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]
      );
    }

    // On récupère les données pour le projet
    $sql = "SELECT * FROM anomalie_details WHERE maven_key='${mavenKey}'";
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
   * peinture_projet_hotspots
   * Récupère les hotspots du projet
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/peinture/projet/hotspots', name: 'peinture_projet_hotspots', methods: ['GET'])]
  public function peintureProjetHotspots(Request $request): response
  {
   // On Bind les variables
    $mavenKey = $request->get('mavenKey');

    // On créé un objet JSON
    $response = new JsonResponse();

    // On envoi un code 406 si aucun éléments n'est trouvé
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

    /** On calul la note sonar  */
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
   * peinture_projet_hotspot_details
   * Récupère le détails des hotspots du projet
   *
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/peinture/projet/hotspot/details', name: 'peinture_projet_hotspot_details', methods: ['GET'])]
  public function peintureProjetHotspotDetails(Request $request): response
  {
    // On bind  les variables
    $mavenKey = $request->get('mavenKey');
    $response = new JsonResponse();

    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(
        ["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]
      );
    }

    $high = 0;
    $medium = 0;
    $low = 0;
    // On récupère la dernière version et sa date de publication
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
   * peinture_projet_nosonar_details
   * Récupère les exclusions nosonar et suppressWarning pour Java
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/peinture/projet/nosonar/details', name: 'peinture_projet_nosonar_details', methods: ['GET'])]
  public function peintureProjetNosonarDetails(Request $request): response
  {
    $mavenKey = $request->get('mavenKey');
    $response = new JsonResponse();

    $sql = "select rule, count(*) as total FROM no_sonar WHERE maven_key='"
      . $mavenKey . "' GROUP BY rule";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $rules = $r->fetchAllAssociative();
    $S1309 = 0;
    $nosonar = 0;
    $total = 0;

    if (empty($rules) === false) {
      foreach ($rules as $rule) {
        if ($rule["rule"] == "java:S1309") {
          $S1309 = $rule["total"];
        }
        if ($rule["rule"] == "java:NoSonar") {
          $nosonar = $rule["total"];
        }
      }
      $total = intval($S1309) + intval($nosonar);
    }

    return $response->setData(
      ["total" => $total, "s1309" => $S1309,
        "nosonar" => $nosonar, Response::HTTP_OK]);
  }
}
