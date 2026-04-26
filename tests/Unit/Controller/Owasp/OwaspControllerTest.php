<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Owasp;

use App\Controller\Owasp\OwaspController;
use App\Entity\OwaspTop10;
use App\Repository\OwaspTop10Repository;
use App\Service\UserAgentTrackingFacade;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class OwaspControllerTest extends TestCase
{
    /** @var ParameterBagInterface&MockObject */   private MockObject $params;
    /** @var EntityManagerInterface&MockObject */  private MockObject $em;
    /** @var UserAgentTrackingFacade&MockObject */ private MockObject $tracking;
    /** @var OwaspTop10Repository&MockObject */    private MockObject $repo;
    /** @var Environment&MockObject */             private MockObject $twig;
    /** @var FlashBag&MockObject */                private MockObject $flashBag;

    private OwaspController $controller;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->repo = $this->createMock(OwaspTop10Repository::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
            ['sonar.version', '2026'],
            ['sonar.url', 'https://sonar.example.com'],
        ]);

        $this->em->method('getRepository')->willReturn($this->repo);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['twig', true],
            ['request_stack', true],
            ['parameter_bag', true],
        ]);
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new OwaspController($this->params, $this->em, $this->tracking);
        $this->controller->setContainer($container);
    }

    /* ============ index ============ */

    public function testIndexFlashesAndRendersWhen2017Fails(): void
    {
        $this->repo->expects($this->once())
            ->method('selectOwaspTop10Referential')
            ->willReturn(['code' => 500, 'erreur' => 'db fail']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'alert'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->anything())
            ->willReturn('<html>err-2017</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>err-2017</html>', $response->getContent());
    }

    public function testIndexFlashesAndRendersWhen2021Fails(): void
    {
        $this->repo->method('selectOwaspTop10Referential')->willReturnOnConsecutiveCalls(
            ['code' => 200, 'liste' => [['a' => 1]]], // 2017 OK
            ['code' => 500, 'erreur' => 'fail-2021'],  // 2021 FAIL
        );

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => str_contains($v['message'], 'fail-2021')));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>err-2021</html>');

        $this->controller->index();
    }

    public function testIndexWarnsWhenBothReferentialsAreEmpty(): void
    {
        $this->repo->method('selectOwaspTop10Referential')->willReturn([
            'code' => 200, 'liste' => [],
        ]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>empty</html>');

        $this->controller->index();
    }

    public function testIndexHappyPathInjectsBothReferentialsAndParams(): void
    {
        $owasp2017 = ['code' => 200, 'liste' => [['menace' => 1]]];
        $owasp2021 = ['code' => 200, 'liste' => [['menace' => 2]]];
        $this->repo->method('selectOwaspTop10Referential')->willReturnOnConsecutiveCalls($owasp2017, $owasp2021);

        $this->flashBag->expects($this->never())->method('add');

        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->tracking->expects($this->once())->method('track')->with('OWASP');

        $this->controller->index();

        $this->assertSame('2026', $capturedCtx['sonar_version']);
        $this->assertSame('https://sonar.example.com', $capturedCtx['serveur']);
        $this->assertSame($owasp2017, $capturedCtx['owasp_2017']);
        $this->assertSame($owasp2021, $capturedCtx['owasp_2021']);
    }

    /* ============ details ============ */

    public function testDetailsFlashesAndRendersIndexWhenRepoFails(): void
    {
        $this->repo->expects($this->once())
            ->method('selectOwaspTop10Details')
            ->with(['menace' => 5])
            ->willReturn(['code' => 500, 'erreur' => 'db fail']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'alert'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/index.html.twig', $this->anything())
            ->willReturn('<html>err</html>');

        $this->controller->details(5);
    }

    public function testDetailsHappyPathRendersDetailWithMenace(): void
    {
        $this->repo->method('selectOwaspTop10Details')->willReturn([
            'code' => 200,
            'details' => [['menace' => 5, 'titre' => 'Injection']],
        ]);

        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with('owasp/detail.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>detail</html>');

        $this->controller->details(5);

        $this->assertSame(5, $capturedCtx['menace']);
        $this->assertSame('Injection', $capturedCtx['owasp']['titre']);
    }
}
