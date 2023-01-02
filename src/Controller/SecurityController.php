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
/** On récupère les exceptions de l'authentification */
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;


/**
 * [Description SecurityController]
 */
class SecurityController extends AbstractController
{
    /**
     * [Description for relogin]
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:33:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
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

    /**
     * [Description for login]
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:33:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/login', name: 'login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        return $this->render('security/login.html.twig', [
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ]);
    }


    /**
     * [Description for logout]
     *
     * @return [type]
     *
     * Created at: 02/01/2023, 18:20:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/logout', name: 'logout')]
    public function logout()
    {
        return new RedirectResponse(
            $this->router->generate('login')
        );
    }
}
