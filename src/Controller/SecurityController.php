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
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
// On récupère les exceptions de l'authentification
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


class SecurityController extends AbstractController
{
    #[Route('/', name: 're_login')]
    public function relogin(AuthenticationUtils $authenticationUtils): Response
    {
        /**
         * Si on est déjà connecté
         * On affiche la page /home"
         * Si on la page /login"
         */

        if ($this->getUser()->getUserIdentifier()) {
            return $this->redirectToRoute('home');
        } else {
            return $this->render('homesecurity/login.html.twig', [
                'error' => $authenticationUtils->getLastAuthenticationError(),
                'version' => $this->getParameter('version'),
                'dateCopyright' => \date('Y')
            ]);
        }
    }

    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout()
    {
        return new RedirectResponse(
            $this->router->generate('login')
        );
    }
}
