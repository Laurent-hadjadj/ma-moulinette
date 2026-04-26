<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Profiling;

use App\Controller\Profiling\ProfilingController;
use App\Service\UserAgentTrackingFacade;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class ProfilingControllerTest extends TestCase
{
    /** @var ParameterBagInterface&MockObject */          private MockObject $params;
    /** @var UserAgentTrackingFacade&MockObject */        private MockObject $tracking;
    /** @var Environment&MockObject */                    private MockObject $twig;
    /** @var FlashBag&MockObject */                       private MockObject $flashBag;
    /** @var AuthorizationCheckerInterface&MockObject */  private MockObject $authChecker;

    private ProfilingController $controller;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
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
        $container->method('has')->willReturnMap([
            ['twig', true],
            ['security.authorization_checker', true],
            ['request_stack', true],
            ['parameter_bag', true],
        ]);
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['security.authorization_checker', 1, $this->authChecker],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new ProfilingController($this->params, $this->tracking);
        $this->controller->setContainer($container);
    }

    public function testProfilingRendersWithFlashWhenNoRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning' && str_contains($v['message'], 'BATCH')));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('profiling/index.html.twig', $this->anything())
            ->willReturn('<html>flash</html>');

        $this->tracking->expects($this->once())->method('track')->with('PROFILING');

        $response = $this->controller->profiling();
        $this->assertSame('<html>flash</html>', $response->getContent());
    }

    public function testProfilingRendersWithoutFlashWhenRoleGranted(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $this->flashBag->expects($this->never())->method('add');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('profiling/index.html.twig', $this->anything())
            ->willReturn('<html>ok</html>');

        $this->controller->profiling();
    }
}
