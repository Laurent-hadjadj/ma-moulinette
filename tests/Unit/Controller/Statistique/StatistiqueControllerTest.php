<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Statistique;

use App\Controller\Statistique\StatistiqueController;
use App\Service\UserAgentAnalysisService;
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
class StatistiqueControllerTest extends TestCase
{
    /** @var ParameterBagInterface&MockObject */          private MockObject $params;
    /** @var UserAgentTrackingFacade&MockObject */        private MockObject $tracking;
    /** @var UserAgentAnalysisService&MockObject */       private MockObject $analysis;
    /** @var Environment&MockObject */                    private MockObject $twig;
    /** @var FlashBag&MockObject */                       private MockObject $flashBag;
    /** @var AuthorizationCheckerInterface&MockObject */  private MockObject $authChecker;

    private StatistiqueController $controller;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->analysis = $this->createMock(UserAgentAnalysisService::class);
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

        $this->controller = new StatistiqueController($this->params, $this->tracking, $this->analysis);
        $this->controller->setContainer($container);
    }

    public function testStatistiqueRendersIndexTemplate(): void
    {
        $this->tracking->expects($this->once())->method('track')->with('STATISTIQUES');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('statistique/index.html.twig', $this->anything())
            ->willReturn('<html>stats</html>');

        $response = $this->controller->statistique();

        $this->assertSame('<html>stats</html>', $response->getContent());
    }

    public function testRunBatchAnalysisFlashesErrorWithoutInternalRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->analysis->expects($this->never())->method('runBatch');

        $this->twig->expects($this->once())->method('render')->willReturn('<html>no-role</html>');

        $this->controller->runBatchAnalysis();
    }

    public function testRunBatchAnalysisFlashesErrorOnBatchFailure(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->analysis->expects($this->once())
            ->method('runBatch')
            ->with(50)
            ->willReturn(['code' => 500, 'erreurs' => ['e1', 'e2']]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error' && str_contains($v['message'], '500')));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>fail</html>');

        $this->controller->runBatchAnalysis();
    }

    public function testRunBatchAnalysisFlashesInfoOnHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->analysis->expects($this->once())
            ->method('runBatch')
            ->willReturn(['code' => 200, 'processed' => 42, 'erreurs' => []]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(
                fn($v) => $v['type'] === 'info'
                    && str_contains($v['message'], '42 collecté')
                    && str_contains($v['message'], '0 erreurs')
            ));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>ok</html>');

        $this->controller->runBatchAnalysis();
    }
}
