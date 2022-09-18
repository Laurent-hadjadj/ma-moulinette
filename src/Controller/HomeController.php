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

// Annotation
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Response;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;


class HomeController extends AbstractController
{
    private $em;

    // On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    /**
     * index
     *
     * @return Response
     */
    #[Route('/home', name: 'home')]
    public function index(): Response
    {
        $nombreFavori=$this->getParameter('nombre.favori');

        // On récupère les projets en favori. Pour le moment on limite le nombre de projet à 10.
        //SQLite : 0 (false) and 1 (true).
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
                WHERE favori=1
                ORDER BY date_version LIMIT $nombreFavori";
        $select = $this->em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
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

        // On récupère le numéro de la dernère version en base
        $sql = "SELECT version
                FROM ma_moulinette
                ORDER BY date_version DESC LIMIT 1";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $get_version=$select->fetchAllAssociative();
        $bd_version=$get_version[0]['version'];

        // si la dernière version en base est inférieure, on renvoie une alerte ;
        if ($version !== $bd_version ) {
            $message = 'Oooups !!! La base de données est en version '.$bd_version.'. Vous devez passer le script de migration '.$version.'.';
            $this->addFlash('alert', $message);
        }

        return $this->render('home/index.html.twig',
        [
            'nombreFavori' => $nombre, 'favori' => $favori,
            'version' => $version, 'dateCopyright' => \date('Y')
        ]);
    }
}
