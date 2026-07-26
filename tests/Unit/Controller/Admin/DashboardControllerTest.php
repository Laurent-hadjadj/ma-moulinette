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

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\DashboardController;
use App\Service\ClientService;
use App\Service\UserAgent\UserAgentTrackingFacade;
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
    /** @var Packages&MockObject */                 private MockObject $assets;
    /** @var RouterInterface&MockObject */          private MockObject $router;
    /** @var ClientService&MockObject */             private MockObject $client;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var ParameterBagInterface&MockObject */    private MockObject $params;

    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;

    private DashboardController $controller;

    protected function setUp(): void
    {
        $this->assets = $this->createMock(Packages::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->twig = $this->createMock(Environment::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

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
            $this->assets, $this->router, $this->client, $this->tracking
        );
        $this->controller->setContainer($container);
    }

    public function testIndexRendersHomeTemplateAndTracks(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with('admin/home.html.twig', $this->callback(fn($ctx) => isset($ctx['dateCopyright'])))
            ->willReturn('<html>home</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>home</html>', $response->getContent());
    }

    public function testBatchSuiviRedirectsToProjet(): void
    {
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

        $this->assertStringContainsString('Ma Moulinette', $dashboard->getAsDto()->getTitle());
    }

    public function testConfigureMenuItemsYieldsExpectedSections(): void
    {
        $items = iterator_to_array($this->controller->configureMenuItems(), false);

        // 3 sections + 5 CRUD links + 2 route links (Dashboard/Statistiques) + 1 "retour" = 11 items
        $this->assertGreaterThanOrEqual(10, count($items));
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

        $this->assertNotNull($actions->getAsDto(\EasyCorp\Bundle\EasyAdminBundle\Config\Crud::PAGE_INDEX)->getAction(
            \EasyCorp\Bundle\EasyAdminBundle\Config\Crud::PAGE_INDEX,
            \EasyCorp\Bundle\EasyAdminBundle\Config\Action::DETAIL
        ));
    }

    public function testConfigureAssetsAddsEntriesAndContent(): void
    {
        $assets = $this->controller->configureAssets();

        $this->assertNotEmpty($assets->getAsDto()->getAssetMapperAssets());
        $this->assertNotEmpty($assets->getAsDto()->getBodyContents());
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

}
