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


class HomeController extends AbstractController
{

    /**
     * index
     *
     * @param  mixed $em
     * @return Response
     */
    #[Route('/', name: 'home')]
    public function index(EntityManagerInterface $em): Response
    {
        $nombreFavori=$this->getParameter('nombre.favori');

        // On récupère les projets en favori. Pour le moment on limite le nombre de projet à 10.
        $sql = "SELECT DISTINCT nom_projet as nom,
                        version, date_version as date,
                        note_reliability as fiabilite,
                        note_security as securite,
                        note_hotspot as hotspot,
                        note_sqale as sqale,
                        nombre_bug as bug,
                        nombre_vulnerability as vulnerability,
                        nombre_code_smell as code_smell,
                        hotspot_total as hotspots
                FROM historique
                WHERE favori=TRUE
                ORDER BY date_version LIMIT $nombreFavori";
        $select = $em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
        $favoris = $select->fetchAllAssociative();

        if (empty($favoris)) {
            $nombre = 0;
            $favori = 'FALSE';
        } else {
            $nombre = 1;
            $favori = $favoris;
        }

        // On récupère la version de l'application
        $version=$this->getParameter('version');

        // On récupère la dernière en base
        $sql = "SELECT version
                FROM ma_moulinette
                ORDER BY date_version DESC LIMIT 1";
        $select = $em->getConnection()->prepare()->executeQuery();

        return $this->render('home/index.html.twig',
        [
            'nombreFavori' => $nombre, 'favori' => $favori,
            'version' => $version, 'dateCopyright' => \date('Y')
        ]);
    }
}
