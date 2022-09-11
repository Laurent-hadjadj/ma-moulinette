<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;

use Symfony\Component\Security\Core\User\UserInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Main\Utilisateur;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class DashboardController extends AbstractDashboardController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * index
     *
     * @return Response
     */
    #[IsGranted('ROLE_USER')]
    #[Route('/admin', name: 'admin')]
    public function index(): Response
    {
        // Systeme
        $symfony_version = \Symfony\Component\HttpKernel\Kernel::VERSION;
        $php_version = PHP_VERSION;
        $ram=round(memory_get_usage()/1048576,2);
        // On récupère le nombre d'utilisateur
        $sql="SELECT count() as total FROM utilisateur;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $application_utilisateur = 0;
        } else {
            $application_utilisateur=$resultat[0]['total'];
        }


        //Application
        // On récupère le nombre de version de ma-moulinette
        $sql="SELECT count() as total FROM ma_moulinette;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $application_versions=0;
        } else {
            $application_versions=$resultat[0]['total'];
        }
        // Statistiques sur le code
        $html= ['fichier'=>12, 'code'=>2510, 'comment'=>102, 'vide'=>150, 'total'=>2762 ];
        $php= ['fichier'=>53, 'code'=>6782, 'comment'=>1708, 'vide'=>1851, 'total'=>10341 ];
        $css= ['fichier'=>18, 'code'=>1676, 'comment'=>440, 'vide'=>395, 'total'=>2511 ];
        $js= ['fichier'=>12, 'code'=>3190, 'comment'=>947, 'vide'=>538, 'total'=>4675 ];

        // On récupère le nombre de projet en base
        $sql="SELECT count() as total FROM sqlite_master WHERE type = 'table'";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projet_nombre = 0;
        } else {
            $application_table=$resultat[0]['total'];
        }

        // On récupère le nombre de projet en base
        $sql="SELECT count() as total FROM liste_projet;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projet_nombre = 0;
        } else {
            $projet_nombre=$resultat[0]['total'];
        }

        // On récupère le nombre de profil sonar
        $sql="SELECT count() as total FROM profiles;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projet_profile = 0;
        } else {
            $projet_profile=$resultat[0]['total'];
        }

        // On récupère le nombre de règle
        $sql="SELECT sum(active_rule_count) as total FROM profiles;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projet_regle = 0;
        } else {
            $projet_regle=$resultat[0]['total'];
        }

        // On récupère le nombre de projet dans l'historique
        $sql="SELECT count() as total FROM anomalie;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projet_anomalie = 0;
        } else {
            $projet_anomalie=$resultat[0]['total'];
        }

        // On récupère le nombre de projet dans l'historique
        $sql="SELECT count() as total FROM historique;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projet_historique = 0;
        } else {
            $projet_historique=$resultat[0]['total'];
        }

        // On récupère le nombre de ligne de code analysé
        $sql="SELECT count(DISTINCT project_name) as total FROM mesures;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $projet_lines = 0;
            $projet_tests = 0;
        } else {
            // on récupère le nombre de projet unique
            $limit=$resultat[0]['total'];
            // On récupère les n premier projet
            $sql="SELECT DISTINCT project_name, lines, tests, date_enregistrement
                    FROM mesures
                    GROUP BY project_name, date_enregistrement
                    ORDER BY date_enregistrement
                    DESC LIMIT ${limit};";
            $select=$this->em->getConnection()->prepare ($sql)->executeQuery();
            $projets = $select->fetchAllAssociative();
            // on calcul la somme des nloc et des tests unitaires
            $lines=0;
            $tests=0;
            foreach ($projets as $projet) {
                $lines=$lines+ $projet['lines'];
                $tests=$tests+$projet['tests'];
            }
            $projet_lines=$lines;
            $projet_tests=$tests;
        }

        // Mesures
        // On récupère le nombre de signalement
        $sql="SELECT sum(anomalie_total) as total FROM anomalie;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $mesure_signalement = 0;
        } else {
            $mesure_signalement=$resultat[0]['total'];
        }
        // On récupère le nombre de bug
        $sql="SELECT sum(bug) as total FROM anomalie;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $mesure_bug = 0;
        } else {
            $mesure_bug=$resultat[0]['total'];
        }
        // On récupère le nombre de vulnérabilité
        $sql="SELECT sum(vulnerability) as total FROM anomalie;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $mesure_vulnerability = 0;
        } else {
            $mesure_vulnerability=$resultat[0]['total'];
        }

        // On récupère le nombre de signalement
        $sql="SELECT sum(code_smell) as total FROM anomalie;";
        $select=$this->em->getConnection()->prepare($sql)->executeQuery();
        $resultat = $select->fetchAllAssociative();
        if (empty($resultat)) {
            $mesure_codesmell = 0;
        } else {
            $mesure_codesmell=$resultat[0]['total'];
        }


        return $this->render('admin/index.html.twig',
        [
            'dateCopyright' => \date('Y'),
            'php_version' => $php_version,
            'symfony_version' => $symfony_version,
            'application_utilisateur' => $application_utilisateur,
            'ram' => $ram,
            'html'=>$html,'php'=>$php,'css'=>$css, 'js'=>$js,
            'application_versions' => $application_versions,
            'application_version' => $this->getParameter('version'),
            'application_table' => $application_table,
            'projet_projet' => $projet_nombre,
            'projet_profile' => $projet_profile,
            'projet_regle' => $projet_regle,
            'projet_historique' => $projet_historique,
            'projet_anomalie' => $projet_anomalie,
            'projet_line' => $projet_lines,
            'projet_test' => $projet_tests,
            'mesure_signalement' => $mesure_signalement,
            'mesure_bug' => $mesure_bug,
            'mesure_vulnerability' => $mesure_vulnerability,
            'mesure_codesmell' => $mesure_codesmell,
        ]);
    }

    /**
     * configureDashboard
     *
     * @return Dashboard
     */
    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="build/favicon/favicon-32x32.png"> Ma Moulinette')
            ->setFaviconPath('build/favicon/favicon-32x32.png')
            ->renderContentMaximized()
            ->renderSidebarMinimized()
            ->disableDarkMode()
            ->generateRelativeUrls();
    }

    /**
     * configureMenuItems
     *
     * @return iterable
     */
    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToUrl('Home', 'fas fa-desktop', $this->generateUrl('home'))
            ->setPermission('ROLE_USER');
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-dashboard')
        ->setPermission('ROLE_USER');
        yield MenuItem::linkToCrud('Utilisateur', 'fas fa-user', Utilisateur::class)
        ->setPermission('ROLE_ADMIN');
    }

    /**
     * configureUserMenu
     *
     * @param  mixed $utilisateur
     * @return UserMenu
     */
    public function configureUserMenu(UserInterface $utilisateur): UserMenu
    {
        if (!$utilisateur instanceof Utilisateur) {
            throw new \Exception('Mauvais utilisateur !!!');
        }

        return parent::configureUserMenu($utilisateur)
            ->setAvatarUrl($utilisateur->getAvatarUrl());
    }

    public function configureActions(): Actions
    {
        return parent::configureActions()
            ->add(Crud::PAGE_INDEX, Action::DETAIL);
    }

    public function configureAssets(): Assets
    {
        return parent::configureAssets()
            ->addWebpackEncoreEntry('admin');
    }

}
