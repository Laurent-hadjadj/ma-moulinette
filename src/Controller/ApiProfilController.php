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
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Component\HttpFoundation\JsonResponse;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;

class ApiProfilController extends AbstractController
{

   /**
   * description
   * Revoie le tableau des laels et des dataset
   * Permet de tracer un jolie dessin sur la répartition des langages de programmation.
   */
  #[Route('/api/quality/langage', name: 'liste_quality_langage', methods: ['GET'])]
  public function liste_quality_langage(EntityManagerInterface $em): response {
      $liste_label=[];
      $liste_dataset=[];

      // On créé la liste des libellés et des données
      $sql = "SELECT language_name as profile FROM profiles";
      $select = $em->getConnection()->prepare($sql)->executeQuery();
      $labels=$select->fetchAllAssociative();
      foreach ($labels as $label ) { array_push($liste_label, $label["profile"]); }

      $sql = "SELECT active_rule_count as total FROM profiles";
      $select = $em->getConnection()->prepare($sql)->executeQuery();
      $datasets=$select->fetchAllAssociative();
      foreach ($datasets as $dataset ) { array_push($liste_dataset, $dataset["total"]); }

      $response = new JsonResponse();
      return $response->setData(["label"=>$liste_label, "dataset"=>$liste_dataset, "Response::HTTP_OK"]);
    }
}
