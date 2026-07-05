<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

// MODIF 2026-06-09 : déplacé depuis Admin/ — AdminMetricsController fusionné dans StatistiqueController
namespace App\Tests\Unit\Controller\Statistique;
use App\Controller\Statistique\StatistiqueController;
use App\Service\UserAgent\UserAgentAnalysisService;
use App\Service\UserAgent\UserAgentTrackingFacade;
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class StatistiqueControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */    private MockObject $em;
    /** @var ParameterBagInterface&MockObject */     private MockObject $params;
    /** @var Environment&MockObject */               private MockObject $twig;
    /** @var Connection&MockObject */                private MockObject $connection;
    /** @var Statement&MockObject */                 private MockObject $statement;
    /** @var Result&MockObject */                    private MockObject $result;
    /** @var UserAgentTrackingFacade&MockObject */   private MockObject $tracking;
    /** @var UserAgentAnalysisService&MockObject */  private MockObject $analysis;

    private StatistiqueController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement = $this->createMock(Statement::class);
        $this->result = $this->createMock(Result::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->analysis = $this->createMock(UserAgentAnalysisService::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma-Moulinette'],
            ['environnement', 'dev'],
            ['version', '2.0.0-RELEASE'],
            ['date', '2026-04-23'],
            // kernel.project_dir pointe vers un répertoire sans admin-stats.json
            ['kernel.project_dir', sys_get_temp_dir() . '/mm-test-no-stats'],
        ]);

        $this->em->method('getConnection')->willReturn($this->connection);
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['twig', 'parameter_bag'], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new StatistiqueController($this->params, $this->em, $this->tracking, $this->analysis);
        $this->controller->setContainer($container);
    }

    // ===== adminDashboard() =====

    public function testAdminDashboardRendersWithBasicPgInfo(): void
    {
        $this->result->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            [['version' => '16.2']],  // postgres_version
            [['total' => 42]],        // ma_moulinette_count
            [['total' => 55]],        // table_count
        );
        // pg_stat_activity + pg_stat_statements via executeQuery
        $this->connection->method('executeQuery')->willReturn($this->result);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/dashboard.html.twig', $this->callback(fn($ctx) =>
                $ctx['postgresql_version'] === '16.2'
                && $ctx['application_nombre_version'] === 42
                && $ctx['application_table'] === 55
                && isset($ctx['php'])
                && isset($ctx['php_version'])
            ))
            ->willReturn('<html>dashboard</html>');

        $response = $this->controller->adminDashboard();

        $this->assertSame('<html>dashboard</html>', $response->getContent());
    }

    public function testAdminDashboardFallsBackWhenPgStatActivityAbsent(): void
    {
        $this->result->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            [['version' => '16.2']],
            [['total' => 1]],
            [['total' => 50]],
        );

        // pg_stat_activity absent → exception → null transmis au template
        $this->connection->method('executeQuery')
            ->willThrowException(new \RuntimeException('function get_pg_stat_activity() does not exist'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/dashboard.html.twig', $this->callback(fn($ctx) =>
                $ctx['pg_stat_activity_idle'] === null
                && $ctx['pg_stat_activity_not_idle'] === null
            ))
            ->willReturn('<html>dashboard-fallback</html>');

        $response = $this->controller->adminDashboard();

        $this->assertSame('<html>dashboard-fallback</html>', $response->getContent());
    }

    public function testAdminDashboardPgStatActivityAndStatementsPresent(): void
    {
        $pgStatRow = [['idle_count' => 3, 'not_idle_count' => 2]];
        $pgStmtRow = [['avg_exec_s' => 0.12, 'min_exec_s' => 0.01, 'max_exec_s' => 1.5, 'stddev_exec_s' => 0.08]];

        $this->result->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            [['version' => '16.2']],
            [['total' => 5]],
            [['total' => 40]],
        );

        $resultActivity = $this->createMock(Result::class);
        $resultActivity->method('fetchAllAssociative')->willReturn($pgStatRow);

        $resultStatements = $this->createMock(Result::class);
        $resultStatements->method('fetchAllAssociative')->willReturn($pgStmtRow);

        $this->connection->method('executeQuery')
            ->willReturnOnConsecutiveCalls($resultActivity, $resultStatements);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/dashboard.html.twig', $this->callback(fn($ctx) =>
                $ctx['pg_stat_activity_idle'] === 3
                && $ctx['pg_stat_activity_not_idle'] === 2
                && $ctx['pg_stat_statements'] === $pgStmtRow[0]
            ))
            ->willReturn('<html>ok</html>');

        $this->controller->adminDashboard();
    }

    public function testAdminDashboardCodeStatsArraysPresent(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([['version' => '16.2', 'total' => 0]]);
        $this->connection->method('executeQuery')->willReturn($this->result);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/dashboard.html.twig', $this->callback(fn($ctx) =>
                array_key_exists('html', $ctx)
                && array_key_exists('php', $ctx)
                && array_key_exists('css', $ctx)
                && array_key_exists('js', $ctx)
                && array_key_exists('md', $ctx)
                && array_key_exists('sql', $ctx)
                && array_key_exists('migration', $ctx)
                && isset($ctx['html']['fichier'])
                && isset($ctx['php']['code'])
                // nouvelles variables dynamic-stats
                && array_key_exists('stats_generated_at', $ctx)
                && array_key_exists('phpunit_unit', $ctx)
                && array_key_exists('phpunit_integration', $ctx)
            ))
            ->willReturn('<html>code-stats</html>');

        $this->controller->adminDashboard();
    }

    public function testAdminDashboardFallsBackToHardcodedWhenNoStatsFile(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([['version' => '16.2', 'total' => 0]]);
        $this->connection->method('executeQuery')->willReturn($this->result);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/dashboard.html.twig', $this->callback(fn($ctx) =>
                $ctx['stats_generated_at'] === null
                && $ctx['phpunit_unit'] === null
                // fallback : valeurs hardcodées connues
                && $ctx['php']['fichier'] === 64
                && $ctx['html']['code'] === 3389
            ))
            ->willReturn('<html>fallback-hardcoded</html>');

        $this->controller->adminDashboard();
    }

    public function testAdminDashboardUsesStatsFileWhenAvailable(): void
    {
        // Préparer un répertoire + fichier JSON temporaire
        $tmpDir   = sys_get_temp_dir() . '/mm-test-with-stats';
        $varDir   = $tmpDir . '/var';
        @mkdir($varDir, 0777, true);
        $jsonFile = $varDir . '/admin-stats.json';

        file_put_contents($jsonFile, json_encode([
            'generated_at' => '2026-06-08T10:00:00+00:00',
            'cloc' => [
                'php'  => ['fichier' => 99, 'code' => 1111, 'comment' => 222, 'vide' => 333, 'total' => 1666],
                'html' => ['fichier' => 5,  'code' => 500,  'comment' => 50,  'vide' => 50,  'total' => 600],
                'css'  => ['fichier' => 3,  'code' => 300,  'comment' => 30,  'vide' => 30,  'total' => 360],
                'js'   => ['fichier' => 4,  'code' => 400,  'comment' => 40,  'vide' => 40,  'total' => 480],
                'md'   => ['fichier' => 2,  'code' => 200,  'comment' => 0,   'vide' => 20,  'total' => 220],
                'sql'  => ['fichier' => 60, 'code' => 2000, 'comment' => 400, 'vide' => 200, 'total' => 2600],
                'migration' => ['fichier' => 1, 'code' => 10, 'comment' => 0, 'vide' => 2, 'total' => 12],
            ],
            'tests' => [
                'unit'        => ['count' => 2854],
                'integration' => ['count' => 455],
            ],
        ]));

        // Recréer le controller avec kernel.project_dir pointant vers le répertoire temporaire
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma-Moulinette'],
            ['environnement', 'dev'],
            ['version', '2.0.0-RELEASE'],
            ['date', '2026-04-23'],
            ['kernel.project_dir', $tmpDir],
        ]);

        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['twig', 'parameter_bag'], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['parameter_bag', 1, $params],
        ]);

        $ctrl = new StatistiqueController($params, $this->em, $this->tracking, $this->analysis);
        $ctrl->setContainer($container);

        $this->result->method('fetchAllAssociative')->willReturn([['version' => '16.2', 'total' => 0]]);
        $this->connection->method('executeQuery')->willReturn($this->result);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/dashboard.html.twig', $this->callback(fn($ctx) =>
                $ctx['stats_generated_at'] === '2026-06-08T10:00:00+00:00'
                && $ctx['phpunit_unit'] === 2854
                && $ctx['phpunit_integration'] === 455
                && $ctx['php']['fichier'] === 99
                && $ctx['sql']['fichier'] === 60
            ))
            ->willReturn('<html>dynamic-stats</html>');

        $ctrl->adminDashboard();

        // Nettoyage
        @unlink($jsonFile);
        @rmdir($varDir);
        @rmdir($tmpDir);
    }

    public function testAdminDashboardAppVersionSplit(): void
    {
        $this->result->method('fetchAllAssociative')->willReturn([['version' => '16.2', 'total' => 0]]);
        $this->connection->method('executeQuery')->willReturn($this->result);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/dashboard.html.twig', $this->callback(fn($ctx) =>
                $ctx['application_local_version'] === '2.0.0'
                && $ctx['app_version'] === '2.0.0-RELEASE'
            ))
            ->willReturn('<html>ok</html>');

        $this->controller->adminDashboard();
    }

    // ===== adminStats() =====

    private function stubStatsQueries(array $overrides = []): void
    {
        $defaults = [
            'utilisateur_count'    => [['total' => 5]],
            'projet_count'         => [['total' => 10]],
            'profile_count'        => [['total' => 3]],
            'rule_count'           => [['total' => 500]],
            'historique_count'     => [['total' => 200]],
            'anomalie_count'       => [['total' => 8]],
            'mesure_signalement'   => [['total' => 1000]],
            'mesure_bug'           => [['total' => 400]],
            'mesure_vulnerability' => [['total' => 150]],
            'mesure_code_smell'    => [['total' => 450]],
            'mesure_lines'         => [['total' => 3]],
        ];
        $rows = array_merge($defaults, $overrides);

        // 11 prepare+executeQuery appels séquentiels
        $this->result->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(...array_values($rows));

        // executeQuery direct pour la requête mesures lignes/tests
        $mesuresResult = $this->createMock(Result::class);
        $mesuresResult->method('fetchAllAssociative')->willReturn([
            ['lines' => 2000, 'tests' => 300, 'date_enregistrement' => '2026-06-01'],
            ['lines' => 1500, 'tests' => 200, 'date_enregistrement' => '2026-05-15'],
            ['lines' => 500,  'tests' => 50,  'date_enregistrement' => '2026-04-01'],
        ]);
        $this->connection->method('executeQuery')->willReturn($mesuresResult);
    }

    public function testAdminStatsRendersWithAllMetrics(): void
    {
        $this->stubStatsQueries();

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/sonar-report.html.twig', $this->callback(fn($ctx) =>
                $ctx['application_utilisateur'] === 5
                && $ctx['projet_projet'] === 10
                && $ctx['projet_profile'] === 3
                && $ctx['projet_regle'] === 500
                && $ctx['projet_historique'] === 200
                && $ctx['projet_anomalie'] === 8
                && $ctx['mesure_bug'] === 400
                && $ctx['mesure_vulnerability'] === 150
                && $ctx['mesure_code_smell'] === 450
                && $ctx['projet_line'] === 4000
                && $ctx['projet_test'] === 550
            ))
            ->willReturn('<html>stats</html>');

        $response = $this->controller->adminStats();

        $this->assertSame('<html>stats</html>', $response->getContent());
    }

    public function testAdminStatsChartDataIsValidJson(): void
    {
        $this->stubStatsQueries();

        $capturedCtx = [];
        $this->twig->method('render')
            ->willReturnCallback(function (string $tpl, array $ctx) use (&$capturedCtx): string {
                $capturedCtx = $ctx;
                return '<html>ok</html>';
            });

        $this->controller->adminStats();

        $this->assertIsString($capturedCtx['chart_anomalies']);
        $this->assertIsString($capturedCtx['chart_projets']);

        $anomalies = json_decode($capturedCtx['chart_anomalies'], true);
        $projets   = json_decode($capturedCtx['chart_projets'],   true);

        $this->assertArrayHasKey('labels', $anomalies);
        $this->assertArrayHasKey('data',   $anomalies);
        $this->assertArrayHasKey('colors', $anomalies);
        $this->assertCount(3, $anomalies['data']);

        $this->assertArrayHasKey('labels', $projets);
        $this->assertArrayHasKey('data',   $projets);
        $this->assertCount(4, $projets['data']);
    }

    public function testAdminStatsChartAnomaliesDataMatchesMesures(): void
    {
        $this->stubStatsQueries([
            'mesure_bug'           => [['total' => 10]],
            'mesure_vulnerability' => [['total' => 5]],
            'mesure_code_smell'    => [['total' => 20]],
        ]);

        $capturedCtx = [];
        $this->twig->method('render')
            ->willReturnCallback(function (string $tpl, array $ctx) use (&$capturedCtx): string {
                $capturedCtx = $ctx;
                return '<html>ok</html>';
            });

        $this->controller->adminStats();

        $anomalies = json_decode($capturedCtx['chart_anomalies'], true);
        $this->assertSame([10, 5, 20], $anomalies['data']);
    }

    public function testAdminStatsWhenNoMesuresLineIsZero(): void
    {
        $this->stubStatsQueries([
            'mesure_lines' => [['total' => 0]],
        ]);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/sonar-report.html.twig', $this->callback(fn($ctx) =>
                $ctx['projet_line'] === 0
                && $ctx['projet_test'] === 0
            ))
            ->willReturn('<html>no-mesures</html>');

        $this->controller->adminStats();
    }

    public function testAdminStatsZeroAnomaliesDoNotBreakChart(): void
    {
        $this->stubStatsQueries([
            'mesure_bug'           => [['total' => 0]],
            'mesure_vulnerability' => [['total' => 0]],
            'mesure_code_smell'    => [['total' => 0]],
        ]);

        $capturedCtx = [];
        $this->twig->method('render')
            ->willReturnCallback(function (string $tpl, array $ctx) use (&$capturedCtx): string {
                $capturedCtx = $ctx;
                return '<html>ok</html>';
            });

        $this->controller->adminStats();

        $anomalies = json_decode($capturedCtx['chart_anomalies'], true);
        $this->assertSame([0, 0, 0], $anomalies['data']);
    }

    public function testAdminStatsGenericRenderVarsPresent(): void
    {
        $this->stubStatsQueries();

        $capturedCtx = [];
        $this->twig->method('render')
            ->willReturnCallback(function (string $tpl, array $ctx) use (&$capturedCtx): string {
                $capturedCtx = $ctx;
                return '<html>ok</html>';
            });

        $this->controller->adminStats();

        $this->assertArrayHasKey('logo_entreprise', $capturedCtx);
        $this->assertArrayHasKey('marque_entreprise_short', $capturedCtx);
        $this->assertArrayHasKey('version', $capturedCtx);
        $this->assertArrayHasKey('env', $capturedCtx);
        $this->assertArrayHasKey('date_copyright', $capturedCtx);
        $this->assertSame('MM', $capturedCtx['marque_entreprise_short']);
        $this->assertSame('2.0.0-RELEASE', $capturedCtx['version']);
    }

    // ===== statistique() — page hub =====

    public function testStatistiqueRendersIndexTemplate(): void
    {
        $this->tracking->expects($this->once())->method('track')->with('STATISTIQUES');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/index.html.twig', $this->isArray())
            ->willReturn('<html>index</html>');

        $response = $this->controller->statistique();
        $this->assertSame('<html>index</html>', $response->getContent());
    }

    public function testStatistiquePassesGenericRenderVars(): void
    {
        $capturedCtx = [];
        $this->twig->method('render')
            ->willReturnCallback(function (string $tpl, array $ctx) use (&$capturedCtx): string {
                $capturedCtx = $ctx;
                return '<html>ok</html>';
            });

        $this->controller->statistique();

        $this->assertArrayHasKey('logo_entreprise', $capturedCtx);
        $this->assertArrayHasKey('version', $capturedCtx);
        $this->assertArrayHasKey('env', $capturedCtx);
        $this->assertArrayHasKey('date_copyright', $capturedCtx);
        $this->assertSame('MM', $capturedCtx['marque_entreprise_short']);
    }

    // ===== runBatchAnalysis() =====

    /**
     * Crée un partial mock du controller avec addFlash() court-circuité
     * et un container contenant uniquement le routeur (redirect ne nécessite pas twig).
     */
    private function buildControllerForBatch(): StatistiqueController
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturn('/statistiques/utilisateur');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['twig', 'parameter_bag', 'router'], true)
        );
        $container->method('get')->willReturnMap([
            ['twig',          1, $this->twig],
            ['parameter_bag', 1, $this->params],
            ['router',        1, $router],
        ]);

        $ctrl = $this->getMockBuilder(StatistiqueController::class)
            ->setConstructorArgs([$this->params, $this->em, $this->tracking, $this->analysis])
            ->onlyMethods(['addFlash'])
            ->getMock();
        $ctrl->setContainer($container);

        return $ctrl;
    }

    public function testRunBatchRedirectsToUtilisateurOnSuccess(): void
    {
        $ctrl = $this->buildControllerForBatch();
        $this->analysis->method('runBatch')
            ->willReturn(['code' => 200, 'processed' => 42, 'erreurs' => []]);

        $response = $ctrl->runBatchAnalysis();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/statistiques/utilisateur', $response->getTargetUrl());
    }

    public function testRunBatchRedirectsToUtilisateurOnError(): void
    {
        $ctrl = $this->buildControllerForBatch();
        $this->analysis->method('runBatch')
            ->willReturn(['code' => 500, 'processed' => 0, 'erreurs' => ['e1', 'e2']]);

        $response = $ctrl->runBatchAnalysis();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/statistiques/utilisateur', $response->getTargetUrl());
    }

    public function testRunBatchCallsTrackingWithBatchKey(): void
    {
        $ctrl = $this->buildControllerForBatch();
        $this->tracking->expects($this->once())->method('track')->with('STATISTIQUES_BATCH');
        $this->analysis->method('runBatch')
            ->willReturn(['code' => 200, 'processed' => 0, 'erreurs' => []]);

        $ctrl->runBatchAnalysis();
    }

    public function testRunBatchCallsAnalysisWith100(): void
    {
        $ctrl = $this->buildControllerForBatch();
        $this->analysis->expects($this->once())->method('runBatch')->with(100)
            ->willReturn(['code' => 200, 'processed' => 0, 'erreurs' => []]);

        $ctrl->runBatchAnalysis();
    }
}
