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

namespace App\Controller\Owasp;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

class OwaspController extends AbstractController
{
    /**
     * [Description for index]
     *
     * @return [type]
     *
     * Created at: 15/12/2022, 22:14:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp', name: 'owasp')]
    public function index(Connection $connection)
    {
        // Récupérer les données de l'OWASP 2017
        $owasp2017 = $connection->fetchAllAssociative('SELECT * FROM owasp_top10 WHERE year = 2017');
        
        // Récupérer les données de l'OWASP 2021
        $owasp2021 = $connection->fetchAllAssociative('SELECT * FROM owasp_top10 WHERE year = 2021');

        return $this->render(
            'owasp/index.html.twig',
            [
                "serveur" => $this->getParameter("sonar.url"),
                "version" => $this->getParameter("version"),
                "dateCopyright" => \date("Y"),
                "owasp2017" => $owasp2017,
                "owasp2021" => $owasp2021,
            ]
        );
    }
}
