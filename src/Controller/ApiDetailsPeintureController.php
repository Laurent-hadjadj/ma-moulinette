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

class ApiDetailsPeintureController extends AbstractController
{

  const HTTP_ERROR_406 = "                   Vous devez lancer une analyse pour ce projet !!!";

   /**
    * is_valide
    * Vérification de l'existence du projet dans la table information_projet
    *
    * @param  mixed $em
    * @param  mixed $maven_key
    * @return array
    */
   protected function is_valide(EntityManagerInterface $em, $maven_key): array
   {
     // On regarde si une analyse a été réalisée.
    $sql="SELECT * FROM information_projet WHERE maven_key='". $maven_key."' LIMIT 1";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $response=$r->fetchAllAssociative();
    if (empty($response)){ return ["code"=>406]; }
    return ["code"=>200];
  }


  /**
   * peinture_projet_anomalie_details
   * Récupère les informations sur la dette technique et les anamalies
   *
   * @param  mixed $em
   * @param  mixed $request
   * @return response
   */
  #[Route('/api/peinture/projet/anomalie/details', name: 'peinture_projet_anomalie_details', methods: ['GET'])]
   public function peinture_projet_anomalie_details(EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');
    $response = new JsonResponse();

    $is_valide=$this->is_valide($em, $maven_key);
    if ($is_valide["code"]==406) {
      return $response->setData(["message"=>static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]);
    }

    // On récupère la dernière version et sa date de publication
    $sql="SELECT * FROM anomalie WHERE maven_key='". $maven_key."' ORDER BY date_enregistrement DESC LIMIT 1";
    $r=$em->getConnection()->prepare($sql)->executeQuery();
    $anomalies=$r->fetchAllAssociative();

    /* Calcul de la dette technique */
    $dette=$anomalies[0]["total_debt"];
    $dette_bug=$anomalies[0]["total_debt_bug"];
    $dette_vulnerability=$anomalies[0]["total_debt_vulnerability"];
    $dette_code_smell=$anomalies[0]["total_debt_code_smell"];

    // Calcul du nombre total d'anomalie
    $bug=$anomalies[0]["bug_blocker"]+$anomalies[0]["bug_critical"]+$anomalies[0]["bug_info"]+$anomalies[0]["bug_major"]+$anomalies[0]["bug_minor"];

    $vulnerability=$anomalies[0]["vulnerability_blocker"]+$anomalies[0]["vulnerability_critical"]+$anomalies[0]["vulnerability_info"]+$anomalies[0]["vulnerability_major"]+$anomalies[0]["vulnerability_minor"];

    $codeSmell=$anomalies[0]["code_smell_blocker"]+$anomalies[0]["code_smell_critical"]+$anomalies[0]["code_smell_info"]+$anomalies[0]["code_smell_major"]+$anomalies[0]["code_smell_minor"];

    /* Répartition des anomalies par sévérité et par nature*/
    $bug_blocker=$anomalies[0]["bug_blocker"];
    $bug_critical=$anomalies[0]["bug_critical"];
    $bug_info=$anomalies[0]["bug_info"];
    $bug_major=$anomalies[0]["bug_major"];
    $bug_minor=$anomalies[0]["bug_minor"];

    $vulnerability_blocker=$anomalies[0]["vulnerability_blocker"];
    $vulnerability_critical=$anomalies[0]["vulnerability_critical"];
    $vulnerability_info=$anomalies[0]["vulnerability_info"];
    $vulnerability_major=$anomalies[0]["vulnerability_major"];
    $vulnerability_minor=$anomalies[0]["vulnerability_minor"];

    $code_smell_blocker=$anomalies[0]["code_smell_blocker"];
    $code_smell_critical=$anomalies[0]["code_smell_critical"];
    $code_smell_info=$anomalies[0]["code_smell_info"];
    $code_smell_major=$anomalies[0]["code_smell_major"];
    $code_smell_minor=$anomalies[0]["code_smell_minor"];

    return $response->setData(["message"=>"200",
    "dette"=>$dette, "dette_bug"=>$dette_bug, "dette_vulnerability"=>$dette_vulnerability, "dette_code_smell"=>$dette_code_smell,
    "bug"=>$bug, "vulnerability"=>$vulnerability, "code_smell"=>$codeSmell,
    "bug_blocker"=>$bug_blocker, "bug_critical"=>$bug_critical, "bug_info"=>$bug_info,
    "bug_major"=>$bug_major, "bug_minor"=>$bug_minor,
    "vulnerability_blocker"=>$vulnerability_blocker, "vulnerability_critical"=>$vulnerability_critical, "vulnerability_info"=>$vulnerability_info, "vulnerability_major"=>$vulnerability_major, "vulnerability_minor"=>$vulnerability_minor,
    "code_smell_blocker"=>$code_smell_blocker, "code_smell_critical"=>$code_smell_critical, "code_smell_info"=>$code_smell_info, "code_smell_major"=>$code_smell_major, "code_smell_minor"=>$code_smell_minor,
    Response::HTTP_OK]);
  }
}
