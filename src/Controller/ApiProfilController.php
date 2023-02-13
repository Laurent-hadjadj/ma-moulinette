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
use Symfony\Component\Routing\Annotation\Route;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;

/** Accès aux tables SLQLite */
use Doctrine\ORM\EntityManagerInterface;

class ApiProfilController extends AbstractController
{

  /**
   * [Description for __construct]
   *  EntityManagerInterface = em
   *
   * @param  private
   *
   * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct(private EntityManagerInterface $em)
  {
    $this->em = $em;
  }

  /**
   * [Description for listeQualityLangage]
   * Revoie le tableau des laels et des dataset
   * Permet de tracer un jolie dessin sur la répartition des langages de programmation.
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:24:33 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/quality/langage', name: 'liste_quality_langage', methods: ['GET'])]
  public function listeQualityLangage(): response
  {
    $listeLabel = [];
    $listeDataset = [];

    /** On créé la liste des libellés et des données */
    $sql = "SELECT language_name AS profile FROM profiles";
    $select = $this->em->getConnection()->prepare($sql)->executeQuery();
    $labels = $select->fetchAllAssociative();
    foreach ($labels as $label) {
      array_push($listeLabel, $label["profile"]);
    }

    $sql = "SELECT active_rule_count AS total FROM profiles";
    $select = $this->em->getConnection()->prepare($sql)->executeQuery();
    $dataSets = $select->fetchAllAssociative();
    foreach ($dataSets as $dataSet) {
      array_push($listeDataset, $dataSet["total"]);
    }

    $response = new JsonResponse();
    return $response->setData(["label" => $listeLabel, "dataset" => $listeDataset, Response::HTTP_OK]);
  }
}
