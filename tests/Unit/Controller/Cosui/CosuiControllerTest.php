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

namespace App\Tests\Unit\Controller\Cosui;

use App\Controller\Cosui\CosuiController;
use App\Service\ProjetCosuiService;
use App\Service\UserAgent\UserAgentTrackingFacade;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
/* MODIF 2026-05-05 : retrait des mocks
 * pointant vers UserAgentTrackingFacade / UserAgentReportingService /
 * LogArchiveService (classes supprimées de src/). */
class CosuiControllerTest extends TestCase
{
    // Valid token: encoding "1234567890|fr.ma-moulinette:ma-moulinette" with ROT13+base64
    private string $validToken;

    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var ProjetCosuiService&MockObject */       private MockObject $cosuiService;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var FlashBag&MockObject */                 private MockObject $flashBag;

    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;

    private CosuiController $controller;

    protected function setUp(): void
    {
        $this->validToken = str_rot13(base64_encode('1234567890|fr.ma-moulinette:ma-moulinette'));

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cosuiService = $this->createMock(ProjetCosuiService::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['twig', 'request_stack'], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['request_stack', 1, $requestStack],
        ]);

        $this->controller = new CosuiController($this->logger, $this->cosuiService, $this->tracking);
        $this->controller->setContainer($container);
    }

    public function testProjetCosuiFlashesErrorWhenTokenEmpty(): void
    {
        $this->cosuiService->expects($this->once())
            ->method('initialRender')
            ->willReturn(['maven_key' => 'NC']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('projet/cosui.html.twig', $this->anything())
            ->willReturn('<html>no-token</html>');

        $response = $this->controller->projetCosui(new Request());
        $this->assertSame('<html>no-token</html>', $response->getContent());
    }

    public function testProjetCosuiFlashesErrorOnInvalidToken(): void
    {
        $this->cosuiService->method('initialRender')->willReturn([]);

        // "only-one-part" (no |) → decode returns null
        $badToken = str_rot13(base64_encode('only-one-part'));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>bad</html>');

        $this->controller->projetCosui(new Request(['token' => $badToken]));
    }

    public function testProjetCosuiFlashesWhenGenerateRenderReturnsError(): void
    {
        $this->cosuiService->method('initialRender')->willReturn([]);
        $this->cosuiService->expects($this->once())
            ->method('generateRender')
            ->with('fr.ma-moulinette:ma-moulinette')
            ->willReturn([
                'code' => 404,
                'type' => 'warning',
                'message' => 'not found',
                'trace' => 'missing',
            ]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>404</html>');

        $this->controller->projetCosui(new Request(['token' => $this->validToken]));
    }

    public function testProjetCosuiFlashesCriticalOnException(): void
    {
        $this->cosuiService->method('initialRender')->willReturn([]);
        $this->cosuiService->method('generateRender')
            ->willThrowException(new \RuntimeException('boom'));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'critical' && $v['trace'] === 'boom'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>exc</html>');

        $this->controller->projetCosui(new Request(['token' => $this->validToken]));
    }

    public function testProjetCosuiHappyPath(): void
    {
        $this->cosuiService->method('initialRender')->willReturn([]);
        $this->cosuiService->method('generateRender')->willReturn([
            'code' => 200,
            'data' => ['something'],
            'projet' => 'fr.ma-moulinette:ma-moulinette',
        ]);

        $this->flashBag->expects($this->never())->method('add');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('projet/cosui.html.twig', $this->callback(fn($ctx) => $ctx['code'] === 200))
            ->willReturn('<html>ok</html>');

        $this->controller->projetCosui(new Request(['token' => $this->validToken]));
    }
}
