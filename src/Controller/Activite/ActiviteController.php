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

namespace App\Controller\Activite;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

use Doctrine\ORM\EntityManagerInterface;

class ActiviteController extends AbstractController
{
    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:14:50 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(private EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:14:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/activite', name: 'activite', methods: 'GET')]
    public function index(Request $request): Response
    {

        return $this->render('activite/index.html.twig', [
            'version' => $this->getParameter('version'), 'dateCopyright' => \date("Y"),
            Response::HTTP_OK]);
    }
}
