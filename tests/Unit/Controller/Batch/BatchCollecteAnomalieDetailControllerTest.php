<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteAnomalieDetailController;
use App\Entity\AnomalieDetails;
use App\Repository\AnomalieDetailsRepository;
use App\Service\ClientService;
use App\Service\ExtractName;
use App\Service\UrlBuilderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteAnomalieDetailControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';

    /** @var EntityManagerInterface&MockObject */       private MockObject $em;
    /** @var ClientService&MockObject */                 private MockObject $client;
    /** @var ExtractName&MockObject */                   private MockObject $extractName;
    /** @var UrlBuilderService&MockObject */             private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */               private MockObject $logger;
    /** @var AnomalieDetailsRepository&MockObject */     private MockObject $repo;
    /** @var ParameterBagInterface&MockObject */         private MockObject $parameterBag;

    private BatchCollecteAnomalieDetailController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->extractName = $this->createMock(ExtractName::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(AnomalieDetailsRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(AnomalieDetails::class)
            ->willReturn($this->repo);

        $this->urlBuilder->method('build')->willReturn('https://sonar/api/issues/search?...');
        $this->parameterBag->method('get')->willReturn('https://sonar.example.com');
        $this->extractName->method('extractNameFromMavenKey')->willReturn('app');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteAnomalieDetailController(
            $this->em, $this->client, $this->extractName, $this->urlBuilder, $this->logger
        );
        $this->controller->setContainer($container);
    }

    public function testReturnsErrorWhenFirstSonarCallFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 500, 'erreur' => 'down']);

        $this->repo->expects($this->never())->method('deleteAnomalieDetailsMavenKey');

        $result = $this->controller->BatchCollecteAnomalieDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
        $this->assertSame('BUG', $result['type']);
    }

    public function testReturnsZeroHistoriqueWhenAllTypesHaveNoIssues(): void
    {
        $this->client->expects($this->exactly(3))
            ->method('httpSonarQube')
            ->willReturn([
                'code' => 200,
                'json' => ['paging' => ['total' => 0], 'facets' => [['values' => []]]],
            ]);

        $this->repo->expects($this->never())->method('deleteAnomalieDetailsMavenKey');
        $this->repo->expects($this->never())->method('insertAnomalieDetail');

        $result = $this->controller->BatchCollecteAnomalieDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['historique']['bug_blocker']);
        $this->assertSame(0, $result['historique']['code_smell_info']);
    }

    public function testReturnsErrorWhenDeleteFails(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'paging' => ['total' => 5],
                'facets' => [['values' => [['val' => 'BLOCKER', 'count' => 1]]]],
            ],
        ]);

        $this->repo->expects($this->once())
            ->method('deleteAnomalieDetailsMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $result = $this->controller->BatchCollecteAnomalieDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testAggregatesSeveritiesAcrossAllThreeTypes(): void
    {
        $this->client->expects($this->exactly(3))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                // BUG : 2 blocker, 1 critical
                ['code' => 200, 'json' => [
                    'paging' => ['total' => 3],
                    'facets' => [['values' => [
                        ['val' => 'BLOCKER', 'count' => 2],
                        ['val' => 'CRITICAL', 'count' => 1],
                    ]]],
                ]],
                // VULNERABILITY : 1 major
                ['code' => 200, 'json' => [
                    'paging' => ['total' => 1],
                    'facets' => [['values' => [['val' => 'MAJOR', 'count' => 1]]]],
                ]],
                // CODE_SMELL : 5 minor, 2 info
                ['code' => 200, 'json' => [
                    'paging' => ['total' => 7],
                    'facets' => [['values' => [
                        ['val' => 'MINOR', 'count' => 5],
                        ['val' => 'INFO', 'count' => 2],
                    ]]],
                ]],
            );

        $this->repo->method('deleteAnomalieDetailsMavenKey')->willReturn(['code' => 200]);

        $capturedInsert = null;
        $this->repo->expects($this->once())
            ->method('insertAnomalieDetail')
            ->with($this->callback(function (array $data) use (&$capturedInsert) {
                $capturedInsert = $data;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalieDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(2, $result['historique']['bug_blocker']);
        $this->assertSame(1, $result['historique']['bug_critical']);
        $this->assertSame(1, $result['historique']['vulnerability_major']);
        $this->assertSame(5, $result['historique']['code_smell_minor']);
        $this->assertSame(2, $result['historique']['code_smell_info']);

        // Map persistée dans insertAnomalieDetail
        $this->assertSame(self::MAVEN_KEY, $capturedInsert['maven_key']);
        $this->assertSame('app', $capturedInsert['name']);
        $this->assertSame(2, $capturedInsert['bug_blocker']);
    }

    public function testReturnsErrorWhenInsertFails(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => [
                'paging' => ['total' => 1],
                'facets' => [['values' => [['val' => 'BLOCKER', 'count' => 1]]]],
            ],
        ]);
        $this->repo->method('deleteAnomalieDetailsMavenKey')->willReturn(['code' => 200]);
        $this->repo->expects($this->once())
            ->method('insertAnomalieDetail')
            ->willReturn(['code' => 500, 'erreur' => 'insert failed']);

        $result = $this->controller->BatchCollecteAnomalieDetail(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }
}
