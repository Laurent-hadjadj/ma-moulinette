<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Admin;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;
use App\Entity\Equipe;
use App\Entity\Portefeuille;
use App\Entity\Batch;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;

use App\Service\Client;

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
    public function sonarHealth(): response
    {
        $url = $this->getParameter(static::$sonarUrl) . "/api/system/health";

        /** On appel le client http */
        $result = $this->em->client->http($url);
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
    public function informationSysteme(): response
    {
        $url = $this->getParameter(static::$sonarUrl) . "/api/system/info";

        /** On appel le client http */
        $result = $this->em->http($url);
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
    /** Informations système **/
    $symfonyVersion = \Symfony\Component\HttpKernel\Kernel::VERSION;
    $phpVersion = PHP_VERSION;
    $ram = round(memory_get_usage() / 1048576, 2);

    $queries = [
        // Connexion active
        "pg_stat_activity" => "SELECT SUM(CASE WHEN state = 'idle' THEN 1 ELSE 0 END) AS idle_count, SUM(CASE WHEN state != 'idle' THEN 1 ELSE 0 END) AS not_idle_count FROM get_pg_stat_activity() WHERE datname = 'ma_moulinette';",

        // Verrous actifs
        "pg_locks" => "SELECT pid, locktype, relation::regclass AS table, page, tuple,  mode, granted FROM pg_locks WHERE granted = false AND pid IN (SELECT pid FROM pg_stat_activity WHERE datname = 'ma_moulinette')",

        // Statistiques de la base de données
        "pg_stat_database" => "WITH stats AS (SELECT datname,            numbackends, xact_commit, xact_rollback, blks_read, blks_hit, tup_returned, tup_fetched, tup_inserted, tup_updated, tup_deleted
        FROM pg_stat_database WHERE datname = 'ma_moulinette')
        SELECT datname, numbackends, xact_commit, xact_rollback, blks_read, blks_hit, tup_returned, tup_fetched, tup_inserted,
        tup_updated, tup_deleted,
        CASE WHEN (blks_hit + blks_read) = 0 THEN 0 ELSE ROUND(blks_hit::numeric / (blks_hit + blks_read), 4)
        END AS cache_hit_ratio,
        CASE WHEN (xact_commit + xact_rollback) = 0 THEN 0 ELSE ROUND(xact_rollback::numeric / (xact_commit + xact_rollback), 4) END AS transaction_rollback_ratio,
        CASE WHEN tup_inserted = 0 THEN 0 ELSE ROUND(tup_fetched::numeric / tup_inserted::numeric, 2) END AS read_requests_per_inserted_tuple,
        CASE WHEN tup_returned = 0 THEN 0 ELSE ROUND(tup_fetched::numeric / tup_returned::numeric, 4) END AS read_requests_ratio FROM stats",

        // Statistiques des tables
        "pg_stat_all_tables" => "SELECT schemaname, relname,seq_scan, seq_tup_read, idx_scan, idx_tup_fetch,
        n_tup_ins, n_tup_upd, n_tup_del,
        (CASE WHEN seq_tup_read != 0 THEN seq_scan::float / seq_tup_read ELSE 0 END) AS seq_scan_ratio,
        (CASE WHEN idx_tup_fetch != 0 THEN idx_scan::float / idx_tup_fetch ELSE 0 END) AS idx_scan_ratio,
        (CASE WHEN n_tup_ins != 0 THEN (n_tup_upd + n_tup_del)::float / n_tup_ins ELSE 0 END) AS transaction_per_insert FROM pg_stat_all_tables WHERE schemaname='ma_moulinette'",

        // Statistiques des indexes
        "pg_stat_all_indexes" => "SELECT schemaname, relname, indexrelname, idx_scan, idx_tup_read, idx_tup_fetch,
        (CASE WHEN idx_tup_read != 0 THEN idx_scan::float / idx_tup_read ELSE 0 END) AS index_usage_ratio,
        (CASE WHEN idx_scan != 0 THEN idx_tup_fetch::float / idx_scan ELSE 0 END) AS tuple_per_index_scan,
        (CASE WHEN idx_tup_read != 0 THEN idx_tup_fetch::float / idx_tup_read ELSE 0 END) AS tuple_per_index_read
        FROM pg_stat_all_indexes WHERE schemaname='ma_moulinette'",

        // Statistiques SQL
        "pg_stat_statements" => "SELECT  AVG(total_exec_time_seconds) AS avg_total_exec_time_seconds,
            AVG(min_exec_time_seconds) AS avg_min_exec_time_seconds,
            AVG(max_exec_time_seconds) AS avg_max_exec_time_seconds,
            AVG(mean_exec_time_seconds) AS avg_mean_exec_time_seconds,
            AVG(stddev_exec_time_seconds) AS avg_stddev_exec_time_seconds
            FROM (SELECT
            total_exec_time / 1000 AS total_exec_time_seconds,
            min_exec_time / 1000 AS min_exec_time_seconds,
            max_exec_time / 1000 AS max_exec_time_seconds,
            mean_exec_time / 1000 AS mean_exec_time_seconds,
            stddev_exec_time / 1000 AS stddev_exec_time_seconds
        FROM pg_stat_statements ORDER BY total_exec_time DESC LIMIT 10) AS top_queries",

        // Nombre d'utilisateurs
        "utilisateur_count" => "SELECT count(*) as total FROM utilisateur",

        // Version de PostgreSQL
        "postgres_version" => "SELECT current_setting('server_version') AS version",

        // Nombre de versions de ma-moulinette
        "ma_moulinette_count" => "SELECT count(*) as total FROM ma_moulinette",

        // Nombre de tables
        "table_count" => "SELECT count(*) as total FROM pg_catalog.pg_tables WHERE schemaname = 'ma_moulinette';",

        // Nombre de projets SonarQube
        "projet_count" => "SELECT count(*) as total FROM liste_projet",

        // Nombre de profils Sonar
        "profile_count" => "SELECT count(*) as total FROM profiles",

        // Nombre de règles
        "rule_count" => "SELECT sum(active_rule_count) as total FROM profiles",

        // Nombre de projets dans l'historique
        "historique_count" => "SELECT count(*) as total FROM historique",

        // Nombre d'anomalies
        "anomalie_count" => "SELECT count(*) as total FROM anomalie",

        // Nombre de signalements
        "mesure_signalement" => "SELECT sum(anomalie_total) as total FROM anomalie",

        // Nombre de bugs
        "mesure_bug" => "SELECT sum(bug) as total FROM anomalie",

        // Nombre de vulnérabilités
        "mesure_vulnerability" => "SELECT sum(vulnerability) as total FROM anomalie",

        // Nombre de code smells
        "mesure_codesmell" => "SELECT sum(code_smell) as total FROM anomalie",

        // Nombre de lignes de code analysées
        "mesure_lines" => "SELECT count(DISTINCT project_name) as total FROM mesures"
    ];

    $conn = $this->em->getConnection();
    $results = [];
    foreach ($queries as $key => $sql) {
        $stmt = $conn->prepare($sql);
        $results[$key] = $stmt->executeQuery()->fetchAllAssociative();
    }

    // Calcul des lignes de code et des tests unitaires
    $projetLines = 0;
    $projetTests = 0;
    $limit = $results['mesure_lines'][0]['total'] ?? 0;
    if ($limit > 0) {
        $sql = "SELECT DISTINCT project_name, lines, tests, date_enregistrement FROM mesures GROUP BY project_name, lines, tests, date_enregistrement ORDER BY date_enregistrement DESC LIMIT $limit";
        $projets = $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
        foreach ($projets as $projet) {
            $projetLines += $projet['lines'];
            $projetTests += $projet['tests'];
        }
    }

    $html = ['fichier' => 21, 'code' => 3389, 'comment' => 120, 'vide' => 196, 'total' => 3705];
    $php = ['fichier' => 64, 'code' => 8255, 'comment' => 2123, 'vide' => 2173, 'total' => 12551];
    $css = ['fichier' => 24, 'code' => 1998, 'comment' => 550, 'vide' => 475, 'total' => 3023];
    $js = ['fichier' => 15, 'code' => 3229, 'comment' => 1001, 'vide' => 546, 'total' => 4776];
    $md = ['fichier' => 20, 'code' => 1077, 'comment' => 0, 'vide' => 570, 'total' => 1647];
    $updateSql = ['fichier' => 7, 'code' => 191, 'comment' => 66, 'vide' => 75, 'total' => 332];
    $migration = ['fichier' => 2, 'code' => 881, 'comment' => 10, 'vide' => 14, 'total' => 112];

    /** La version de ma-moulinette */
    $app=explode('-', $this->getParameter("version"));

    return $this->render('admin/index.html.twig', [
        'dateCopyright' => date('Y'),
        'date' => $this->getParameter("date"),
        'version' => $this->getParameter('version'),
        'php_version' => $phpVersion,
        'symfony_version' => $symfonyVersion,
        'postgresql_version' => $results['postgres_version'][0]['version'] ?? '-',
        'application_utilisateur' => $results['utilisateur_count'][0]['total'] ?? 0,
        'ram' => $ram,
        'integrity' => 'todo',
        'pg_stat_activity_idle' => $results['pg_stat_activity'][0]['idle_count'],
        'pg_stat_activity_not_idle' => $results['pg_stat_activity'][0]['not_idle_count'],
        'pg_locks' => count($results['pg_locks']),
        'cache_hit_ratio'=> $results['pg_stat_database'][0]['cache_hit_ratio'],
        'transaction_rollback_ratio'=> $results['pg_stat_database'][0]['transaction_rollback_ratio'],
        'read_requests_per_inserted_tuple'=> $results['pg_stat_database'][0]['read_requests_per_inserted_tuple'],
        'read_requests_ratio' => $results['pg_stat_database'][0]['read_requests_ratio'],
        'seq_scan_ratio' => $results['pg_stat_all_tables'][0]['seq_scan_ratio'],
        'idx_scan_ratio' => $results['pg_stat_all_tables'][0]['idx_scan_ratio'],
        'transaction_per_insert' => $results['pg_stat_all_tables'][0]['transaction_per_insert'],
        'index_usage_ratio' => $results['pg_stat_all_indexes'][0]['index_usage_ratio'],
        'tuple_per_index_scan' => $results['pg_stat_all_indexes'][0]['tuple_per_index_scan'],
        'tuple_per_index_read' => $results['pg_stat_all_indexes'][0]['tuple_per_index_read'],
        'avg_total_exec_time_seconds' => $results['pg_stat_statements'][0]['avg_total_exec_time_seconds'],
        'avg_min_exec_time_seconds' => $results['pg_stat_statements'][0]['avg_min_exec_time_seconds'],
        'avg_max_exec_time_seconds' => $results['pg_stat_statements'][0]['avg_max_exec_time_seconds'],
        'avg_stddev_exec_time_seconds' => $results['pg_stat_statements'][0]['avg_stddev_exec_time_seconds'],
        'html' => $html, 'php' => $php, 'css' => $css, 'js' => $js, 'md' => $md, 'sql' => $updateSql, 'migration' => $migration,
        'application_nombre_version' => $results['ma_moulinette_count'][0]['total'] ?? 0,
        'application_local_version' => $app[0],
        'application_table' => $results['table_count'][0]['total'] ?? 0,
        'projet_projet' => $results['projet_count'][0]['total'] ?? 0,
        'projet_profile' => $results['profile_count'][0]['total'] ?? 0,
        'projet_regle' => $results['rule_count'][0]['total'] ?? 0,
        'projet_historique' => $results['historique_count'][0]['total'] ?? 0,
        'projet_anomalie' => $results['anomalie_count'][0]['total'] ?? 0,
        'projet_line' => $projetLines,
        'projet_test' => $projetTests,
        'mesure_signalement' => $results['mesure_signalement'][0]['total'] ?? 0,
        'mesure_bug' => $results['mesure_bug'][0]['total'] ?? 0,
        'mesure_vulnerability' => $results['mesure_vulnerability'][0]['total'] ?? 0,
        'mesure_codesmell' => $results['mesure_codesmell'][0]['total'] ?? 0,
    ]);
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
