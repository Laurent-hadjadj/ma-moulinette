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

class BoardController extends AbstractController
{
 /**
   * description
   * On récupère les résultat Owasp
  */
  #[Route('/suivi', name: 'suivi', methods: ['GET'])]
  public function suivi (EntityManagerInterface $em, Request $request): response
  {
    $maven_key=$request->get('maven_key');

    // On récupere les failles owasp
    $sql="select nom_projet as nom, version, suppress_warning, no_sonar, nombre_bug as bug, nombre_vulnerability as faille, nombre_code_smell as mauvaise_pratique, hotspot_total as nombre_hotspot, frontend as presentation, backend as metier, batch, note_reliability as fiabilite, note_security as securite, note_hotspot, note_sqale as maintenabilite from historique where maven_key='". $maven_key."' order by date_version ASC";
    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $dash=$select->fetchAllAssociative();

    $sql="select nombre_anomalie_bloquante as bloquant, nombre_anomalie_critique as critique, nombre_anomalie_majeur as majeur from historique where maven_key='". $maven_key."' order by date_version ASC";
    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $severite=$select->fetchAllAssociative();

    // si on ne trouve pas la liste
    //if (empty($dash)) { return $response->setData(["code"=>"406"]); }

    return $this->render('dash/index.html.twig',
    [   'dash'=>$dash, 'severite'=>$severite, 'nom'=>$dash[0]["nom"],
        'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
    ]);

  }

}
