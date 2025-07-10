<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\Auth;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Psr\Log\LoggerInterface;

/**
 * [Description LoginController]
 */
class LoginController extends AbstractController
{
    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;
    private $rgaa;

    public function __construct(
        private UrlGeneratorInterface $router,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private AuthenticationUtils $authenticationUtils
    ) {
        $this->params = $params;
        $this->authenticationUtils = $authenticationUtils;
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
        $this->rgaa = $params->get('rgaa');
    }

    /**
     * [Description for genericRender]
     *
     * @return array
     *
     * Created at: 29/10/2024 20:14:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer' => null,
            'logo_entreprise' => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long' => $this->marqueEntrepriseLong,
            'env' => $this->environnement,
            'version' => $this->version,
            'date_copyright' => $this->dateCopyright,
            'rgaa' => $this->rgaa];
    }

    /**
     * [Description for re-login]
     *
     * @param AuthenticationUtils $authenticationUtils
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:33:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/', name: 're_login')]
    public function reLogin(): Response
    {
        /**
         * Si on est déjà connecté
         * On affiche la page /accueil, Si on la page /login
         */
        if ($this->getUser()->getUserIdentifier()) {
            $this->logger->info('[Auth] Utilisateur déjà authentifié - redirection vers le_prompt');
            return $this->redirectToRoute('accueil');
        } else {
            $error = $this->authenticationUtils->getLastAuthenticationError();
            if ($error) {
                $this->logger->warning('[Auth] Échec d\'authentification détecté : ' . $error->getMessage());
            } else {
                $this->logger->info('[Auth] Affichage du formulaire de connexion');
            }
            $render=static::genericRender();
            $render['error'] = $this->authenticationUtils->getLastAuthenticationError();
            $render['type_footer'] = 'complet';
            return $this->render('auth/login.html.twig', $render);
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/login', name: 'login')]
    public function login(): Response
    {
        /** Si on est déjà connecté on redirige l'utilisateur sur la page prompt */
        if (!is_Null($this->getUser())) {
            $this->logger->info('[Auth] Utilisateur connecté - redirection directe vers le_prompt');
            return $this->redirectToRoute('accueil');
        }

        $error = $this->authenticationUtils->getLastAuthenticationError();
        if ($error) {
            $this->logger->warning('[Auth] Échec d\'authentification : ' . $error->getMessage());
        } else {
            $this->logger->info('[Auth] Affichage de la page de connexion (login)');
        }

        $render=static::genericRender();
        $render['erreur'] = $this->authenticationUtils->getLastAuthenticationError();
        $render['type_footer'] = 'complet';

        return $this->render('auth/login.html.twig', $render);
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
        $this->logger->info('[Auth] Déconnexion utilisateur – redirection vers login');
        return new RedirectResponse($this->router->generate('login'));
    }
}
