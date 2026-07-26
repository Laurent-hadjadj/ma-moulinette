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

namespace App\Tests\Unit\Controller\Repartition;

use App\Controller\Batch\BatchCollecteRepartitionController;
use App\Controller\Repartition\ApiRepartitionController;
use App\Entity\{Repartition, RepartitionTemp};
use App\Repository\{RepartitionRepository, RepartitionTempRepository};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiRepartitionControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */              private MockObject $em;
    /** @var BatchCollecteRepartitionController&MockObject */  private MockObject $batchCollecte;
    /** @var LoggerInterface&MockObject */                     private MockObject $logger;
    /** @var Security&MockObject */                            private MockObject $security;
    /** @var RepartitionTempRepository&MockObject */           private MockObject $tempRepo;
    /** @var RepartitionRepository&MockObject */               private MockObject $repo;
    /** @var AuthorizationCheckerInterface&MockObject */       private MockObject $authChecker;

    private ApiRepartitionController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->batchCollecte = $this->createMock(BatchCollecteRepartitionController::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->tempRepo = $this->createMock(RepartitionTempRepository::class);
        $this->repo = $this->createMock(RepartitionRepository::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->em->method('getRepository')->willReturnMap([
            [RepartitionTemp::class, $this->tempRepo],
            [Repartition::class, $this->repo],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'security.authorization_checker'
        );
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
        ]);

        $this->controller = new ApiRepartitionController(
            $this->em,
            $this->batchCollecte,
            $this->logger,
            $this->security
        );
        $this->controller->setContainer($container);
    }

    /* ============ apiRepartitionCollecte ============ */

    public function testCollecteReturns400OnMissingFields(): void
    {
        $response = $this->controller->apiRepartitionCollecte($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testCollecteReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->apiRepartitionCollecte($this->jsonRequest([
            'maven_key' => 'k', 'category' => 'BUG', 'severity' => 'BLOCKER', 'setup' => 123,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testCollecteReturnsErrorOnBatchFailure(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchCollecte->expects($this->once())
            ->method('batchCollecteRepartition')
            ->willReturn(['code' => 500, 'erreur' => 'fail']);

        $response = $this->controller->apiRepartitionCollecte($this->jsonRequest([
            'maven_key' => 'k', 'category' => 'BUG', 'severity' => 'BLOCKER', 'setup' => 123,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testCollecteHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchCollecte->expects($this->once())
            ->method('batchCollecteRepartition')
            ->willReturn(['code' => 200, 'data' => ['total' => 42, 'temps' => 1.5]]);

        $response = $this->controller->apiRepartitionCollecte($this->jsonRequest([
            'maven_key' => 'k', 'category' => 'BUG', 'severity' => 'BLOCKER', 'setup' => 123,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(42, $data['total']);
        $this->assertEquals(1.5, $data['temps']);
    }

    /* ============ apiRepartitionAnalyse ============ */

    public function testAnalyseReturns400OnMissingFields(): void
    {
        $response = $this->controller->apiRepartitionAnalyse($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testAnalyseReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->apiRepartitionAnalyse($this->jsonRequest([
            'maven_key' => 'k', 'category' => 'BUG', 'severity' => 'BLOCKER', 'setup' => 123,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testAnalyseReturns404WhenSetupDoesNotExist(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->tempRepo->expects($this->once())
            ->method('findOneBy')
            ->with(['setup' => 123])
            ->willReturn(null);

        $response = $this->controller->apiRepartitionAnalyse($this->jsonRequest([
            'maven_key' => 'k', 'category' => 'BUG', 'severity' => 'BLOCKER', 'setup' => 123,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $data['code']);
    }

    public function testAnalyseReturns202OnCheckCategory(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->tempRepo->method('findOneBy')->willReturn(new RepartitionTemp());

        $this->batchCollecte->expects($this->never())->method('batchCollecteRepartitionAnalyse');

        $response = $this->controller->apiRepartitionAnalyse($this->jsonRequest([
            'maven_key' => 'k', 'category' => 'CHECK', 'severity' => 'ANY', 'setup' => 123,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(202, $data['code']);
        $this->assertSame('CHECK', $data['category']);
    }

    public function testAnalyseReturnsErrorOnBatchFailure(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->tempRepo->method('findOneBy')->willReturn(new RepartitionTemp());
        $this->batchCollecte->expects($this->once())
            ->method('batchCollecteRepartitionAnalyse')
            ->willReturn(['code' => 500, 'erreur' => 'fail']);

        $response = $this->controller->apiRepartitionAnalyse($this->jsonRequest([
            'maven_key' => 'k', 'category' => 'BUG', 'severity' => 'BLOCKER', 'setup' => 123,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testAnalyseHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->tempRepo->method('findOneBy')->willReturn(new RepartitionTemp());
        $this->batchCollecte->method('batchCollecteRepartitionAnalyse')->willReturn([
            'code' => 200,
            'frontend' => 10, 'backend' => 5, 'autre' => 2, 'inconnu' => 1, 'total' => 18,
        ]);

        $response = $this->controller->apiRepartitionAnalyse($this->jsonRequest([
            'maven_key' => 'k', 'category' => 'BUG', 'severity' => 'BLOCKER', 'setup' => 123,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(10, $data['frontend']);
        $this->assertSame('Manuel', $data['mode']);
    }

    public function testAnalyseReturns500OnException(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->tempRepo->method('findOneBy')->willReturn(new RepartitionTemp());
        $this->batchCollecte->method('batchCollecteRepartitionAnalyse')
            ->willThrowException(new \RuntimeException('boom'));

        $response = $this->controller->apiRepartitionAnalyse($this->jsonRequest([
            'maven_key' => 'k', 'category' => 'BUG', 'severity' => 'BLOCKER', 'setup' => 123,
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('boom', $data['trace']);
    }

    /* ============ apiRepartitionHistorique ============ */

    public function testHistoriqueReturns400WhenMavenKeyMissing(): void
    {
        $response = $this->controller->apiRepartitionHistorique($this->jsonRequest([]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testHistoriqueReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->apiRepartitionHistorique($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testHistoriqueReturnsErrorWhenRepoFails(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->expects($this->once())
            ->method('findLatestMavenKeyWithControl')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $response = $this->controller->apiRepartitionHistorique($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testHistoriqueReturns404WhenResultEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->method('findLatestMavenKeyWithControl')->willReturn([
            'code' => 200, 'result' => [],
        ]);

        $response = $this->controller->apiRepartitionHistorique($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $data['code']);
    }

    public function testHistoriqueHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->method('findLatestMavenKeyWithControl')->willReturn([
            'code' => 200,
            'result' => [[
                'setup' => 123,
                'date_enregistrement' => '2026-04-10 10:00:00',
                'maven_key' => 'k',
            ]],
        ]);

        $response = $this->controller->apiRepartitionHistorique($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(201, $data['code']);
        $this->assertSame('Historique', $data['mode']);
        $this->assertSame(123, $data['data']['setup']);
    }

    /* ============ apiRepartitionAnalyseMaj ============ */

    public function testMajReturns400OnMissingFields(): void
    {
        $response = $this->controller->apiRepartitionAnalyseMaj($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testMajReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->apiRepartitionAnalyseMaj($this->jsonRequest([
            'maven_key' => 'k', 'setup' => 123, 'calcul' => ['BUG', 'BLOCKER', 1, 2, 3, 4],
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testMajReturnsErrorOnBatchFailure(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchCollecte->expects($this->once())
            ->method('batchCollecteRepartitionMaJ')
            ->willReturn(['code' => 500, 'erreur' => 'maj fail']);

        $response = $this->controller->apiRepartitionAnalyseMaj($this->jsonRequest([
            'maven_key' => 'k', 'setup' => 123, 'calcul' => ['BUG'],
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testMajHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->batchCollecte->method('batchCollecteRepartitionMaJ')->willReturn([
            'code' => 200, 'message' => 'ok',
        ]);

        $response = $this->controller->apiRepartitionAnalyseMaj($this->jsonRequest([
            'maven_key' => 'k', 'setup' => 123, 'calcul' => ['BUG'],
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame('ok', $data['message']);
    }

    /* ============ helper ============ */

    /**
     * @param array<int|string, mixed>|string $body
     */
    private function jsonRequest(array|string $body): Request
    {
        if (is_string($body)) {
            $content = $body;
        } elseif ($body === []) {
            $content = '{}';
        } else {
            $content = json_encode($body);
        }
        return new Request([], [], [], [], [], [], $content);
    }
}
