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

class ApiOwaspPeintureController extends AbstractController
{

  /**
   * description
   * On récupère les résultat Owasp
  */
  #[Route('/api/peinture/owasp/liste', name: 'peinture_owasp_liste', methods: ['GET'])]
  public function peinture_owasp_liste (EntityManagerInterface $em, Request $request): response {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    // On récupere les failles owasp
    $sql="SELECT * FROM owasp WHERE maven_key='". $maven_key."' ORDER BY date_enregistrement DESC LIMIT 1";
    $list=$em->getConnection()->prepare($sql)->executeQuery();
    $owasp=$list->fetchAllAssociative();

    // si on ne trouve pas la liste
    if (empty($owasp)) { return $response->setData(["code"=>"406"]); }

    // Informations
    $total = $owasp[0]["a1"] + $owasp[0]["a2"] + $owasp[0]["a3"] + $owasp[0]["a4"] + $owasp[0]["a5"] + $owasp[0]["a6"] + $owasp[0]["a7"] + $owasp[0]["a8"] + $owasp[0]["a9"] + $owasp[0]["a10"];

    $bloquante = $owasp[0]["a1_blocker"] + $owasp[0]["a2_blocker"] + $owasp[0]["a3_blocker"] + $owasp[0]["a4_blocker"] + $owasp[0]["a5_blocker"] + $owasp[0]["a6_blocker"] + $owasp[0]["a7_blocker"] + $owasp[0]["a8_blocker"] + $owasp[0]["a9_blocker"] + $owasp[0]["a10_blocker"];

    $critique=$owasp[0]["a1_critical"] + $owasp[0]["a2_critical"] + $owasp[0]["a3_critical"] + $owasp[0]["a4_critical"] + $owasp[0]["a5_critical"] + $owasp[0]["a6_critical"] + $owasp[0]["a7_critical"] + $owasp[0]["a8_critical"] + $owasp[0]["a9_critical"] + $owasp[0]["a10_critical"];

    $majeure = $owasp[0]["a1_major"] + $owasp[0]["a2_major"] + $owasp[0]["a3_major"] + $owasp[0]["a4_major"] + $owasp[0]["a5_major"] + $owasp[0]["a6_major"] + $owasp[0]["a7_major"] + $owasp[0]["a8_major"] + $owasp[0]["a9_major"] + $owasp[0]["a10_major"];

     $mineure = $owasp[0]["a1_minor"] + $owasp[0]["a2_minor"] + $owasp[0]["a3_minor"] + $owasp[0]["a4_minor"] + $owasp[0]["a5_minor"] + $owasp[0]["a6_minor"] + $owasp[0]["a7_minor"] + $owasp[0]["a8_minor"] + $owasp[0]["a9_minor"] + $owasp[0]["a10_minor"];

    return $response->setData(
      ["code"=>"200", "total"=>$total,
      "bloquante"=>$bloquante, "critique"=>$critique, "majeure"=>$majeure, "mineure"=>$mineure,
      "a1"=>$owasp[0]["a1"],"a2"=>$owasp[0]["a2"],"a3"=>$owasp[0]["a3"],"a4"=>$owasp[0]["a4"],
      "a5"=>$owasp[0]["a5"],"a6"=>$owasp[0]["a6"],"a7"=>$owasp[0]["a7"],"a8"=>$owasp[0]["a8"],
      "a9"=>$owasp[0]["a9"],"a10"=>$owasp[0]["a10"],
      "a1_blocker"=>$owasp[0]["a1_blocker"], "a2_blocker"=>$owasp[0]["a2_blocker"], "a3_blocker"=>$owasp[0]["a3_blocker"], "a4_blocker"=>$owasp[0]["a4_blocker"], "a5_blocker"=>$owasp[0]["a5_blocker"], "a6_blocker"=>$owasp[0]["a6_blocker"], "a7_blocker"=>$owasp[0]["a7_blocker"], "a8_blocker"=>$owasp[0]["a8_blocker"], "a9_blocker"=>$owasp[0]["a9_blocker"], "a10_blocker"=>$owasp[0]["a10_blocker"],
      "a1_critical"=>$owasp[0]["a1_critical"], "a2_critical"=>$owasp[0]["a2_critical"], "a3_critical"=>$owasp[0]["a3_critical"], "a4_critical"=>$owasp[0]["a4_critical"], "a5_critical"=>$owasp[0]["a5_critical"], "a6_critical"=>$owasp[0]["a6_critical"], "a7_critical"=>$owasp[0]["a7_critical"], "a8_critical"=>$owasp[0]["a8_critical"], "a9_critical"=>$owasp[0]["a9_critical"], "a10_critical"=>$owasp[0]["a10_critical"],
      "a1_major"=>$owasp[0]["a1_major"], "a2_major"=>$owasp[0]["a2_major"], "a3_major"=>$owasp[0]["a3_major"], "a4_major"=>$owasp[0]["a4_major"], "a5_major"=>$owasp[0]["a5_major"], "a6_major"=>$owasp[0]["a6_major"], "a7_major"=>$owasp[0]["a7_major"], "a8_major"=>$owasp[0]["a8_major"], "a9_major"=>$owasp[0]["a9_major"], "a10_major"=>$owasp[0]["a10_major"],
      "a1_minor"=>$owasp[0]["a1_minor"], "a2_minor"=>$owasp[0]["a2_minor"], "a3_minor"=>$owasp[0]["a3_minor"], "a4_minor"=>$owasp[0]["a4_minor"], "a5_minor"=>$owasp[0]["a5_minor"], "a6_minor"=>$owasp[0]["a6_minor"], "a7_minor"=>$owasp[0]["a7_minor"], "a8_minor"=>$owasp[0]["a8_minor"], "a9_minor"=>$owasp[0]["a9_minor"], "a10_minor"=>$owasp[0]["a10_minor"],
      Response::HTTP_OK]);
  }

  /**
   * description
   * On récupère les résultats hotspot
  */
  #[Route('/api/peinture/owasp/hotspot/info', name: 'peinture_owasp_hotspot_info', methods: ['GET'])]
  public function peinture_owasp_hotspot_info (EntityManagerInterface $em, Request $request): response  {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    // On compte le nmbre de hotspot REVIEWED
    $sql="SELECT count(*) as reviewed FROM hotspot_owasp WHERE maven_key='". $maven_key."' AND status='REVIEWED'";
    $list=$em->getConnection()->prepare($sql)->executeQuery();
    $reviewed=$list->fetchAllAssociative();

    // On compte le nmbre de hotspot TO_REVIEW
    $sql="SELECT count(*) as to_review FROM hotspot_owasp WHERE maven_key='". $maven_key."' AND status='TO_REVIEW'";
    $list=$em->getConnection()->prepare($sql)->executeQuery();
    $to_review=$list->fetchAllAssociative();

    // On récupère le nombre de hotspot owasp par niveau de sévérité potentiel
    $sql="SELECT probability, count(*) as total FROM hotspot_owasp WHERE maven_key='". $maven_key."' AND status='TO_REVIEW' GROUP BY probability";
    $count=$em->getConnection()->prepare($sql)->executeQuery();
    $probability=$count->fetchAllAssociative();
    $high=0; $medium=0; $low=0;
    foreach ($probability as $elt) {
      if ($elt["probability"]=="HIGH"){$high=$elt["total"];}
      if ($elt["probability"]=="MEDIUM"){$medium=$elt["total"];}
      if ($elt["probability"]=="LOW"){$low=$elt["total"];}
     }

    return $response->setData(
      ["reviewed"=>$reviewed[0]["reviewed"], "to_review"=>$to_review[0]["to_review"], "total"=>$reviewed[0]["reviewed"] + $to_review[0]["to_review"],
      "high"=>$high, "medium"=>$medium, "low"=>$low,
      Response::HTTP_OK]);
  }

  /**
   * description
   * On récupère les résultats de la table hotpsot_owasp
  */
  #[Route('/api/peinture/owasp/hotspot/liste', name: 'peinture_owasp_hotspot_liste', methods: ['GET'])]
  public function peinture_owasp_hotspot_liste (EntityManagerInterface $em, Request $request): response {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    // On compte le nombre de hotspot de type OWASP au statut TO_REVIEWED
    $sql="SELECT menace, count(*) as total FROM hotspot_owasp WHERE maven_key='". $maven_key."' AND status='TO_REVIEW' GROUP BY menace";
    $list=$em->getConnection()->prepare($sql)->executeQuery();
    $menaces=$list->fetchAllAssociative();

    $menace_a1=0; $menace_a2=0; $menace_a3=0; $menace_a4=0; $menace_a5=0; $menace_a6=0; $menace_a7=0; $menace_a8=0; $menace_a9=0; $menace_a10=0;

    foreach ($menaces as $elt) {
      if ($elt["menace"]==="a1") { $menace_a1= $elt["total"]; }
      if ($elt["menace"]==="a2") { $menace_a2= $elt["total"]; }
      if ($elt["menace"]==="a3") { $menace_a3= $elt["total"]; }
      if ($elt["menace"]==="a4") { $menace_a4= $elt["total"]; }
      if ($elt["menace"]==="a5") { $menace_a5= $elt["total"]; }
      if ($elt["menace"]==="a6") { $menace_a6= $elt["total"]; }
      if ($elt["menace"]==="a7") { $menace_a7= $elt["total"]; }
      if ($elt["menace"]==="a8") { $menace_a8= $elt["total"]; }
      if ($elt["menace"]==="a9") { $menace_a9= $elt["total"]; }
      if ($elt["menace"]==="a10") { $menace_a10= $elt["total"]; }
    }

    return $response->setData(
      ["menace_a1"=>$menace_a1, "menace_a2"=>$menace_a2, "menace_a3"=>$menace_a3, "menace_a4"=>$menace_a4,"menace_a5"=>$menace_a5, "menace_a6"=>$menace_a6, "menace_a7"=>$menace_a7, "menace_a8"=>$menace_a8, "menace_a9"=>$menace_a9, "menace_a10"=>$menace_a10,
      Response::HTTP_OK]);
  }

/**
   * description
   * On récupère le détails des failles de type hotpsot
  */
  #[Route('/api/peinture/owasp/hotspot/details', name: 'peinture_owasp_hotspot_details', methods: ['GET'])]
  public function peinture_owasp_hotspot_details (EntityManagerInterface $em, Request $request): response {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    // On compte le nombre de hotspot de type OWASP au statut TO_REVIEWED
    $sql="SELECT * FROM hotspot_details WHERE maven_key='". $maven_key."'  ORDER BY severity";
    $list=$em->getConnection()->prepare($sql)->executeQuery();
    $details=$list->fetchAllAssociative();
    if (empty($details)){ $details="vide"; }

    return $response->setData(["details"=> $details, Response::HTTP_OK]);
  }


  /**
   * description
   * On récupère le détails des failles de type hotpsot
  */
  #[Route('/api/peinture/owasp/hotspot/severity', name: 'peinture_owasp_hotspot_severity', methods: ['GET'])]
  public function peinture_owasp_severity (EntityManagerInterface $em, Request $request): response {
    $maven_key=$request->get('maven_key');
    $menace=$request->get('menace');
    $response = new JsonResponse();

    $str_select="SELECT count(*) as total FROM hotspot_owasp WHERE maven_key='";
    $str_menace="' AND menace='";

    // On compte le nombre de faille OWASP u statut HIGH
    $sql=$str_select.$maven_key.$str_menace.$menace."' AND status='TO_REVIEW' AND probability='HIGH'";
    $count=$em->getConnection()->prepare($sql)->executeQuery();
    $_high=$count->fetchAllAssociative();

    // On compte le nombre de faille OWASP au statut MEDIUM
    $sql=$str_select. $maven_key.$str_menace.$menace."' AND status='TO_REVIEW' AND probability='MEDIUM'";
    $count=$em->getConnection()->prepare($sql)->executeQuery();
    $_medium=$count->fetchAllAssociative();

    // On compte le nombre de faille OWASP au statut LOW
    $sql=$str_select.$maven_key.$str_menace.$menace."' AND status='TO_REVIEW' AND probability='LOW'";
    $count=$em->getConnection()->prepare($sql)->executeQuery();
    $_low=$count->fetchAllAssociative();

    if (empty($_high)){ $high=0; } else {$high=$_high[0];}
    if (empty($_medium)){ $medium=0; } else {$medium=$_medium[0];}
    if (empty($_low)){ $low=0; } else {$low=$_low[0];}

    return $response->setData(["high"=> $high, "medium"=>$medium, "low"=>$low, Response::HTTP_OK]);
  }

}
