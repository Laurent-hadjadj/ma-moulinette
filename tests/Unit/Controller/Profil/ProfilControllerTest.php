<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Profil;

use App\Controller\Profil\ProfilController;
use App\Entity\Profiles;
use App\Repository\ProfilesRepository;
use App\Service\UserAgentTrackingFacade;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class ProfilControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */   private MockObject $em;
    /** @var ParameterBagInterface&MockObject */    private MockObject $params;
    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;
    /** @var ProfilesRepository&MockObject */       private MockObject $repo;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var FlashBag&MockObject */                 private MockObject $flashBag;

    private ProfilController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->repo = $this->createMock(ProfilesRepository::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
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

        $this->controller = new ProfilController($this->em, $this->params, $this->logger, $this->tracking);
        $this->controller->setContainer($container);
    }

    public function testIndexFlashesErrorWhenRepoFails(): void
    {
        // selectProfiles retourne une erreur : un seul flash 'error' puis return early (refacto 2026)
        $this->repo->expects($this->once())
            ->method('selectProfiles')
            ->willReturn(['code' => 500, 'erreur' => 'db fail', 'liste' => []]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('profil/index.html.twig', $this->callback(fn($ctx) => $ctx['liste'] === []))
            ->willReturn('<html>err</html>');

        $this->controller->index();
    }

    public function testIndexFlashesWarningWhenListIsEmpty(): void
    {
        $this->repo->expects($this->once())
            ->method('selectProfiles')
            ->willReturn(['code' => 200, 'liste' => []]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>empty</html>');

        $this->controller->index();
    }

    public function testIndexHappyPathRendersWithList(): void
    {
        $liste = [['id' => 1, 'name' => 'Sonar way']];
        $this->repo->method('selectProfiles')->willReturn(['code' => 200, 'liste' => $liste]);

        $this->flashBag->expects($this->never())->method('add');

        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with('profil/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->tracking->expects($this->once())->method('track')->with('PROFIL');

        $this->controller->index();

        $this->assertSame($liste, $capturedCtx['liste']);
    }

    public function testIndexFlashesAndRendersEmptyListOnException(): void
    {
        $this->repo->expects($this->once())
            ->method('selectProfiles')
            ->willThrowException(new \RuntimeException('boom'));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'critical'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('profil/index.html.twig', $this->callback(fn($ctx) => $ctx['liste'] === []))
            ->willReturn('<html>exc</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>exc</html>', $response->getContent());
    }
}
