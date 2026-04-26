<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteNoSonarController;
use App\Entity\NoSonar;
use App\Repository\NoSonarRepository;
use App\Service\ClientService;
use App\Service\UrlBuilderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteNoSonarControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';

    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var ClientService&MockObject */           private MockObject $client;
    /** @var UrlBuilderService&MockObject */       private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */         private MockObject $logger;
    /** @var NoSonarRepository&MockObject */       private MockObject $repo;
    /** @var ParameterBagInterface&MockObject */   private MockObject $parameterBag;

    private BatchCollecteNoSonarController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(NoSonarRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(NoSonar::class)
            ->willReturn($this->repo);

        $this->urlBuilder->method('build')->willReturn('https://sonar.example.com/api/issues/search?...');
        $this->parameterBag->method('get')->willReturn('https://sonar.example.com');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteNoSonarController(
            $this->em, $this->client, $this->urlBuilder, $this->logger
        );
        $this->controller->setContainer($container);
    }

    public function testReturnsErrorWhenSonarHttpCallFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 500, 'erreur' => 'down']);

        $this->repo->expects($this->never())->method('deleteNoSonarMavenKey');

        $result = $this->controller->BatchCollecteNoSonar(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(['code' => 500, 'erreur' => 'down'], $result);
    }

    public function testReturnsErrorWhenDeleteFails(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['paging' => ['total' => 0], 'issues' => []],
        ]);

        $this->repo->expects($this->once())
            ->method('deleteNoSonarMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete failed']);

        $this->repo->expects($this->never())->method('insertNoSonar');

        $result = $this->controller->BatchCollecteNoSonar(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(['code' => 500, 'erreur' => 'delete failed'], $result);
    }

    public function testReturnsErrorWhenInsertFails(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['paging' => ['total' => 0], 'issues' => []],
        ]);
        $this->repo->method('deleteNoSonarMavenKey')->willReturn(['code' => 200]);
        $this->repo->expects($this->once())
            ->method('insertNoSonar')
            ->willReturn(['code' => 500, 'erreur' => 'insert failed']);

        $result = $this->controller->BatchCollecteNoSonar(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(['code' => 500, 'erreur' => 'insert failed'], $result);
    }

    public function testReturnsEmptyHistoriqueWhenNoIssuesFound(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['paging' => ['total' => 0], 'issues' => []],
        ]);
        $this->repo->method('deleteNoSonarMavenKey')->willReturn(['code' => 200]);
        $this->repo->expects($this->once())
            ->method('insertNoSonar')
            ->with([]) // tableau vide quand aucun issue
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteNoSonar(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['historique']['total_no_sonar']);
        $this->assertSame(0, $result['historique']['java_no_sonar']);
    }

    public function testCountsEachRuleTypeIndividually(): void
    {
        $issues = [
            ['rule' => 'java:S1309',    'component' => 'x:a.java', 'line' => 10],
            ['rule' => 'java:S1309',    'component' => 'x:b.java', 'line' => 20],
            ['rule' => 'java:S1310',    'component' => 'x:a.java', 'line' => 5],
            ['rule' => 'java:S1315',    'component' => 'x:a.java', 'line' => 3],
            ['rule' => 'java:NoSonar',  'component' => 'x:a.java', 'line' => 100],
            ['rule' => 'java:NoSonar',  'component' => 'x:b.java', 'line' => 101],
            ['rule' => 'java:NoSonar',  'component' => 'x:c.java', 'line' => 102],
            ['rule' => 'python:NoSonar','component' => 'x:a.py',   'line' => 50],
            ['rule' => 'php:NoSonar',   'component' => 'x:a.php',  'line' => 60],
            ['rule' => 'unknown:rule',  'component' => 'x:z.java', 'line' => null],
        ];

        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['paging' => ['total' => count($issues)], 'issues' => $issues],
        ]);
        $this->repo->method('deleteNoSonarMavenKey')->willReturn(['code' => 200]);

        $capturedInsert = null;
        $this->repo->expects($this->once())
            ->method('insertNoSonar')
            ->with($this->callback(function (array $data) use (&$capturedInsert) {
                $capturedInsert = $data;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteNoSonar(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        // Compteurs par règle (historique)
        $this->assertSame(3, $result['historique']['java_no_sonar']);
        $this->assertSame(1, $result['historique']['python_no_sonar']);
        $this->assertSame(1, $result['historique']['php_no_sonar']);
        $this->assertSame(1, $result['historique']['check_style']);
        $this->assertSame(1, $result['historique']['no_pmd']);
        $this->assertSame(2, $result['historique']['suppress_warning']);
        $this->assertSame(5, $result['historique']['total_no_sonar']); // java+python+php NoSonar

        // Tous les issues sont persistés (même 'unknown:rule')
        $this->assertCount(10, $capturedInsert);
        // Les line null sont remplacés par 0
        $nullLineIssue = array_values(array_filter($capturedInsert, fn ($i) => $i['line'] === 0));
        $this->assertCount(1, $nullLineIssue);
    }
}
