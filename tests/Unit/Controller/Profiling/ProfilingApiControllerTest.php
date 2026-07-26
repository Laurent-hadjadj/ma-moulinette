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

namespace App\Tests\Unit\Controller\Profiling;

use App\Controller\Profiling\ProfilingApiController;
use App\Repository\BatchProfilingRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class ProfilingApiControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */         private MockObject $em;
    /** @var LoggerInterface&MockObject */                private MockObject $logger;
    /** @var BatchProfilingRepository&MockObject */       private MockObject $repo;
    /** @var AuthorizationCheckerInterface&MockObject */  private MockObject $authChecker;

    private ProfilingApiController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(BatchProfilingRepository::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->em->method('getRepository')->willReturn($this->repo);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'security.authorization_checker'
        );
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
        ]);

        $this->controller = new ProfilingApiController($this->em, $this->logger);
        $this->controller->setContainer($container);
    }

    /* ============ indicateur ============ */

    public function testIndicateurReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->indicateur($this->jsonRequest(['indicateur' => 'utilisateur']));
        $data = json_decode($response->getContent(), true);

        // Refacto sémantique : 404 → 403 (Forbidden plus correct pour absence de rôle)
        $this->assertSame(403, $data['code']);
    }

    public function testIndicateurReturns400OnInvalidJson(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->indicateur($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testIndicateurReturns400WhenIndicateurKeyMissing(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->indicateur($this->jsonRequest(['other' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testIndicateurReturns400OnUnauthorizedValue(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->indicateur($this->jsonRequest(['indicateur' => 'bogus']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testIndicateurHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->expects($this->once())
            ->method('findGlobalSummary')
            ->with('utilisateur')
            ->willReturn(['nb' => 5]);

        $response = $this->controller->indicateur($this->jsonRequest(['indicateur' => 'utilisateur']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(['nb' => 5], $data['indicateur']);
    }

    /* ============ summary ============ */

    public function testSummaryWithoutRoleReturnsEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->summary();
        $data = json_decode($response->getContent(), true);

        $this->assertSame([], $data['summary']);
    }

    public function testSummaryHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->expects($this->once())
            ->method('getGlobalKpi')
            ->willReturn(['summary' => [['projets' => 10]]]);

        $response = $this->controller->summary();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(['projets' => 10], $data['summary']);
    }

    /* ============ latest ============ */

    public function testLatestWithoutRoleReturnsEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->latest();
        $data = json_decode($response->getContent(), true);

        $this->assertSame([], $data['latest']);
    }

    public function testLatestHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->expects($this->once())
            ->method('findLatest')
            ->with(10)
            ->willReturn([['id' => 1]]);

        $response = $this->controller->latest();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertCount(1, $data['latest']);
    }

    /* ============ weekly / monthlyAll / users / allPortefeuille (formatChartData) ============ */

    public function testWeeklyHappyPathFormatsChartData(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->expects($this->once())
            ->method('findWeeklyStats')
            ->willReturn([
                ['semaine' => 'S14', 'portefeuille' => 'P1', 'average_time' => 1.234, 'average_memory' => 10.5],
                ['semaine' => 'S15', 'portefeuille' => 'P1', 'average_time' => 2.5, 'average_memory' => 11.0],
                ['semaine' => 'S14', 'portefeuille' => 'P2', 'average_time' => 3.0, 'average_memory' => 12.0],
            ]);

        $response = $this->controller->weekly();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(['S14', 'S15'], $data['weekly']['labels']);
        $this->assertCount(2, $data['weekly']['datasetsTime']);
        // 1er dataset pour P1 : time pour S14 = 1.23, pour S15 = 2.5
        $this->assertSame('P1', $data['weekly']['datasetsTime'][0]['label']);
        $this->assertSame(1.23, $data['weekly']['datasetsTime'][0]['data'][0]);
        $this->assertSame(2.5, $data['weekly']['datasetsTime'][0]['data'][1]);
        // P2 : pas de S15 → null
        $this->assertSame('P2', $data['weekly']['datasetsTime'][1]['label']);
        $this->assertEquals(3.0, $data['weekly']['datasetsTime'][1]['data'][0]);
        $this->assertNull($data['weekly']['datasetsTime'][1]['data'][1]);
    }

    public function testMonthlyAllWithoutRoleReturnsEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->monthlyAll();
        $data = json_decode($response->getContent(), true);

        $this->assertSame([], $data['monthly']);
    }

    public function testMonthlyAllHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->expects($this->once())
            ->method('findMonthlyStats')
            ->willReturn([
                ['mois' => '2026-03', 'portefeuille' => 'P1', 'average_time' => 5, 'average_memory' => 20],
            ]);

        $response = $this->controller->monthlyAll();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(['2026-03'], $data['monthly']['labels']);
    }

    public function testUsersWithoutRoleReturnsEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->users();
        $data = json_decode($response->getContent(), true);

        $this->assertSame([], $data['user']);
    }

    public function testUsersHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->expects($this->once())
            ->method('findUsersStats')
            ->willReturn([
                ['utilisateur' => 'alice@x', 'average_time' => 1, 'average_memory' => 10],
            ]);

        $response = $this->controller->users();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(['alice@x'], $data['user']['labels']);
    }

    public function testAllPortefeuilleHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->repo->expects($this->once())
            ->method('findStatsByPortefeuille')
            ->willReturn([
                ['portefeuille' => 'P1', 'average_time' => 2, 'average_memory' => 15],
            ]);

        $response = $this->controller->allPortefeuille();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(['P1'], $data['portefeuille']['labels']);
    }

    /* ============ helper ============ */

    /**
     * @param array<string, string>|string $body
     */
    private function jsonRequest(array|string $body): Request
    {
        $content = is_string($body) ? $body : json_encode($body, JSON_FORCE_OBJECT);
        return new Request([], [], [], [], [], [], $content);
    }
}
