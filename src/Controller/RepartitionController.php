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

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;


class RepartitionController extends AbstractController
{

    /**
     * projetRepartition
     * @param  mixed $em
     * @param  mixed $request
     * @return Response
     */
    #[Route('/projet/repartition', name: 'projet_repartition')]
    public function projetRepartition(EntityManagerInterface $em, Request $request): Response
    {
        // On récupère la clé du projet
        $mavenKey = $request->get('mavenKey');
        // On enregistre le nom du projet
        $app=explode(":", $mavenKey);

        // On se connecte à la base pour connaitre la version du dernier setup pour le projet.
        $sql = "SELECT setup
                FROM temp_repartition
                WHERE maven_key='${mavenKey}'
                ORDER by setup DESC";

        // On execute la requête
        $select = $em->getConnection()
                     ->prepare(trim(preg_replace("/\s+/u", " ", $sql)))
                     ->executeQuery();

       //On récupère le résultat
       $reponse = $select->fetchAllAssociative();

       if (empty($reponse)) {
           $setup=["setup"=>"NaN"];
           $statut="NaN";
        } else {
            $setup=$reponse[0];
            $statut="actuel";
        }

        return $this->render('projet/anomalie.details.html.twig',
        [
            'monApplication' => $app[1],
            'mavenKey' => $mavenKey,
            'setup' =>  $setup,
            'statut'=> $statut,
            'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
        ]);
    }

}
