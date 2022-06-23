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

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;


class RepartitionController extends AbstractController
{

    /**
     * index
     *
     * @param  mixed $em
     * @return Response
     */
    #[Route('/projet/repartition', name: 'repartition')]
    public function index(EntityManagerInterface $em): Response
    {

        return $this->render('projet/anomalie.details.html.twig',
        [
            'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
        ]);
    }
}
