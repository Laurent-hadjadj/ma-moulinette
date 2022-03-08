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
use Symfony\Component\HttpFoundation\JsonResponse;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;

class ApiProjetPeintureController extends AbstractController
{

  const HTTP_ERROR_406 = "                   Vous devez lancer une analyse pour ce projet !!!";

/**
 * description
 * Vérification de l'existence du projet dans la table information_projet
 */
   function is_valide(EntityManagerInterface $em, $maven_key): array
   {
     // On regarde si une analyse a été réalisée.
    $sql="SELECT * FROM information_projet WHERE maven_key='". $maven_key."' LIMIT 1";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $response=$r->fetchAllAssociative();
    if (empty($response)){ return ["code"=>406]; }
    return ["code"=>200];
  }


  /**
   * description
   * Récupupère la liste de mes projets et ceux en favoris
  * http://{url}/api/project_analyses/search?project={key}
  */
  #[Route('/api/liste/projet', name: 'liste_projet', methods: ['GET'])]
  public function liste_projet(EntityManagerInterface $em): response
  {
    $response = new JsonResponse();
    //On récupère la liste des projets ayant déjà fait l'objet d'une analyse.
    $sql="SELECT project_name as name, maven_key as key FROM anomalie GROUP BY maven_key ORDER BY project_name ASC";
    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $liste_projet=$select->fetchAllAssociative();

    // Si on a pas trouvé d'application.
    if ( empty($liste_projet) ){ return $response->setData(["code"=>"406",Response::HTTP_OK]); }

    //On récupère la liste des projets favori.
    $sql="SELECT maven_key as key FROM favori WHERE favori='TRUE'";
    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $favori=$select->fetchAllAssociative();

    // Si on a pas trouvé de favori.
    if ( empty($favori) ){ $liste_favori=["vide"]; } else { $liste_favori=$favori;}

    return $response->setData(["code"=>"200", "liste"=>$liste_projet, "favori"=>$liste_favori, Response::HTTP_OK]);
  }

  /**
   * description
   * Récupère les informations sur le projet : type de version, dernière version, date de l'audit
  */
  #[Route('/api/peinture/projet/version', name: 'peinture_projet_version', methods: ['GET'])]
  public function peinture_projet_version(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    $is_valide=$this->is_valide($em, $maven_key);
    if ($is_valide["code"]==406) {

      return $response->setData(["message"=> self::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]);
    }

    // On récupere le nombre de version par type
    $sql="SELECT type, COUNT(type) as 'total' FROM information_projet WHERE maven_key='". $maven_key."' GROUP BY type";
    $list=$em->getConnection()->prepare($sql)->executeQuery();
    $infoVersion=$list->fetchAllAssociativeIndexed();
    $label=[];
    $dataset=[];
    foreach ($infoVersion as $key => $value) { array_push($label, $key); array_push($dataset, $value["total"]); }

    // On récupère la dernière version et sa date de publication
    $sql="SELECT project_version as projet, date FROM information_projet WHERE maven_key='".$maven_key."' ORDER BY date DESC LIMIT 1";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $infoRelease=$r->fetchAllAssociative();

    return $response->setData(
      ["version"=>$infoVersion, "label"=>$label, "dataset"=>$dataset, "projet"=>$infoRelease[0]["projet"], "date"=>$infoRelease[0]["date"], Response::HTTP_OK]);
  }

  /**
   * description
   * Récupère les informations sur le projet : type de version, dernière version, date de l'audit
  */
  #[Route('/api/peinture/projet/information', name: 'peinture_projet_information', methods: ['GET'])]
  public function peinture_projet_information(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    $is_valide=$this->is_valide($em, $maven_key);
    if ($is_valide["code"]==406) {
      return $response->setData(["message"=>self::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]);
    }

    // On récupère la dernière version et sa date de publication
    $sql="SELECT project_name as name, lines, coverage, duplication_density as duplication, tests, issues FROM mesures WHERE maven_key='". $maven_key."' ORDER BY date_enregistrement DESC LIMIT 1";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $infoProjet=$r->fetchAllAssociative();

    return $response->setData([
      "name"=>$infoProjet[0]["name"], "lines"=>$infoProjet[0]["lines"],
      "coverage"=>$infoProjet[0]["coverage"], "duplication"=>$infoProjet[0]["duplication"],
      "tests"=>$infoProjet[0]["tests"], "issues"=>$infoProjet[0]["issues"],
       Response::HTTP_OK]);
  }

  /**
   * description
   * Récupère les informations sur la dette technique et les anamalies
  */
  #[Route('/api/peinture/projet/anomalie', name: 'peinture_projet_anomalie', methods: ['GET'])]
  public function peinture_projet_anomalie(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    $is_valide=$this->is_valide($em, $maven_key);
    if ($is_valide["code"]==406) {
      return $response->setData(["message"=>self::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]);
    }

    // On récupère la dernière version et sa date de publication
    $sql="SELECT * FROM anomalie WHERE maven_key='". $maven_key."'";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $anomalies=$r->fetchAllAssociative();

    /* Dette : Dette total, répartition de la dette en fonction du type.
     * On récupère la valeur en jour, heure, minute pour l'affichage et la valeur en minutes
     * pour la historique (i.e on fera les comparaison sur cette valeur)
    */
    $dette=$anomalies[0]["dette"];
    $dette_minute=$anomalies[0]["dette_minute"];
    $dette_reliability=$anomalies[0]["dette_reliability"];
    $dette_reliability_minute=$anomalies[0]["dette_reliability_minute"];
    $dette_vulnerability=$anomalies[0]["dette_vulnerability"];
    $dette_vulnerability_minute=$anomalies[0]["dette_vulnerability_minute"];
    $dette_code_smell=$anomalies[0]["dette_code_smell"];
    $dette_code_smell_minute=$anomalies[0]["dette_code_smell_minute"];

    // Types
    //$anomalie_total=$anomalies[0]["anomalie_total"];
    $type_bug=$anomalies[0]["bug"];
    $type_vulnerability=$anomalies[0]["vulnerability"];
    $type_code_smell=$anomalies[0]["code_smell"];

    // Severity
    $severity_blocker=$anomalies[0]["blocker"];
    $severity_critical=$anomalies[0]["critical"];
    $severity_major=$anomalies[0]["major"];
    $severity_info=$anomalies[0]["info"];
    $severity_minor=$anomalies[0]["minor"];

    // Module
    $frontend=$anomalies[0]["frontend"];
    $backend=$anomalies[0]["backend"];
    $batch=$anomalies[0]["batch"];

    /* On récupère les notes (A-F) */
    $types=["reliability", "security", "sqale"];
    foreach ($types as $type)
    {
      $sql="SELECT type, value FROM notes WHERE maven_key='". $maven_key."' AND type='".$type."' ORDER BY date DESC LIMIT 1";
      $r=$em->getConnection()->prepare($sql)->executeQuery();
      $note=$r->fetchAllAssociative();
      if ($type=="reliability"){$note_reliability=$note[0]["value"];}
      if ($type=="security"){$note_security=$note[0]["value"];}
      if ($type=="sqale"){$note_sqale=$note[0]["value"];}
    }

    return $response->setData([
    "dette"=>$dette,
    "dette_reliability"=>$dette_reliability,
    "dette_vulnerability"=>$dette_vulnerability,
    "dette_code_smell"=>$dette_code_smell,
    "dette_minute"=>$dette_minute,
    "dette_reliability_minute"=>$dette_reliability_minute,
    "dette_vulnerability_minute"=>$dette_vulnerability_minute,
    "dette_code_smell_minute"=>$dette_code_smell_minute,
    "bug"=>$type_bug, "vulnerability"=>$type_vulnerability, "code_smell"=>$type_code_smell,
    "blocker"=>$severity_blocker, "critical"=>$severity_critical, "info"=>$severity_info,
    "major"=>$severity_major, "minor"=>$severity_minor,
    "frontend"=>$frontend, "backend"=>$backend, "batch"=>$batch,
    "note_reliability"=>$note_reliability, "note_security"=>$note_security, "note_sqale"=>$note_sqale, Response::HTTP_OK]);
  }

  /**
   * description
   * Récupère les hotspots du projet
  */
  #[Route('/api/peinture/projet/hotspots', name: 'peinture_projet_hotspots', methods: ['GET'])]
  public function peinture_projet_hotspots(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    $is_valide=$this->is_valide($em, $maven_key);
    if ($is_valide["code"]==406) {
      return $response->setData(["message"=>self::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]);
    }

    // On récupère la dernière version et sa date de publication
    $sql="select count(*) as to_review FROM hotspots WHERE maven_key='".$maven_key."' AND status='TO_REVIEW'";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $to_review=$r->fetchAllAssociative();

    $sql="select count(*) as reviewed FROM hotspots WHERE maven_key='".$maven_key."' AND status='REVIEWED'";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $reviewed=$r->fetchAllAssociative();

    if (empty($to_review[0]["to_review"])) { $note="A"; }
    else {
      $ratio=intval($reviewed[0]["reviewed"])*100/intval($to_review[0]["to_review"])+intval($reviewed[0]["reviewed"]);
      if ($ratio >= 80) { $note="A"; }
      if ($ratio >= 70 && $ratio < 80) { $note="B"; }
      if ($ratio >= 50 && $ratio < 70) { $note="C"; }
      if ($ratio >= 30 && $ratio < 50) { $note="D"; }
      if ($ratio < 30) { $note="E"; }
    }

    return $response->setData(["note"=>$note, Response::HTTP_OK]);
  }

  /**
   * description
   * Récupère les hotspots du projet
  */
  #[Route('/api/peinture/projet/hotspot/details', name: 'peinture_projet_hotspot_details', methods: ['GET'])]
  public function peinture_projet_hotspot_details(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    $is_valide=$this->is_valide($em, $maven_key);
    if ($is_valide["code"]==406) {
      return $response->setData(["message"=>self::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]);
    }

    $high=0; $medium=0; $low=0;
    // On récupère la dernière version et sa date de publication
    $sql="select niveau, count(*) as hotspot FROM hotspots WHERE maven_key='".$maven_key."' AND status='TO_REVIEW' GROUP BY niveau";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $niveaux=$r->fetchAllAssociative();

    foreach($niveaux as $niveau) {
      if ($niveau["niveau"]=="1") { $high=$niveau["hotspot"]; }
      if ($niveau["niveau"]=="2") {$medium=$niveau["hotspot"]; }
      if ($niveau["niveau"]=="3") {$low=$niveau["hotspot"]; }
     }
     $total=intval($high)+intval($medium)+intval($low);
     return $response->setData(["code"=>200, "total"=>$total,"high"=>$high,"medium"=>$medium, "low"=>$low, Response::HTTP_OK]);
  }

  /**
   * description
   * Récupère les exclusions nosonar et suppressWarning pour Java
  */
  #[Route('/api/peinture/projet/nosonar/details', name: 'peinture_projet_nosonar_details', methods: ['GET'])]
  public function peinture_projet_nosonar_details(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    $sql="select rule, count(*) as total FROM no_sonar WHERE maven_key='".$maven_key."' GROUP BY rule";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $rules=$r->fetchAllAssociative();
    $S1309=0; $nosonar=0; $total=0;

    if (empty($rules)===false){
      foreach($rules as $rule) {
        if ($rule["rule"]=="java:S1309") { $S1309=$rule["total"]; }
        if ($rule["rule"]=="java:NoSonar") {$nosonar=$rule["total"]; }
       }
       $total=intval($S1309)+intval($nosonar);
    }

     return $response->setData(["total"=>$total,"s1309"=>$S1309, "nosonar"=>$nosonar, Response::HTTP_OK]);
  }

}
