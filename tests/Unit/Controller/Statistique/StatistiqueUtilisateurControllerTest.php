<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Statistique;

use App\Controller\Statistique\StatistiqueUtilisateurController;
use App\Service\UserAgentReportingService;
use App\Service\UserAgentTrackingFacade;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class StatistiqueUtilisateurControllerTest extends TestCase
{
    /** @var ParameterBagInterface&MockObject */          private MockObject $params;
    /** @var UserAgentReportingService&MockObject */      private MockObject $reporting;
    /** @var UserAgentTrackingFacade&MockObject */        private MockObject $tracking;
    /** @var Environment&MockObject */                    private MockObject $twig;
    /** @var FlashBag&MockObject */                       private MockObject $flashBag;
    /** @var AuthorizationCheckerInterface&MockObject */  private MockObject $authChecker;

    private StatistiqueUtilisateurController $controller;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->reporting = $this->createMock(UserAgentReportingService::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
        ]);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'twig', 'security.authorization_checker', 'request_stack', 'parameter_bag',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['security.authorization_checker', 1, $this->authChecker],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new StatistiqueUtilisateurController($this->params, $this->reporting, $this->tracking);
        $this->controller->setContainer($container);
    }

    public function testStatistiquesFlashesWarningWithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->reporting->expects($this->never())->method('getUtilisateurDisponible');

        $this->twig->expects($this->once())->method('render')->willReturn('<html>no-role</html>');

        $this->controller->statistiques(new Request());
    }

    public function testStatistiquesNormalizesInvalidPeriodToDay(): void
    {
        $this->setUpHappyPath();

        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/utilisateur.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->controller->statistiques(new Request(['period' => 'invalid']));

        $this->assertSame('day', $capturedCtx['period']);
    }

    public function testStatistiquesReturnsEarlyWhenUtilisateurDisponibleFails(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->reporting->method('getPeriodBounds')->willReturn([
            'start' => new \DateTimeImmutable('2026-04-01'),
            'end' => new \DateTimeImmutable('2026-04-30'),
            'label' => 'April',
        ]);
        $this->reporting->expects($this->once())
            ->method('getUtilisateurDisponible')
            ->willReturn(['code' => 500, 'erreur' => 'db down']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->reporting->expects($this->never())->method('getUtilisateurActif');

        $this->twig->expects($this->once())->method('render')->willReturn('<html>err</html>');

        $this->controller->statistiques(new Request());
    }

    public function testStatistiquesReturnsEarlyWhenOsStatsFail(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->reporting->method('getPeriodBounds')->willReturn([
            'start' => new \DateTimeImmutable('2026-04-01'),
            'end' => new \DateTimeImmutable('2026-04-30'),
            'label' => 'April',
        ]);
        $this->reporting->method('getUtilisateurDisponible')->willReturn([
            'code' => 200, 'data' => ['nombre_utilisateur_disponible' => 100],
        ]);
        $this->reporting->method('getUtilisateurActif')->willReturn([
            'code' => 200, 'data' => ['nombre_utilisateur_actif' => 50],
        ]);
        $this->reporting->expects($this->once())
            ->method('getOsStats')
            ->willReturn(['code' => 404, 'erreur' => 'no os']);

        $this->flashBag->expects($this->once())->method('add');

        $this->reporting->expects($this->never())->method('getBrowserStats');

        $this->twig->expects($this->once())->method('render')->willReturn('<html>os-fail</html>');

        $this->controller->statistiques(new Request());
    }

    public function testStatistiquesHappyPathInjectsAllData(): void
    {
        $this->setUpHappyPath();

        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/utilisateur.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->flashBag->expects($this->never())->method('add');

        $this->tracking->expects($this->once())->method('track')->with('STATISTIQUES_UTILISATEUR');

        $this->controller->statistiques(new Request(['period' => 'week', 'week' => '14']));

        $this->assertSame(100, $capturedCtx['nombre_utilisateur_disponible']);
        $this->assertSame(50, $capturedCtx['nombre_utilisateur_actif']);
        $this->assertSame('week', $capturedCtx['period']);
        $this->assertSame('14', $capturedCtx['selected_week']);
    }

    /**
     * Configure all 10 reporting service methods to succeed with minimal payload.
     */
    private function setUpHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $this->reporting->method('getPeriodBounds')->willReturn([
            'start' => new \DateTimeImmutable('2026-04-01'),
            'end' => new \DateTimeImmutable('2026-04-30'),
            'label' => 'April',
        ]);
        $this->reporting->method('getUtilisateurDisponible')->willReturn([
            'code' => 200, 'data' => ['nombre_utilisateur_disponible' => 100],
        ]);
        $this->reporting->method('getUtilisateurActif')->willReturn([
            'code' => 200, 'data' => ['nombre_utilisateur_actif' => 50],
        ]);

        $ok = ['code' => 200, 'data' => ['items' => []]];
        $this->reporting->method('getOsStats')->willReturn($ok);
        $this->reporting->method('getBrowserStats')->willReturn($ok);
        $this->reporting->method('getDeviceStats')->willReturn($ok);
        $this->reporting->method('getSessionPagesReport')->willReturn(['code' => 200, 'pages' => ['items' => []]]);
        $this->reporting->method('getSessionDurationByPeriodStats')->willReturn(['code' => 200, 'items' => []]);
        $this->reporting->method('getAvgSessionDurationReport')->willReturn(['code' => 200, 'data' => []]);
        $this->reporting->method('getUniqueSessionReport')->willReturn(['code' => 200, 'data' => []]);
        $this->reporting->method('getCategoryByUniqueSessionReport')->willReturn(['code' => 200]);
        $this->reporting->method('getUniqueSessionByCategoryReport')->willReturn(['code' => 200]);
    }
}
