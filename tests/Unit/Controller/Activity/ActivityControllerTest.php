<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Activity;

use App\Controller\Activity\ActivityController;
use App\Entity\Activity;
use App\Entity\ActivityHistorique;
use App\Repository\ActivityHistoriqueRepository;
use App\Repository\ActivityRepository;
use App\Service\ClientService;
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
class ActivityControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */         private MockObject $em;
    /** @var ClientService&MockObject */                  private MockObject $client;
    /** @var ParameterBagInterface&MockObject */          private MockObject $params;
    /** @var UserAgentTrackingFacade&MockObject */        private MockObject $tracking;
    /** @var ActivityRepository&MockObject */             private MockObject $activityRepo;
    /** @var ActivityHistoriqueRepository&MockObject */   private MockObject $historiqueRepo;
    /** @var Environment&MockObject */                    private MockObject $twig;
    /** @var FlashBag&MockObject */                       private MockObject $flashBag;

    private ActivityController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->activityRepo = $this->createMock(ActivityRepository::class);
        $this->historiqueRepo = $this->createMock(ActivityHistoriqueRepository::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
            ['sonar.url', 'https://sonar.example.com'],
        ]);

        $this->em->method('getRepository')->willReturnMap([
            [Activity::class, $this->activityRepo],
            [ActivityHistorique::class, $this->historiqueRepo],
        ]);

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

        $this->controller = new ActivityController(
            $this->em,
            $this->params,
            $this->client,
            $this->tracking
        );
        $this->controller->setContainer($container);
    }

    public function testIndexRendersWithFlashWhenSonarFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpActivity')
            ->willReturn(['code' => 503, 'erreur' => 'down']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'alert'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('activity/index.html.twig', $this->anything())
            ->willReturn('<html>err</html>');

        $this->activityRepo->expects($this->never())->method('selectActivity');

        $response = $this->controller->index();

        $this->assertSame('<html>err</html>', $response->getContent());
    }

    public function testIndexRendersWithWarningWhenAnalysesEmpty(): void
    {
        $this->client->method('httpActivity')->willReturn([
            'code' => 200,
            'json' => ['tasks' => [['executedAt' => '2026-04-10 10:00:00']]],
        ]);
        $this->activityRepo->expects($this->once())
            ->method('selectActivity')
            ->willReturn(['code' => 200, 'liste' => []]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning' && str_contains($v['message'], 'vide')));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>empty</html>');

        $this->controller->index();
    }

    public function testIndexRendersWithAlertWhenHistoriqueEmpty(): void
    {
        $this->client->method('httpActivity')->willReturn([
            'code' => 200,
            'json' => ['tasks' => [['executedAt' => '2026-04-10 10:00:00']]],
        ]);
        $this->activityRepo->method('selectActivity')->willReturn([
            'code' => 200,
            'liste' => [['foo' => 'bar']],
        ]);
        $this->activityRepo->method('dernierDate')->willReturn([
            'code' => 200,
            'liste' => ['date' => '2026-04-10 10:00:00'],
        ]);
        $this->historiqueRepo->expects($this->once())
            ->method('selectActivity')
            ->willReturn(['liste' => []]);

        $this->flashBag->expects($this->atLeastOnce())
            ->method('add');

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>no historique</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>no historique</html>', $response->getContent());
    }

    public function testIndexFlashesUpdateNoticeWhenSonarHasNewerTasks(): void
    {
        // dateSonar > dateBase → flash "mise à jour"
        $this->client->method('httpActivity')->willReturn([
            'code' => 200,
            'json' => ['tasks' => [['executedAt' => '2026-04-20 10:00:00']]],
        ]);
        $this->activityRepo->method('selectActivity')->willReturn([
            'code' => 200,
            'liste' => [['foo' => 'bar']],
        ]);
        $this->activityRepo->method('dernierDate')->willReturn([
            'code' => 200,
            'liste' => ['date' => '2026-04-10 10:00:00'],
        ]);
        $this->historiqueRepo->method('selectActivity')->willReturn([
            'liste' => [[
                'max_time' => '2026-04-10 10:00:00',
                'date_enregistrement' => '2026-04-10 10:00:00',
            ]],
        ]);

        $capturedFlashes = [];
        $this->flashBag->method('add')->willReturnCallback(
            function ($type, $value) use (&$capturedFlashes): void {
                $capturedFlashes[] = $value['message'];
            }
        );

        $this->twig->expects($this->once())->method('render')->willReturn('<html>ok</html>');

        $this->controller->index();

        $joined = implode(' | ', $capturedFlashes);
        $this->assertStringContainsString('mettre à jour', $joined);
    }
}
