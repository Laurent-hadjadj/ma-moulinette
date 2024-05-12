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

namespace App\Controller\Projet;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/** Securité */
use Symfony\Bundle\SecurityBundle\Security;

/** API */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * [Description ProjetController]
 */
class ProjetController extends AbstractController
{
    /**
     * [Description for index]
     * Affiche la page projet
     *
     * @param Security $security
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:16:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/projet', name: 'projet', methods: 'GET')]
    public function index(Security $security, Request $request): Response
    {
        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();

        /** On regarde si le bookmark est actif */
        $bookmark = ['null'];
        if ($preference['statut']['bookmark']) {
            $bookmark = $preference['bookmark'];
        }

        $render = [
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y'),
            'bookmark' => $bookmark,
            Response::HTTP_OK
        ];
            return $this->render('projet/index.html.twig', $render);
    }
}
