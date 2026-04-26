<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Activity;

use App\Controller\Activity\ApiActivityController;
use App\Entity\Activity;
use App\Entity\ActivityHistorique;
use App\Repository\ActivityHistoriqueRepository;
use App\Repository\ActivityRepository;
use App\Service\ClientService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiActivityControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */         private MockObject $em;
    /** @var ClientService&MockObject */                  private MockObject $client;
    /** @var MessageBusInterface&MockObject */            private MockObject $messageBus;
    /** @var Security&MockObject */                       private MockObject $security;
    /** @var LoggerInterface&MockObject */                private MockObject $logger;
    /** @var ActivityRepository&MockObject */             private MockObject $activityRepo;
    /** @var ActivityHistoriqueRepository&MockObject */   private MockObject $historiqueRepo;
    /** @var AuthorizationCheckerInterface&MockObject */  private MockObject $authChecker;
    /** @var ParameterBagInterface&MockObject */          private MockObject $params;

    private ApiActivityController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->messageBus = $this->createMock(MessageBusInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->activityRepo = $this->createMock(ActivityRepository::class);
        $this->historiqueRepo = $this->createMock(ActivityHistoriqueRepository::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturnMap([
            [Activity::class, $this->activityRepo],
            [ActivityHistorique::class, $this->historiqueRepo],
        ]);

        $this->params->method('get')->willReturn('https://sonar.example.com');
        $this->messageBus->method('dispatch')->willReturn(new Envelope(new \stdClass()));

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.authorization_checker', true],
            ['parameter_bag', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new ApiActivityController(
            $this->em,
            $this->client,
            $this->messageBus,
            $this->security,
            $this->logger
        );
        $this->controller->setContainer($container);
    }

    /* ============ sauvegardeHistorique ============ */

    public function testSauvegardeReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->sauvegardeHistorique();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testSauvegardeReturnsErrorWhenSonarCallFails(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->client->expects($this->once())
            ->method('httpActivity')
            ->willReturn(['code' => 503, 'erreur' => 'down']);

        $response = $this->controller->sauvegardeHistorique();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(503, $data['code']);
        $this->assertSame('alert', $data['type']);
    }

    public function testSauvegardeReturns204WhenTasksEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->client->expects($this->once())
            ->method('httpActivity')
            ->willReturn(['code' => 200, 'json' => ['tasks' => [], 'paging' => ['total' => 0]]]);

        $response = $this->controller->sauvegardeHistorique();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(204, $data['code']);
    }

    public function testSauvegardeHappyPathWithEmptyDatabaseInsertsActivity(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        // Tasks + paging (triggers last-page path). Use a recent submitted_at so lancerBatch loops 0-1 times.
        $recent = (new \DateTime('now'))->modify('-1 days')->format('Y-m-d\TH:i:sP');
        $this->client->method('httpActivity')->willReturn([
            'code' => 200,
            'json' => [
                'tasks' => [['submitted_at' => $recent, 'status' => 'SUCCESS']],
                'paging' => ['total' => 1],
            ],
        ]);

        // dateBase empty → triggers insertActivity path
        $this->activityRepo->method('dernierDate')->willReturn(['liste' => []]);
        $this->activityRepo->expects($this->once())
            ->method('insertActivity')
            ->willReturn(['code' => 200]);

        // other activity calls (premiereDate, nombreAnalyse, nombreStatus, tempsExecutionMax)
        $this->activityRepo->method('premiereDate')->willReturn([
            'liste' => [['date' => '2026-01-01']],
        ]);
        $this->activityRepo->method('nombreAnalyse')->willReturn(['liste' => ['nb_analyse' => 50]]);
        $this->activityRepo->method('nombreStatus')->willReturn(['liste' => ['nb_status' => 45]]);
        $this->activityRepo->method('tempsExecutionMax')->willReturn(['liste' => ['max_time' => 120]]);

        // historique: empty → insert
        $this->historiqueRepo->method('selectActivity')->willReturnOnConsecutiveCalls(
            ['request' => []],
            ['request' => [['date_enregistrement' => '2026-04-24 10:00:00']]],
        );
        $this->historiqueRepo->expects($this->once())->method('insertHistoriqueActivity');

        $response = $this->controller->sauvegardeHistorique();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    public function testSauvegardeHappyPathWithExistingDataUpdatesHistorique(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $recent = (new \DateTime('now'))->modify('-1 days')->format('Y-m-d\TH:i:sP');
        $this->client->method('httpActivity')->willReturn([
            'code' => 200,
            'json' => [
                'tasks' => [['submitted_at' => $recent, 'status' => 'SUCCESS']],
                'paging' => ['total' => 1],
            ],
        ]);

        // dateBase non-empty → while loop (but if date is yesterday, loop runs 0 or 1 times)
        $yesterday = (new \DateTime('now'))->modify('-1 days')->format('Y-m-d H:i:s');
        $this->activityRepo->method('dernierDate')->willReturn([
            'liste' => [['date' => $yesterday]],
        ]);

        $this->activityRepo->method('premiereDate')->willReturn([
            'liste' => [['date' => '2026-01-01']],
        ]);
        $this->activityRepo->method('nombreAnalyse')->willReturn(['liste' => ['nb_analyse' => 100]]);
        $this->activityRepo->method('nombreStatus')->willReturn(['liste' => ['nb_status' => 90]]);
        $this->activityRepo->method('tempsExecutionMax')->willReturn(['liste' => ['max_time' => 200]]);

        // historique: non-empty → update
        $this->historiqueRepo->method('selectActivity')->willReturnOnConsecutiveCalls(
            ['request' => [['year' => '2026']]],
            ['request' => [['date_enregistrement' => '2026-04-24 11:00:00']]],
        );
        $this->historiqueRepo->expects($this->once())->method('updateHistoriqueActivity');

        $response = $this->controller->sauvegardeHistorique();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    /* ============ apiDessin ============ */

    public function testDessinReturns400OnInvalidJson(): void
    {
        $response = $this->controller->apiDessin($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testDessinReturns400WhenSourceMissing(): void
    {
        $response = $this->controller->apiDessin($this->jsonRequest(['other' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testDessinAnalyseCallsListeAnalyseJour(): void
    {
        $this->activityRepo->expects($this->once())
            ->method('listeAnalyseJour')
            ->willReturn(['code' => 200, 'liste' => [['day' => 1]]]);

        $response = $this->controller->apiDessin($this->jsonRequest(['source' => 'analyse']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(200, $data['listeDonnee']['code']);
    }

    public function testDessinProjetCallsListeProjectAnalyse(): void
    {
        $this->activityRepo->expects($this->once())
            ->method('listeProjectAnalyse')
            ->willReturn(['code' => 200, 'liste' => []]);

        $response = $this->controller->apiDessin($this->jsonRequest(['source' => 'projet']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    public function testDessinProjetAnalyseCallsListeAnalyseProjet(): void
    {
        $this->activityRepo->expects($this->once())
            ->method('listeAnalyseProjet')
            ->willReturn(['code' => 200, 'liste' => []]);

        $response = $this->controller->apiDessin($this->jsonRequest(['source' => 'projet_analyse']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    public function testDessinReturns400OnUnknownSource(): void
    {
        $this->activityRepo->expects($this->never())->method('listeAnalyseJour');
        $this->activityRepo->expects($this->never())->method('listeProjectAnalyse');
        $this->activityRepo->expects($this->never())->method('listeAnalyseProjet');

        $response = $this->controller->apiDessin($this->jsonRequest(['source' => 'bogus']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
        $this->assertStringContainsString('bogus', $data['message']);
    }

    /* ============ helper ============ */

    private function jsonRequest(array|string $body): Request
    {
        $content = is_string($body) ? $body : json_encode($body, JSON_FORCE_OBJECT);
        return new Request([], [], [], [], [], [], $content);
    }
}
