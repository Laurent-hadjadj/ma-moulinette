<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteTodoController;
use App\Entity\Todo;
use App\Repository\TodoRepository;
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
class BatchCollecteTodoControllerTest extends TestCase
{
    private const MAVEN_KEY = 'com.acme:app';

    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var ClientService&MockObject */           private MockObject $client;
    /** @var UrlBuilderService&MockObject */       private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */         private MockObject $logger;
    /** @var TodoRepository&MockObject */          private MockObject $repo;
    /** @var ParameterBagInterface&MockObject */   private MockObject $parameterBag;

    private BatchCollecteTodoController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(TodoRepository::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->em->expects($this->atLeastOnce())
            ->method('getRepository')
            ->with(Todo::class)
            ->willReturn($this->repo);

        $this->urlBuilder->method('build')->willReturn('https://sonar.example.com/api/...');
        $this->parameterBag->method('get')->willReturn('https://sonar.example.com');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([['parameter_bag', true]]);
        $container->method('get')->willReturnMap([['parameter_bag', 1, $this->parameterBag]]);

        $this->controller = new BatchCollecteTodoController(
            $this->em, $this->client, $this->urlBuilder, $this->logger
        );
        $this->controller->setContainer($container);
    }

    public function testReturnsErrorWhenFirstSonarCallFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 503, 'erreur' => 'down']);

        $this->repo->expects($this->never())->method('deleteTodoMavenKey');

        $result = $this->controller->BatchCollecteTodo(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(503, $result['code']);
    }

    public function testReturnsErrorWhenDeleteFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => ['paging' => ['total' => 0], 'issues' => []]]);

        $this->repo->expects($this->once())
            ->method('deleteTodoMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $result = $this->controller->BatchCollecteTodo(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
    }

    public function testReturnsZeroWhenNoTodosFound(): void
    {
        $this->client->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => ['paging' => ['total' => 0], 'issues' => []]]);
        $this->repo->method('deleteTodoMavenKey')->willReturn(['code' => 200]);

        $this->repo->expects($this->never())->method('insertTodo');

        $result = $this->controller->BatchCollecteTodo(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        $this->assertSame(0, $result['nombre']);
    }

    public function testCountsTodosByLanguageFromRuleKey(): void
    {
        $issues = [
            ['rule' => 'java:S1135',       'component' => 'x:a.java', 'line' => 10],
            ['rule' => 'java:S1135',       'component' => 'x:b.java', 'line' => 20],
            ['rule' => 'python:S1135',    'component' => 'x:a.py',   'line' => 30],
            ['rule' => 'php:S1135',       'component' => 'x:a.php',  'line' => null],
            ['rule' => 'javascript:S1135','component' => 'x:a.js',   'line' => 50],
            ['rule' => 'typescript:S1135','component' => 'x:a.ts',   'line' => 60],
            ['rule' => 'ruby:S1135',      'component' => 'x:a.rb',   'line' => 70],
            ['rule' => 'web:S1135',       'component' => 'x:a.html', 'line' => 80],
            ['rule' => 'xml:S1315',       'component' => 'x:a.xml',  'line' => 90],
            ['rule' => 'unknown:rule',    'component' => 'x:z',      'line' => 100],
        ];

        // Appel 1 : pré-check avec ps=1 → 1 issue pour activer la suite
        // Appel 2 : page 1 full → toutes les issues
        // Appel 3 : page 2 → vide → break
        $this->client->expects($this->exactly(3))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['paging' => ['total' => count($issues)], 'issues' => [$issues[0]]]],
                ['code' => 200, 'json' => ['paging' => ['total' => count($issues)], 'issues' => $issues]],
                ['code' => 200, 'json' => ['paging' => ['total' => count($issues)], 'issues' => []]],
            );

        $this->repo->method('deleteTodoMavenKey')->willReturn(['code' => 200]);
        $this->repo->expects($this->once())
            ->method('insertTodo')
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteTodo(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        // Chaque langue a son propre compteur (bug copy-paste corrigé)
        $this->assertSame(2, $result['historique']['java_todo']);
        $this->assertSame(1, $result['historique']['python_todo']);
        $this->assertSame(1, $result['historique']['php_todo']);
        $this->assertSame(1, $result['historique']['javascript_todo']);
        $this->assertSame(1, $result['historique']['typescript_todo']);
        $this->assertSame(1, $result['historique']['ruby_todo']);
        $this->assertSame(1, $result['historique']['web_todo']);
        $this->assertSame(1, $result['historique']['xml_todo']);
    }

    /**
     * Régression 2026-05-03 : suppression de l'init `$java_todo = ... = $inconnu = 0;`
     * faisait crasher en runtime avec "Warning: Undefined variable $inconnu" dès la
     * première règle non reconnue. Ce test exerce le default du switch et vérifie
     * que tous les compteurs sont des entiers (init effective).
     */
    public function testCountersAreInitializedToZeroEvenWhenAllRulesUnknown(): void
    {
        $issues = [
            ['rule' => 'unknown:rule1', 'component' => 'x:a', 'line' => 1],
            ['rule' => 'unknown:rule2', 'component' => 'x:b', 'line' => 2],
        ];

        $this->client->expects($this->exactly(3))
            ->method('httpSonarQube')
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'json' => ['paging' => ['total' => count($issues)], 'issues' => [$issues[0]]]],
                ['code' => 200, 'json' => ['paging' => ['total' => count($issues)], 'issues' => $issues]],
                ['code' => 200, 'json' => ['paging' => ['total' => count($issues)], 'issues' => []]],
            );
        $this->repo->method('deleteTodoMavenKey')->willReturn(['code' => 200]);
        $this->repo->method('insertTodo')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteTodo(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(200, $result['code']);
        // Chaque clé doit être un int (0), pas null/undefined → garantit l'init
        foreach (['java_todo', 'python_todo', 'php_todo', 'xml_todo', 'web_todo',
                  'javascript_todo', 'typescript_todo', 'ruby_todo'] as $k) {
            $this->assertIsInt($result['historique'][$k], "$k devrait être initialisé à 0");
            $this->assertSame(0, $result['historique'][$k], "$k devrait valoir 0 (aucune règle connue)");
        }
    }

    public function testReturnsErrorWhenInsertFails(): void
    {
        $issues = [['rule' => 'java:S1135', 'component' => 'x:a.java', 'line' => 1]];

        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['paging' => ['total' => 1], 'issues' => $issues],
        ]);
        $this->repo->method('deleteTodoMavenKey')->willReturn(['code' => 200]);
        $this->repo->expects($this->once())
            ->method('insertTodo')
            ->willReturn(['code' => 500, 'erreur' => 'insert failed']);

        $result = $this->controller->BatchCollecteTodo(self::MAVEN_KEY, 'auto', 'u');

        $this->assertSame(500, $result['code']);
        $this->assertSame('error', $result['type']);
    }
}
