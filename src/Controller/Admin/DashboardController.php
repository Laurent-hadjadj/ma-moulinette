<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Annotation\Route;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Main\Utilisateur;
use App\Entity\Main\Equipe;
use App\Entity\Main\Portefeuille;
use App\Entity\Main\Batch;
use PDO;

use App\Service\Client;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;

use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;

use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use Symfony\Component\Security\Core\User\UserInterface;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * [Description DashboardController]
 */
class DashboardController extends AbstractDashboardController
{
    public static $sonarUrl = "sonar.url";
    public static $removeReturnline = "/\s+/u";

    /**
     * [Description for __construct]
     *
     * @param mixed
     *
     * Created at: 02/01/2023, 18:33:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
    ) {
        $this->em = $em;
    }

    /**
     * [Description for sonarHealth]
     * Vérifie l'état du serveur
     * http://{url}}/api/system/health
     * Encore une fois, c'est null, il faut être admin pour récupérrer le résultat.
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:14:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/health', name: 'sonar_health', methods: ['POST'])]
    public function sonarHealth(Request $request, Client $client): response
    {
        $url = $this->getParameter(static::$sonarUrl) . "/api/system/health";

        /** On appel le client http */
        $result = $client->http($url);
        return new JsonResponse($result, Response::HTTP_OK);
    }

    /**
     * [Description for informationSysteme]
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
    #[Route('/api/system/info', name: 'information_systeme', methods: ['POST'])]
    public function informationSysteme(Request $request, Client $client): response
    {
        $url = $this->getParameter(static::$sonarUrl) . "/api/system/info";

        /** On appel le client http */
        $result = $client->http($url);
        return new JsonResponse($result, Response::HTTP_OK);
    }
    /**
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 02/01/2023, 18:34:07 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[IsGranted('ROLE_UTILISATEUR')]
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        /** Systeme **/
        /** On récupère la version de symfony et de PHP. */
        $symfonyVersion = \Symfony\Component\HttpKernel\Kernel::VERSION;
        $phpVersion = PHP_VERSION;
        $ram = round(memory_get_usage() / 1048576, 2);
        /** On vérifie l'intégrité de la base */
        $sql = "PRAGMA integrity_check;";
        $s = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $s->fetchAllAssociative();
        $integrity = $resultat[0]['integrity_check'];

        /** On récupère le nombre d'utilisateur. */
        $sql = "SELECT count() as total FROM utilisateur;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $applicationUtilisateur = 0;
        } else {
            $applicationUtilisateur = $resultat[0]['total'];
        }
        /** On récupère la version de la base de données. */
        $dbh = new PDO('sqlite::memory:');
        $versionSqlite = $dbh->query('select sqlite_version()')->fetch()[0];
        $dbh = null;

        /** Application */
        /** On récupère le nombre de version de ma-moulinette */
        $sql = "SELECT count() as total FROM ma_moulinette;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $applicationNombreVersion = 0;
        } else {
            $applicationNombreVersion = $resultat[0]['total'];
        }

        /** Statistiques sur le code. */
        $html = ['fichier' => 21, 'code' => 3389, 'comment' => 120, 'vide' => 196, 'total' => 3705 ];
        $php = ['fichier' => 64, 'code' => 8255, 'comment' => 2123, 'vide' => 2173, 'total' => 12551 ];
        $css = ['fichier' => 24, 'code' => 1998, 'comment' => 550, 'vide' => 475, 'total' => 3023 ];
        $js = ['fichier' => 15, 'code' => 3229, 'comment' => 1001, 'vide' => 546, 'total' => 4776 ];
        $md = ['fichier' => 20, 'code' => 1077, 'comment' => 0, 'vide' => 570, 'total' => 1647 ];
        $updateSql = ['fichier' => 7, 'code' => 191, 'comment' => 66, 'vide' => 75, 'total' => 332 ];
        $migration = ['fichier' => 2, 'code' => 881, 'comment' => 10, 'vide' => 14, 'total' => 112 ];

        /** On récupère le nombre de projet en base. */
        $sql = "SELECT count() as total FROM sqlite_master WHERE type = 'table'";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $applicationTable = 0;
        } else {
            $applicationTable = $resultat[0]['total'];
        }

        /** On récupère le nombre de projet sonarqube en base. */
        $sql = "SELECT count() as total FROM liste_projet;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projetNombre = 0;
        } else {
            $projetNombre = $resultat[0]['total'];
        }

        /** On récupère le nombre de profil sonar. */
        $sql = "SELECT count() as total FROM profiles;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projetProfile = 0;
        } else {
            $projetProfile = $resultat[0]['total'];
        }

        /** On récupère le nombre de règle. */
        $sql = "SELECT sum(active_rule_count) as total FROM profiles;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projetRegle = 0;
        } else {
            $projetRegle = $resultat[0]['total'];
        }

        /** On récupère le nombre de projet dans l'historique. */
        $sql = "SELECT count() as total FROM anomalie;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projetAnomalie = 0;
        } else {
            $projetAnomalie = $resultat[0]['total'];
        }

        /** On récupère le nombre de projet dans l'historique. */
        $sql = "SELECT count() as total FROM historique;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projetHistorique = 0;
        } else {
            $projetHistorique = $resultat[0]['total'];
        }

        /** On récupère le nombre de ligne de code analysé. */
        $sql = "SELECT count(DISTINCT project_name) as total FROM mesures;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projetLines = 0;
            $projetTests = 0;
        } else {
            /** On récupère le nombre de projet unique. */
            $limit = $resultat[0]['total'];
            /** On récupère les n premier projet. */
            $sql = "SELECT DISTINCT project_name, lines, tests, date_enregistrement
                    FROM mesures
                    GROUP BY project_name, date_enregistrement
                    ORDER BY date_enregistrement
                    DESC LIMIT $limit;";
            $select = $this->em->getConnection()->prepare($sql)->executeQuery();
            $projets = $select->fetchAllAssociative();
            /** On calcul la somme des nloc et des tests unitaires. */
            $lines = 0;
            $tests = 0;
            foreach ($projets as $projet) {
                $lines = $lines + $projet['lines'];
                $tests = $tests + $projet['tests'];
            }
            $projetLines = $lines;
            $projetTests = $tests;
        }

        /**  Mesures */
        /** On récupère le nombre de signalement. */
        $sql = "SELECT sum(anomalie_total) as total FROM anomalie;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $mesureSignalement = 0;
        } else {
            $mesureSignalement = $resultat[0]['total'];
        }
        /** On récupère le nombre de bug. */
        $sql = "SELECT sum(bug) as total FROM anomalie;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $mesureBug = 0;
        } else {
            $mesureBug = $resultat[0]['total'];
        }
        /** On récupère le nombre de vulnérabilité. */
        $sql = "SELECT sum(vulnerability) as total FROM anomalie;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $mesureVulnerability = 0;
        } else {
            $mesureVulnerability = $resultat[0]['total'];
        }

        /** On récupère le nombre de signalement. */
        $sql = "SELECT sum(code_smell) as total FROM anomalie;";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $mesureCodeSmell = 0;
        } else {
            $mesureCodeSmell = $resultat[0]['total'];
        }


        return $this->render(
            'admin/index.html.twig',
            [
            'dateCopyright' => \date('Y'),
            'date' => $this->getParameter("date"),
            'version' => $this->getParameter("version"),
            'php_version' => $phpVersion,
            'symfony_version' => $symfonyVersion,
            'sqlite_version' => $versionSqlite,
            'application_utilisateur' => $applicationUtilisateur,
            'ram' => $ram,
            'integrity' => $integrity,
            'html' => $html,'php' => $php,'css' => $css, 'js' => $js, 'md' => $md, 'sql' => $updateSql, 'migration' => $migration,
            'application_nombre_version' => $applicationNombreVersion,
            'application_local_version' => $this->getParameter('version'),
            'application_table' => $applicationTable,
            'projet_projet' => $projetNombre,
            'projet_profile' => $projetProfile,
            'projet_regle' => $projetRegle,
            'projet_historique' => $projetHistorique,
            'projet_anomalie' => $projetAnomalie,
            'projet_line' => $projetLines,
            'projet_test' => $projetTests,
            'mesure_signalement' => $mesureSignalement,
            'mesure_bug' => $mesureBug,
            'mesure_vulnerability' => $mesureVulnerability,
            'mesure_codesmell' => $mesureCodeSmell,
        ]
        );
    }

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
        return Dashboard::new()
            ->setTitle('<img src="build/favicon/favicon-32x32.png"> Ma Moulinette')
            ->setFaviconPath('build/favicon/favicon-32x32.png')
            ->renderContentMaximized()
            ->renderSidebarMinimized()
            ->disableDarkMode();
            //->generateRelativeUrls();
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
        yield MenuItem::linkToUrl('Home', 'fas fa-desktop', $this->generateUrl('home'))
            ->setPermission('ROLE_UTILISATEUR');

        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-dashboard')
        ->setPermission('ROLE_UTILISATEUR');

        yield MenuItem::linkToCrud('Utilisateur', 'fas fa-user', Utilisateur::class)
        ->setPermission('ROLE_GESTIONNAIRE');

        yield MenuItem::linkToCrud('Equipe', 'fas fa-users', Equipe::class)
        ->setPermission('ROLE_GESTIONNAIRE');

        yield MenuItem::linkToCrud('Portefeuille', 'fas fa-gamepad', Portefeuille::class)
        ->setPermission('ROLE_GESTIONNAIRE');

        yield MenuItem::linkToCrud('Batch', 'fas fa-gears', Batch::class)
        ->setPermission('ROLE_BATCH');
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

        return parent::configureUserMenu($utilisateur)
            ->setAvatarUrl($utilisateur->getAvatarUrl());
    }

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
        ->addWebpackEncoreEntry('easyBatch')
        ->addWebpackEncoreEntry('easyAdmin');
    }

}
