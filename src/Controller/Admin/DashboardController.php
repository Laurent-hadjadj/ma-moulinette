<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Admin;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\HttpFoundation\{Response, JsonResponse};
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\{Utilisateur};
use App\Service\ClientService;
use App\Service\UserAgent\UserAgentTrackingFacade;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\{AdminDashboard, AdminRoute};
use EasyCorp\Bundle\EasyAdminBundle\Config\{Crud, Assets, Action, Actions, MenuItem, UserMenu, Dashboard};
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;


/**
 * [Description DashboardController]
 */
#[AdminDashboard(routePath: '/admin', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private static string $sonarUrl = "sonar.url";

    /**
     * [Description for __construct]
     *
     * Created at: 02/01/2023, 18:33:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private Packages $assets,
        private RouterInterface $router,
        private ClientService $client,
        private UserAgentTrackingFacade $tracking
    ) {}

    /**
     * [Description for sonarHealth]
     * Vérifie l'état du serveur
     * http://{url}}/api/system/health
     * Encore une fois, c'est null, il faut être admin pour récupérer le résultat.
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:14:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/health', name: 'sonar_health', methods: ['POST'])]
    public function sonarHealth(): Response
    {
        $url = rtrim($this->getParameter(self::$sonarUrl), '/') . "/api/system/health";

        /** On appel le client http */
        $result = $this->client->httpSonarQube($url);
        return new JsonResponse($result, Response::HTTP_OK);
    }

    /**
     * [Description for informationSystème]
     * On récupère les informations système du serveur
     * http://{url}}/api/system/info
     *
     * Attention, il faut avoir le role sonar administrateur
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:14:39 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/system/info', name: 'information_system', methods: ['POST'])]
    public function informationSystem(): Response
    {
        $url = rtrim($this->getParameter(self::$sonarUrl), '/') . "/api/system/info";

        /** On appel le client http */
        $result = $this->client->httpSonarQube($url);
        return new JsonResponse($result, Response::HTTP_OK);
    }

    #[IsGranted('ROLE_UTILISATEUR')]
    #[AdminRoute('/admin', name: 'admin')]
    public function index(): Response
    {
        $this->tracking->track('ADMIN_HOME');
        return $this->render('admin/home.html.twig', ['dateCopyright' => date('Y')]);
    }

    #[Route('/admin/projet', name: 'admin_projet')]
    public function batchSuivi(): Response
    {
        return new RedirectResponse($this->router->generate('projet'));
    }

    // MODIF 2026-06-08 : adminDashboard() déplacé dans AdminMetricsController

    /**
     * [Description for configureDashboard]
     *
     * @return Dashboard
     *
     * Created at: 02/01/2023, 18:34:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureDashboard(): Dashboard
    {
        $faviconPath = $this->assets->getUrl('favicon/favicon-32x32.png');
        return Dashboard::new()
            ->setTitle('<img src="' . $faviconPath . '" alt="Logo"> Ma Moulinette')
            ->setFaviconPath($faviconPath)
            ->renderContentMaximized()
            ->renderSidebarMinimized()
            ->disableDarkMode();
    }

    /**
     * [Description for configureMenuItems]
     *
     * @return iterable
     *
     * Created at: 02/01/2023, 18:34:47 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute("Retour à l’application", 'fas fa-backward-step', 'admin_projet');

        yield MenuItem::section('Gestion utilisateur');

        yield MenuItem::linkTo(UtilisateurCrudController::class, 'Utilisateurs', 'fas fa-user');
        yield MenuItem::linkTo(GroupeUtilisateurCrudController::class, 'Groupes Utilisateurs', 'fas fa-user-group');
        yield MenuItem::linkTo(GroupeFonctionnelCrudController::class, 'Groupes Fonctionnels', 'fas fa-stamp');

        yield MenuItem::section('Traitement');
        yield MenuItem::linkTo(PortefeuilleCrudController::class, 'Portefeuilles', 'fas fa-wallet');
        yield MenuItem::linkTo(BatchCrudController::class, 'Batch', 'fas fa-cogs');

        yield MenuItem::section('Application');

        // MODIF : routes déplacées sous /statistiques/*
        yield MenuItem::linkToRoute('Dashboard', 'fas fa-chart-bar', 'statistiques_dashboard');
        yield MenuItem::linkToRoute('Ma-Moulinette', 'fas fa-chart-pie', 'statistiques_sonar_report');
        yield MenuItem::linkToRoute('Activité', 'fas fa-chart-line', 'statistiques_utilisateur');
        yield MenuItem::linkToRoute('Projets', 'fas fa-th-list', 'statistiques_projet');
    }

    /**
     * [Description for configureUserMenu]
     *
     * @param UserInterface $utilisateur
     *
     * @return UserMenu
     *
     * Created at: 02/01/2023, 18:34:53 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureUserMenu(UserInterface $utilisateur): UserMenu
    {
        if (!$utilisateur instanceof Utilisateur) {
            throw new \LogicException('Mauvais utilisateur !!!');
        }

        /**
         * Asset Mapper + Packages -> respecte le base path Symfony
         * (donc le X-Forwarded-Prefix injecte par le reverse proxy en prod).
         */
        $url = $this->assets->getUrl('avatar/' . ($utilisateur->getAvatar() ?? 'personne.png'));

        return parent::configureUserMenu($utilisateur)
            ->setAvatarUrl($url);
    }

    /**
     * [Description for configureActions]
     *
     * @return Actions
     *
     * Created at: 09/11/2025 15:00:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    /**
     * [Description for configureAssets]
     * Charge un css spécifique
     *
     * @return Assets
     *
     * Created at: 02/01/2023, 18:35:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addAssetMapperEntry('easy-admin','easy-footer', 'easy-groupe-fonctionnel')
            ->addHtmlContentToBody('<!-- generated at ' . time() . ' -->');
    }

}
