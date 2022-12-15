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

class ApiDetailsPeintureController extends AbstractController
{
  /**
   * [Description for __construct]
   *
   * @param  private
   * @param mixed
   *
   * Created at: 15/12/2022, 21:09:53 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct(private EntityManagerInterface $em)
  {
    $this->em = $em;
  }

  const HTTP_ERROR_406 = "                   Vous devez lancer une analyse pour ce projet !!!";

  /**
   * is_valide
   * Vérification de l'existence du projet dans la table information_projet
   *
   * @param  mixed $mavenKey
   * @return array
   */
  protected function isValide($mavenKey): array
  {
    /** On regarde si une analyse a été réalisée. */
    $sql = "SELECT * FROM information_projet WHERE maven_key='" . $mavenKey . "' LIMIT 1";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $response = $r->fetchAllAssociative();
    if (empty($response)) {
      return ["code" => 406];
    }
    return ["code" => 200];
  }


  /**
   * [Description for peintureProjetAnomalieDetails]
   * peinture_projet_anomalie_details
   * Récupère les informations sur la dette technique et les anamalies
   *
   * @param Request $request
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:11:15 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/peinture/projet/anomalie/details', name: 'peinture_projet_anomalie_details', methods: ['GET'])]
  public function peintureProjetAnomalieDetails(Request $request): response
  {
    $mavenKey = $request->get('mavenKey');
    $response = new JsonResponse();

    $isValide = $this->isValide($mavenKey);
    if ($isValide["code"] == 406) {
      return $response->setData(["message" => static::HTTP_ERROR_406, Response::HTTP_NOT_ACCEPTABLE]);
    }

    /** On récupère la dernière version et sa date de publication */
    $sql = "SELECT * FROM anomalie WHERE maven_key='" . $mavenKey . "' ORDER BY date_enregistrement DESC LIMIT 1";
    $r = $this->em->getConnection()->prepare($sql)->executeQuery();
    $anomalies = $r->fetchAllAssociative();

    /* Calcul de la dette technique */
    $dette = $anomalies[0]["total_debt"];
    $detteBug = $anomalies[0]["total_debt_bug"];
    $detteVulnerability = $anomalies[0]["total_debt_vulnerability"];
    $detteCodeSmell = $anomalies[0]["total_debt_code_smell"];

    // Calcul du nombre total d'anomalie
    $bug = $anomalies[0]["bug_blocker"] +
            $anomalies[0]["bug_critical"] +
            $anomalies[0]["bug_info"] +
            $anomalies[0]["bug_major"] +
            $anomalies[0]["bug_minor"];

    $vulnerability = $anomalies[0]["vulnerability_blocker"] +
                      $anomalies[0]["vulnerability_critical"] +
                      $anomalies[0]["vulnerability_info"] +
                      $anomalies[0]["vulnerability_major"] +
                      $anomalies[0]["vulnerability_minor"];

    $codeSmell = $anomalies[0]["code_smell_blocker"] +
                  $anomalies[0]["code_smell_critical"] +
                  $anomalies[0]["code_smell_info"] +
                  $anomalies[0]["code_smell_major"] +
                  $anomalies[0]["code_smell_minor"];

    /* Répartition des anomalies par sévérité et par nature*/
    $bugBlocker = $anomalies[0]["bug_blocker"];
    $bugCritical = $anomalies[0]["bug_critical"];
    $bugInfo = $anomalies[0]["bug_info"];
    $bugMajor = $anomalies[0]["bug_major"];
    $bugMinor = $anomalies[0]["bug_minor"];

    $vulnerabilityBlocker = $anomalies[0]["vulnerability_blocker"];
    $vulnerabilityCritical = $anomalies[0]["vulnerability_critical"];
    $vulnerabilityInfo = $anomalies[0]["vulnerability_info"];
    $vulnerabilityMajor = $anomalies[0]["vulnerability_major"];
    $vulnerabilityMinor = $anomalies[0]["vulnerability_minor"];

    $codeSmellBlocker = $anomalies[0]["code_smell_blocker"];
    $codeSmellCritical = $anomalies[0]["code_smell_critical"];
    $codeSmellInfo = $anomalies[0]["code_smell_info"];
    $codeSmellMajor = $anomalies[0]["code_smell_major"];
    $codeSmellMinor = $anomalies[0]["code_smell_minor"];

    return $response->setData([
      "message" => 200,
      "dette" => $dette,
      "detteBug" => $detteBug,
      "detteVulnerability" => $detteVulnerability,
      "detteCodeSmell" => $detteCodeSmell,
      "bug" => $bug,
      "vulnerability" => $vulnerability,
      "codeSmell" => $codeSmell,
      "bugBlocker" => $bugBlocker,
      "bugCritical" => $bugCritical,
      "bugInfo" => $bugInfo,
      "bugMajor" => $bugMajor,
      "bugMinor" => $bugMinor,
      "vulnerabilityBlocker" => $vulnerabilityBlocker,
      "vulnerabilityCritical" => $vulnerabilityCritical,
      "vulnerabilityInfo" => $vulnerabilityInfo,
      "vulnerabilityMajor" => $vulnerabilityMajor,
      "vulnerabilityMinor" => $vulnerabilityMinor,
      "codeSmellBlocker" => $codeSmellBlocker,
      "codeSmelCritical" => $codeSmellCritical,
      "codeSmellInfo" => $codeSmellInfo,
      "codeSmellMajor" => $codeSmellMajor,
      "codeSmellMinor" => $codeSmellMinor,
      Response::HTTP_OK
    ]);
  }
}
