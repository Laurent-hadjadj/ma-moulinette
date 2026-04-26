<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Entity\Utilisateur;
use App\Service\ClientService;
use App\Service\UserAgentTrackingFacade;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Asset\Packages;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class DashboardControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */   private MockObject $em;
    /** @var Packages&MockObject */                 private MockObject $assets;
    /** @var RouterInterface&MockObject */          private MockObject $router;
    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;
    /** @var ClientService&MockObject */             private MockObject $client;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var ParameterBagInterface&MockObject */    private MockObject $params;

    private DashboardController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->assets = $this->createMock(Packages::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->client = $this->createMock(ClientService::class);
        $this->twig = $this->createMock(Environment::class);
        $this->params = $this->createMock(ParameterBagInterface::class);

        $this->params->method('get')->willReturnMap([
            ['version', '2.0.0-RELEASE'],
            ['date', '2026-04-23'],
            ['sonar.url', 'https://sonar.example.com'],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['twig', 'router', 'parameter_bag'], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['router', 1, $this->router],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new DashboardController(
            $this->em, $this->assets, $this->router, $this->tracking, $this->client
        );
        $this->controller->setContainer($container);
    }

    public function testIndexRendersHomeTemplateAndTracks(): void
    {
        $this->tracking->expects($this->once())->method('track')->with('EASY_ADMIN_ACCUEIL');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('admin/home.html.twig', $this->callback(fn($ctx) => isset($ctx['dateCopyright'])))
            ->willReturn('<html>home</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>home</html>', $response->getContent());
    }

    public function testBatchSuiviRedirectsToProjet(): void
    {
        $this->tracking->expects($this->once())->method('track')->with('EASY_ADMIN_REDIRECT_PROJET');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('projet')
            ->willReturn('/projet');

        $response = $this->controller->batchSuivi();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/projet', $response->headers->get('Location'));
    }

    public function testConfigureDashboardReturnsDashboardWithTitle(): void
    {
        $this->assets->expects($this->atLeastOnce())
            ->method('getUrl')
            ->willReturn('/assets/favicon.png');

        $dashboard = $this->controller->configureDashboard();

        $this->assertInstanceOf(Dashboard::class, $dashboard);
    }

    public function testConfigureMenuItemsYieldsExpectedSections(): void
    {
        $items = iterator_to_array($this->controller->configureMenuItems(), false);

        // 2 sections + 5 CRUD links + 2 bottom links + 1 "retour" = 10 items
        $this->assertGreaterThanOrEqual(8, count($items));
    }

    public function testConfigureUserMenuThrowsWhenNotUtilisateur(): void
    {
        $fakeUser = $this->createMock(\Symfony\Component\Security\Core\User\UserInterface::class);

        $this->expectException(\LogicException::class);

        $this->controller->configureUserMenu($fakeUser);
    }

    public function testConfigureActionsAddsDetailOnIndex(): void
    {
        $actions = $this->controller->configureActions();

        $this->assertInstanceOf(\EasyCorp\Bundle\EasyAdminBundle\Config\Actions::class, $actions);
    }

    public function testConfigureAssetsAddsEntriesAndContent(): void
    {
        $assets = $this->controller->configureAssets();

        $this->assertInstanceOf(\EasyCorp\Bundle\EasyAdminBundle\Config\Assets::class, $assets);
    }

    public function testSonarHealthDelegatesToClientService(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->with($this->stringEndsWith('/api/system/health'))
            ->willReturn(['code' => 200, 'json' => ['health' => 'GREEN']]);

        $response = $this->controller->sonarHealth();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testInformationSystemDelegatesToClientService(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->with($this->stringEndsWith('/api/system/info'))
            ->willReturn(['code' => 200, 'json' => ['Server ID' => 'abc']]);

        $response = $this->controller->informationSystem();

        $this->assertInstanceOf(JsonResponse::class, $response);
    }

    public function testStatsRunsAllQueriesAndRendersAdminIndex(): void
    {
        $this->tracking->expects($this->once())->method('track')->with('EASY_ADMIN_STATS');

        $connection = $this->createMock(Connection::class);
        $this->em->method('getConnection')->willReturn($connection);

        // fullRow satisfies every access pattern in stats()
        $fullRow = [
            'idle_count' => 2,
            'not_idle_count' => 1,
            'cache_hit_ratio' => 0.98,
            'transaction_rollback_ratio' => 0.01,
            'read_requests_per_inserted_tuple' => 1.5,
            'read_requests_ratio' => 0.8,
            'seq_scan_ratio' => 0.1,
            'idx_scan_ratio' => 0.9,
            'transaction_per_insert' => 0.3,
            'index_usage_ratio' => 0.7,
            'tuple_per_index_scan' => 2.0,
            'tuple_per_index_read' => 0.6,
            'avg_total_exec_time_seconds' => 1.2,
            'avg_min_exec_time_seconds' => 0.1,
            'avg_max_exec_time_seconds' => 3.4,
            'avg_stddev_exec_time_seconds' => 0.5,
            'version' => '16.0',
            'total' => 1,
            'lines' => 100,
            'tests' => 10,
        ];

        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $connection->method('prepare')->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([$fullRow]);
        // pg_stat_statements query uses executeQuery directly on connection
        $connection->method('executeQuery')->willReturn($result);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('admin/index.html.twig', $this->callback(fn($ctx) =>
                $ctx['postgresql_version'] === '16.0'
                && $ctx['mesure_code_smell'] === 1
                && $ctx['avg_total_exec_time_seconds'] === 1.2
                && $ctx['projet_line'] === 100
            ))
            ->willReturn('<html>stats</html>');

        $response = $this->controller->stats();

        $this->assertSame('<html>stats</html>', $response->getContent());
    }

    public function testStatsHandlesPgStatStatementsExtensionFailure(): void
    {
        $this->tracking->expects($this->once())->method('track');

        $connection = $this->createMock(Connection::class);
        $this->em->method('getConnection')->willReturn($connection);

        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $connection->method('prepare')->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([[
            'idle_count' => 0, 'not_idle_count' => 0,
            'cache_hit_ratio' => 0, 'transaction_rollback_ratio' => 0,
            'read_requests_per_inserted_tuple' => 0, 'read_requests_ratio' => 0,
            'seq_scan_ratio' => 0, 'idx_scan_ratio' => 0, 'transaction_per_insert' => 0,
            'index_usage_ratio' => 0, 'tuple_per_index_scan' => 0, 'tuple_per_index_read' => 0,
            'version' => '16.0', 'total' => 0, 'lines' => 0, 'tests' => 0,
        ]]);

        // Extension pg_stat_statements absent : executeQuery throws
        $connection->method('executeQuery')
            ->willThrowException(new \RuntimeException('relation "pg_stat_statements" does not exist'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('admin/index.html.twig', $this->callback(fn($ctx) =>
                $ctx['avg_total_exec_time_seconds'] === -1
                && $ctx['avg_stddev_exec_time_seconds'] === -1
            ))
            ->willReturn('<html>fallback</html>');

        $response = $this->controller->stats();

        $this->assertSame('<html>fallback</html>', $response->getContent());
    }
}
