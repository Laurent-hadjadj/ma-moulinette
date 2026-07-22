<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Controller\Statistique;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{RedirectResponse, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Controller\Traits\AppUserAware;
use App\Entity\Historique;
use App\Service\MesProjets;
use App\Service\UserAgent\{UserAgentTrackingFacade, UserAgentAnalysisService};

/**
 * [Description StatistiqueController]
 */
class StatistiqueController extends AbstractController
{
    use AppUserAware;

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    // MODIF 2026-06-08 : chemin vers var/admin-stats.json
    private string $statsFile;
    private string $fallbackStatsFile;

    public function __construct(
        ParameterBagInterface $params,
        private EntityManagerInterface $em,
        private UserAgentTrackingFacade $tracking,
        private UserAgentAnalysisService $analysis,
        private MesProjets $mesProjets
    ) {
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');

        $this->statsFile = $params->get('kernel.project_dir') . '/var/admin-stats.json';
        $this->fallbackStatsFile = $params->get('kernel.project_dir') . '/migrations/admin-stats.json';
    }

    /**
     * [Description for genericRender]
     *
     * @return array<int|string, mixed>
     *
     * Created at: 30/10/2024 08:21:04 (Europe/Paris)
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
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for statistique]
     * Ouverture de la page de statistiques
     *
     * @return Response
     *
     * Created at: 04/09/2024 14:57:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/statistiques', name: 'statistiques', methods: ['GET'])]
    public function statistique(): Response
    {
        $this->tracking->track('STATISTIQUES');
        $render = $this->genericRender();
        return $this->render('statistique/index.html.twig', $render);
    }

    /**
     * [Description for runBatchAnalysis]
     * MODIF 2026-06-09 : redirect vers statistiques_utilisateur après traitement.
     * Bouton d'appel déplacé sur statistique/utilisateur.html.twig (ROLE_INTERNAL).
     *
     * @return RedirectResponse
     *
     * Created at: 04/09/2024 14:57:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/statistique/run_batch', name: 'runBatchAnalysis', methods: ['GET'])]
    #[IsGranted('ROLE_INTERNAL', message: 'Vous ne disposez pas des droits suffisants pour accéder à cette page.', statusCode: 403)]
    public function runBatchAnalysis(): RedirectResponse
    {
        // MODIF 2026-06-09 : redirect après batch → statistiques_utilisateur
        $this->tracking->track('STATISTIQUES_BATCH');

        $exec = $this->analysis->runBatch(100);

        /* En cas d'échec, runBatch() relaie la réponse du repository : elle porte 'erreur'
           (le message, au singulier) et non 'erreurs' (la liste du cas nominal). */
        if ($exec['code'] !== 200) {
            $this->addFlash('notice', [
                'type'    => 'error',
                'message' => "Une erreur s'est produite pendant l'analyse des données UserAgent ({$exec['code']})",
                'trace'   => $exec['erreur'] ?? null,
            ]);
            return $this->redirectToRoute('statistiques_utilisateur');
        }

        $erreur = count($exec['erreurs']);
        $this->addFlash('notice', [
            'type'    => 'info',
            'message' => "Collecte terminée : {$exec['processed']} collecté, {$erreur} erreurs.",
            'trace'   => null,
        ]);
        return $this->redirectToRoute('statistiques_utilisateur');
    }

    /**
     * [Description for statistiquesProjet]
     * Liste tous les projets avec leur dernière synthèse de métriques.
     *
     * @return Response
     *
     * Created at: 12/07/2026 12:07:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/statistiques/projet', name: 'statistiques_projet', methods: ['GET'])]
    public function statistiquesProjet(): Response
    {
        $this->tracking->track('STATISTIQUES_PROJET');

        /* MODIF 2026-07-22 : ajout du filtrage par groupe fonctionnel, aligné sur
         * mes_projets/clean_code_synthese — cette page remontait jusqu'ici les
         * métriques de TOUS les projets de la base à n'importe quel utilisateur
         * authentifié, sans restriction de périmètre. */
        $groupes = $this->appUser()->getListeGroupeFonctionnel();
        if (empty($groupes)) {
            $this->addFlash('notice', [
                'type'    => 'warning',
                'message' => 'Vous devez être rattaché à un groupe fonctionnel pour accéder à cette vue (Erreur 404).',
                'trace'   => null,
            ]);
            return $this->render('statistique/projet.html.twig', $this->genericRender() + ['projets' => []]);
        }

        $mesProjets = $this->mesProjets->liste($groupes);
        if ($mesProjets['code'] !== 200 || empty($mesProjets['projets'])) {
            $this->addFlash('notice', [
                'type'    => 'warning',
                'message' => "Aucun projet trouvé pour votre groupe fonctionnel. Vérifiez le tag utilisé dans SonarQube (Erreur 406).",
                'trace'   => null,
            ]);
            return $this->render('statistique/projet.html.twig', $this->genericRender() + ['projets' => []]);
        }

        $mavenKeys = array_values(array_map(
            static fn(array $projet): string => (string) $projet['id'],
            $mesProjets['projets']
        ));

        $result = $this->em->getRepository(Historique::class)->selectAllProjetsDerniereSynthese($mavenKeys);

        if ($result['code'] !== 200) {
            $this->addFlash('notice', [
                'type'    => 'error',
                'message' => 'Erreur lors de la récupération des métriques projets.',
                'trace'   => $result['erreur'] ?? null,
            ]);
            return $this->render('statistique/projet.html.twig', $this->genericRender() + ['projets' => []]);
        }

        return $this->render('statistique/projet.html.twig', $this->genericRender() + [
            'projets' => $result['projets'],
        ]);
    }

    /**
     * [Description for loadStatsFile]
     * Charge et décode un fichier JSON de stats ; retourne null si absent ou invalide.
     *
     * @param string $path
     *
     * @return array<int|string, mixed>|null
     *
     * Created at: 12/07/2026 12:08:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function loadStatsFile(string $path): ?array
    {
        if (!is_file($path)) {
            return null;
        }
        $decoded = json_decode((string)file_get_contents($path), true);
        return is_array($decoded) ? $decoded : null;
    }

    /**
     * [Description for adminDashboard]
     * Page technique : versions PHP/Symfony/PG, RAM, pg_stat, code source.
     * MODIF 2026-06-09 : route déplacée sous /statistiques pour navigation cohérente
     *
     * @return Response
     *
     * Created at: 09/06/2026 13:16:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/statistiques/dashboard', name: 'statistiques_dashboard')]
    public function adminDashboard(): Response
    {
        $symfonyVersion = \Symfony\Component\HttpKernel\Kernel::VERSION;
        $ram = round(memory_get_usage() / 1048576, 2);
        $conn = $this->em->getConnection();
        $results = [];

        foreach ([
            'postgres_version'    => "SELECT current_setting('server_version') AS version",
            'ma_moulinette_count' => "SELECT count(*) as total FROM ma_moulinette.ma_moulinette",
            'table_count'         => "SELECT count(*) as total FROM ma_moulinette.pg_catalog.pg_tables WHERE schemaname = 'ma_moulinette'",
        ] as $key => $sql) {
            $results[$key] = $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
        }

        // MODIF 2026-06-08 : pg_stat_activity dans try/catch (extension optionnelle)
        try {
            $results['pg_stat_activity'] = $conn->executeQuery(
                "SELECT
                    SUM(CASE WHEN state = 'idle' THEN 1 ELSE 0 END) AS idle_count,
                    SUM(CASE WHEN state != 'idle' THEN 1 ELSE 0 END) AS not_idle_count
                FROM ma_moulinette.get_pg_stat_activity() WHERE datname = 'ma_moulinette'"
            )->fetchAllAssociative();
        } catch (\Throwable) {
            $results['pg_stat_activity'] = [];
        }

        // MODIF 2026-06-08 : pg_stat_statements dans try/catch (extension optionnelle)
        try {
            $results['pg_stat_statements'] = $conn->executeQuery(
                "SELECT
                    ROUND((AVG(total_exec_time)/1000)::numeric, 4) AS avg_exec_s,
                    ROUND((MIN(min_exec_time)/1000)::numeric, 4)   AS min_exec_s,
                    ROUND((MAX(max_exec_time)/1000)::numeric, 4)   AS max_exec_s,
                    ROUND((AVG(stddev_exec_time)/1000)::numeric, 4) AS stddev_exec_s
                FROM ma_moulinette.pg_stat_statements"
            )->fetchAllAssociative();
        } catch (\Throwable) {
            $results['pg_stat_statements'] = [];
        }

        $app = explode('-', $this->getParameter('version'));

        // MODIF 2026-06-08 :
        // 1. var/admin-stats.json (local, non commité, effacé en déploiement)
        // 2. migrations/admin-stats.json (commité, survit au déploiement)
        // 3. Valeurs hardcodées si aucun fichier trouvé
        $dynStats = $this->loadStatsFile($this->statsFile)
            ?? $this->loadStatsFile($this->fallbackStatsFile);
        $cloc = $dynStats['cloc'] ?? [];

        return $this->render('statistique/dashboard.html.twig', $this->genericRender() + [
            'php_version'                => PHP_VERSION,
            'symfony_version'            => $symfonyVersion,
            'postgresql_version'         => $results['postgres_version'][0]['version'] ?? '-',
            'ram'                        => $ram,
            'pg_stat_activity_idle'      => $results['pg_stat_activity'][0]['idle_count'] ?? null,
            'pg_stat_activity_not_idle'  => $results['pg_stat_activity'][0]['not_idle_count'] ?? null,
            'pg_stat_statements'         => $results['pg_stat_statements'][0] ?? [],
            'application_nombre_version' => $results['ma_moulinette_count'][0]['total'] ?? 0,
            'application_local_version'  => $app[0],
            'application_table'          => $results['table_count'][0]['total'] ?? 0,
            'app_version'                => $this->getParameter('version'),
            'app_date'                   => $this->getParameter('date'),
            'stats_generated_at'         => $dynStats['generated_at'] ?? null,
            'phpunit_unit'               => $dynStats['tests']['unit']['count'] ?? null,
            'phpunit_integration'        => $dynStats['tests']['integration']['count'] ?? null,
            'html'      => $cloc['html']      ?? ['fichier' => 21,  'code' => 3389, 'comment' => 120,  'vide' => 196,  'total' => 3705],
            'php'       => $cloc['php']       ?? ['fichier' => 64,  'code' => 8255, 'comment' => 2123, 'vide' => 2173, 'total' => 12551],
            'css'       => $cloc['css']       ?? ['fichier' => 24,  'code' => 1998, 'comment' => 550,  'vide' => 475,  'total' => 3023],
            'js'        => $cloc['js']        ?? ['fichier' => 15,  'code' => 3229, 'comment' => 1001, 'vide' => 546,  'total' => 4776],
            'md'        => $cloc['md']        ?? ['fichier' => 20,  'code' => 1077, 'comment' => 0,    'vide' => 570,  'total' => 1647],
            'sql'       => $cloc['sql']       ?? ['fichier' => 7,   'code' => 191,  'comment' => 66,   'vide' => 75,   'total' => 332],
            'migration' => $cloc['migration'] ?? ['fichier' => 2,   'code' => 881,  'comment' => 10,   'vide' => 14,   'total' => 112],
        ]);
    }

    /**
     * [Description for adminStats]
     * Page fonctionnelle : utilisateurs, projets SonarQube, anomalies, charts.
     * MODIF 2026-06-09 : route déplacée sous /statistiques pour navigation cohérente
     *
     * @return Response
     *
     * Created at: 09/06/2026 13:17:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/statistiques/ma-moulinette', name: 'statistiques_sonar_report')]
    public function adminStats(): Response
    {
        $conn = $this->em->getConnection();
        $results = [];

        foreach ([
            'utilisateur_count'    => "SELECT count(*) as total FROM ma_moulinette.utilisateur",
            'projet_count'         => "SELECT count(*) as total FROM ma_moulinette.liste_projet",
            'profile_count'        => "SELECT count(*) as total FROM ma_moulinette.profiles",
            'rule_count'           => "SELECT sum(active_rule_count) as total FROM ma_moulinette.profiles",
            'historique_count'     => "SELECT count(*) as total FROM ma_moulinette.historique",
            'anomalie_count'       => "SELECT count(*) as total FROM ma_moulinette.anomalie",
            'mesure_signalement'   => "SELECT sum(anomalie_total) as total FROM ma_moulinette.anomalie",
            'mesure_bug'           => "SELECT sum(bug) as total FROM ma_moulinette.anomalie",
            'mesure_vulnerability' => "SELECT sum(vulnerability) as total FROM ma_moulinette.anomalie",
            'mesure_code_smell'    => "SELECT sum(code_smell) as total FROM ma_moulinette.anomalie",
            'mesure_lines'         => "SELECT count(DISTINCT project_name) as total FROM ma_moulinette.mesures",
        ] as $key => $sql) {
            $results[$key] = $conn->prepare($sql)->executeQuery()->fetchAllAssociative();
        }

        $projetLines = 0;
        $projetTests = 0;
        $limit = (int)($results['mesure_lines'][0]['total'] ?? 0);
        if ($limit > 0) {
            $rows = $conn->executeQuery(
                "SELECT DISTINCT project_name, lines, tests, date_enregistrement
                FROM ma_moulinette.mesures
                GROUP BY project_name, lines, tests, date_enregistrement
                ORDER BY date_enregistrement DESC
                LIMIT " . $limit
            )->fetchAllAssociative();
            foreach ($rows as $row) {
                $projetLines += (int)($row['lines'] ?? 0);
                $projetTests += (int)($row['tests'] ?? 0);
            }
        }

        $bug           = (int)($results['mesure_bug'][0]['total'] ?? 0);
        $vulnerability = (int)($results['mesure_vulnerability'][0]['total'] ?? 0);
        $codeSmell     = (int)($results['mesure_code_smell'][0]['total'] ?? 0);

        return $this->render('statistique/ma-moulinette.html.twig', $this->genericRender() + [
            'application_utilisateur' => $results['utilisateur_count'][0]['total'] ?? 0,
            'projet_projet'           => $results['projet_count'][0]['total'] ?? 0,
            'projet_profile'          => $results['profile_count'][0]['total'] ?? 0,
            'projet_regle'            => $results['rule_count'][0]['total'] ?? 0,
            'projet_historique'       => $results['historique_count'][0]['total'] ?? 0,
            'projet_anomalie'         => $results['anomalie_count'][0]['total'] ?? 0,
            'projet_line'             => $projetLines,
            'projet_test'             => $projetTests,
            'mesure_signalement'      => $results['mesure_signalement'][0]['total'] ?? 0,
            'mesure_bug'              => $bug,
            'mesure_vulnerability'    => $vulnerability,
            'mesure_code_smell'       => $codeSmell,
            'chart_anomalies' => json_encode([
                'labels' => ['Fiabilité', 'Sécurité', 'Maintenabilité'],
                'data'   => [$bug, $vulnerability, $codeSmell],
                'colors' => ['#cc4b37', '#d79a00', '#1779ba'],
            ]),
            'chart_projets' => json_encode([
                'labels' => ['Projets SQ', 'Profils', 'Historique', 'Analysés'],
                'data'   => [
                    (int)($results['projet_count'][0]['total'] ?? 0),
                    (int)($results['profile_count'][0]['total'] ?? 0),
                    (int)($results['historique_count'][0]['total'] ?? 0),
                    (int)($results['anomalie_count'][0]['total'] ?? 0),
                ],
                'colors' => ['#1779ba', '#3adb76', '#ffae00', '#ec5840'],
            ]),
        ]);
    }
}
