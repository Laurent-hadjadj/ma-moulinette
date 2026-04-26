<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchManuelController;
use App\Controller\Batch\CollecteController;
use App\Entity\BatchTraitement;
use App\Entity\Utilisateur;
use App\Repository\BatchTraitementRepository;
use App\Service\ListeProjetPortefeuilleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchManuelControllerTest extends TestCase
{
    /** @var CollecteController&MockObject */             private MockObject $collecte;
    /** @var EntityManagerInterface&MockObject */         private MockObject $em;
    /** @var LoggerInterface&MockObject */                private MockObject $logger;
    /** @var LoggerInterface&MockObject */                private MockObject $profiler;
    /** @var Security&MockObject */                       private MockObject $security;
    /** @var ListeProjetPortefeuilleService&MockObject */ private MockObject $listeProjetService;
    /** @var BatchTraitementRepository&MockObject */      private MockObject $batchTraitementRepo;
    /** @var AuthorizationCheckerInterface&MockObject */  private MockObject $authChecker;
    /** @var ParameterBagInterface&MockObject */          private MockObject $params;

    private BatchManuelController $controller;

    protected function setUp(): void
    {
        $this->collecte = $this->createMock(CollecteController::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->profiler = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->listeProjetService = $this->createMock(ListeProjetPortefeuilleService::class);
        $this->batchTraitementRepo = $this->createMock(BatchTraitementRepository::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturn($this->batchTraitementRepo);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['security.authorization_checker', true],
            ['parameter_bag', true],
        ]);
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new BatchManuelController(
            $this->collecte,
            $this->em,
            $this->logger,
            $this->profiler,
            $this->security,
            $this->listeProjetService
        );
        $this->controller->setContainer($container);
    }

    /* ============ getPendingOrProgress ============ */

    public function testGetPendingReturnsErrorWhenRepoFails(): void
    {
        $this->batchTraitementRepo->expects($this->once())
            ->method('countBatchTraitementPendingAndProgress')
            ->willReturn(['code' => 500, 'erreur' => 'boom']);

        $response = $this->controller->getPendingOrProgress();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('critical', $data['type']);
    }

    public function testGetPendingReturnsCountOnHappyPath(): void
    {
        $this->batchTraitementRepo->expects($this->once())
            ->method('countBatchTraitementPendingAndProgress')
            ->willReturn(['code' => 200, 'pending' => 3, 'progress' => 1]);

        $response = $this->controller->getPendingOrProgress();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(3, $data['pending']);
        $this->assertSame(1, $data['in_progress']);
    }

    /* ============ addPending ============ */

    public function testAddPendingReturns400OnInvalidJson(): void
    {
        $response = $this->controller->addPending($this->jsonRequest('not-json'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testAddPendingReturns400WhenTraitementIdMissing(): void
    {
        $response = $this->controller->addPending($this->jsonRequest(['other' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testAddPendingReturnsErrorWhenUpdateFails(): void
    {
        $this->batchTraitementRepo->expects($this->once())
            ->method('updateBatchTraitementPending')
            ->willReturn(['code' => 500, 'erreur' => 'update fail']);

        $response = $this->controller->addPending($this->jsonRequest(['traitement_id' => 'abc']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('update fail', $data['trace']);
    }

    public function testAddPendingHappyPath(): void
    {
        $this->batchTraitementRepo->expects($this->once())
            ->method('updateBatchTraitementPending')
            ->willReturn(['code' => 200]);

        $response = $this->controller->addPending($this->jsonRequest(['traitement_id' => 'abc']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    /* ============ traitementManuel ============ */

    public function testTraitementManuelReturns400OnInvalidJson(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->traitementManuel($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testTraitementManuelReturns400WhenFieldsMissing(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->traitementManuel($this->jsonRequest([
            'traitement_id' => 'abc',
            // missing titre_portefeuille, portefeuille
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testTraitementManuelQueuesWhenAlreadyInProgress(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        // An existing in-progress traitement exists
        $this->batchTraitementRepo->expects($this->once())
            ->method('findBy')
            ->with(['inProgress' => true])
            ->willReturn([new BatchTraitement('t', 'p', 'r', 'rs')]);

        $this->batchTraitementRepo->expects($this->once())
            ->method('updateBatchTraitementPending')
            ->willReturn(['code' => 200]);

        $response = $this->controller->traitementManuel($this->jsonRequest([
            'traitement_id' => '01HK0000000000000000000000',
            'titre_portefeuille' => 'T',
            'portefeuille' => 'P',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(202, $data['code']);
        $this->assertSame('info', $data['type']);
    }

    public function testTraitementManuelReturns404WhenProjectListEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->method('findBy')->willReturn([]); // no one in progress
        $this->listeProjetService->expects($this->once())
            ->method('listeProjet')
            ->willReturn([
                'code' => 404,
                'type' => 'warning',
                'message' => 'empty',
                'erreur' => 'nothing',
            ]);

        $response = $this->controller->traitementManuel($this->jsonRequest([
            'traitement_id' => '01HK0000000000000000000000',
            'titre_portefeuille' => 'T',
            'portefeuille' => 'P',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $data['code']);
    }

    public function testTraitementManuelReturnsErrorWhenUpdateFailsBeforeLoop(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->method('findBy')->willReturn([]);
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['com.acme:app'],
        ]);

        $user = new Utilisateur();
        $user->setCourriel('u@example.com');
        $this->security->method('getUser')->willReturn($user);

        $this->batchTraitementRepo->expects($this->once())
            ->method('updateBatchTraitement')
            ->willReturn(['code' => 500, 'erreur' => 'update fail']);

        $this->collecte->expects($this->never())->method('collecte');

        $response = $this->controller->traitementManuel($this->jsonRequest([
            'traitement_id' => '01HK0000000000000000000000',
            'titre_portefeuille' => 'T',
            'portefeuille' => 'P',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testTraitementManuelAbortsLoopWhenCollecteReturns500(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->method('findBy')->willReturn([]);
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['com.acme:app', 'com.acme:other'],
        ]);

        $user = new Utilisateur();
        $user->setCourriel('u@example.com');
        $this->security->method('getUser')->willReturn($user);

        $this->batchTraitementRepo->method('updateBatchTraitement')->willReturn(['code' => 200]);

        $this->collecte->expects($this->once())
            ->method('collecte')
            ->willReturn(['code' => 500, 'compte_rendu' => 'boom']);

        $response = $this->controller->traitementManuel($this->jsonRequest([
            'traitement_id' => '01HK0000000000000000000000',
            'titre_portefeuille' => 'T',
            'portefeuille' => 'P',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('warning', $data['type']);
        $this->assertStringContainsString('com.acme:app', $data['message']);
    }

    public function testTraitementManuelHappyPathReturnsReferenceAfterFullLoop(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->method('findBy')->willReturn([]);
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['com.acme:app'],
        ]);

        $user = new Utilisateur();
        $user->setCourriel('u@example.com');
        $this->security->method('getUser')->willReturn($user);

        // Both updateBatchTraitement calls succeed (before loop + after loop)
        $this->batchTraitementRepo->method('updateBatchTraitement')->willReturn(['code' => 200]);

        $this->collecte->expects($this->once())
            ->method('collecte')
            ->willReturn(['code' => 200, 'compte_rendu' => 'ok']);

        $response = $this->controller->traitementManuel($this->jsonRequest([
            'traitement_id' => '01HK0000000000000000000000',
            'titre_portefeuille' => 'T',
            'portefeuille' => 'P',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertStringContainsString('succès', $data['message']);
        $this->assertArrayHasKey('reference', $data);
    }

    public function testTraitementManuelReturnsErrorWhenFinalUpdateFails(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchTraitementRepo->method('findBy')->willReturn([]);
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['com.acme:app'],
        ]);

        $user = new Utilisateur();
        $user->setCourriel('u@example.com');
        $this->security->method('getUser')->willReturn($user);

        // First updateBatchTraitement (before loop) succeeds, second (after loop) fails
        $this->batchTraitementRepo->method('updateBatchTraitement')->willReturnOnConsecutiveCalls(
            ['code' => 200],
            ['code' => 500, 'erreur' => 'final update fail'],
        );

        $this->collecte->method('collecte')->willReturn(['code' => 200, 'compte_rendu' => 'ok']);

        $response = $this->controller->traitementManuel($this->jsonRequest([
            'traitement_id' => '01HK0000000000000000000000',
            'titre_portefeuille' => 'T',
            'portefeuille' => 'P',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    /* ============ helper ============ */

    private function jsonRequest(array|string $body): Request
    {
        $content = is_string($body) ? $body : json_encode($body, JSON_FORCE_OBJECT);
        return new Request([], [], [], [], [], [], $content);
    }
}
