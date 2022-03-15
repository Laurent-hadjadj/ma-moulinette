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
    $sql="SELECT nom_projet as nom, version, suppress_warning, no_sonar, nombre_bug as bug, nombre_vulnerability as faille, nombre_code_smell as mauvaise_pratique, hotspot_total as nombre_hotspot, frontend as presentation, backend as metier, batch, note_reliability as fiabilite, note_security as securite, note_hotspot, note_sqale as maintenabilite FROM historique WHERE maven_key='". $maven_key."' GROUP BY date_version ORDER BY date_version ASC";
    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $dash=$select->fetchAllAssociative();

    // On récupére les anomalies par sévérité
    $sql="SELECT nombre_anomalie_bloquante as bloquant, nombre_anomalie_critique as critique, nombre_anomalie_majeur as majeur FROM historique where maven_key='". $maven_key."' GROUP BY date_version ORDER BY date_version ASC";
    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $severite=$select->fetchAllAssociative();

    // Graphique
    $sql="SELECT nombre_bug as bug, nombre_vulnerability as secu, nombre_code_smell as code_smell, date_version as date FROM historique where maven_key='". $maven_key."' GROUP BY date_version ORDER BY date_version ASC";
    $select=$em->getConnection()->prepare($sql)->executeQuery();
    $graph=$select->fetchAllAssociative();

    // On compte le nombre de résultat
    $nl=count((array)$graph);

    for ($i=0; $i<$nl; $i++)
    {
      $bug[$i]=$graph[$i]["bug"];
      $secu[$i]=$graph[$i]["secu"];
      $code_smell[$i]=$graph[$i]["code_smell"];
      $date[$i]=$graph[$i]["date"];
    }

    // on ajote une valeur null a la fin de chaque serie
    $bug[$nl+1]=0;
    $secu[$nl+1]=0;
    $code_smell[$nl+1]=0;
    $dd = new \DateTime($graph[$nl-1]["date"]);
    $dd->modify('+1 day');
    $ddd=$dd->format('Y-m-d');
    $date[$nl+1]=$ddd;

    return $this->render('dash/index.html.twig',
    [   'dash'=>$dash, 'severite'=>$severite, 'nom'=>$dash[0]["nom"],
        'data1'=>json_encode($bug), 'data2'=>json_encode($secu), 'data3'=>json_encode($code_smell), 'labels'=>json_encode($date),
        'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
    ]);
  }
}
