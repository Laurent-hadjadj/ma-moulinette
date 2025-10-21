<?php

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteTodoController;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ClientService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\TodoRepository;
use Symfony\Component\DependencyInjection\Container;
use Psr\Container\ContainerInterface;

/**
 * [Description BatchCollecteTodoControllerTest]
 */
class BatchCollecteTodoControllerTest extends TestCase
{
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private EntityManagerInterface $entityManager;

    /** @var Client&\PHPUnit\Framework\MockObject\MockObject */
    private ClientService $client;

    /** @var ParameterBagInterface&\PHPUnit\Framework\MockObject\MockObject */
    private ParameterBagInterface $parameterBag;

    /** @var TodoRepository&\PHPUnit\Framework\MockObject\MockObject */
    private TodoRepository $todoRepository;

    /** @var BatchCollecteTodoController */
    private BatchCollecteTodoController $controller;

    /** @var ContainerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private ContainerInterface $container;

    private static $parameters='componentKeys=DummyMavenKey&rules=javascript:S1135,xml:S1135,typescript:S1135,Web:S1135,java:S1135,php:s1135,ruby:s1135,python:s1135&p=1&ps=500';
    private static $api = 'http://localhost/api/issues/search?';
    private static $s1135 = 'php:s1135';

    protected function setUp(): void
    {
        /**
          * Création des Mocks pout
          * EntityManagerInterface
          * Client
          * ParameterBagInterface
          * TodoRepository
          */
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->todoRepository = $this->createMock(TodoRepository::class);

        /** Stubbing la méthode getRepository pour retourner le mock de TodoRepository */
        $this->entityManager->method('getRepository')->willReturn($this->todoRepository);

        /* Création du mock pour ContainerInterface */
        $this->container = $this->createMock(Container::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Instanciation du contrôleur
        $this->controller = new BatchCollecteTodoController($this->entityManager, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testBatchCollecteTodoSuccess()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api.static::$parameters);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturn(
            ['json' => ['paging' => ['total' => 1], 'issues' => [['rule' => static::$s1135, 'component' => 'component', 'line' => 10]]]]);

        // Création du mock pour la méthode deleteTodoMavenKey
        $this->todoRepository->method('deleteTodoMavenKey')->willReturn(['code' => 200]);

        // Création du mock pour la méthode insertTodo
        $this->todoRepository->method('insertTodo')->willReturn(['code' => 200]);

        // Appel de la méthode à tester
        $result = $this->controller->BatchCollecteTodo('dummyMavenKey', 'dummyModeCollecte', 'dummyUtilisateurCollecte');

        // Assertions
        $this->assertEquals(200, $result['code']);
        $this->assertEquals(1, $result['nombre']);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
    }

    public function testBatchCollecteTodoHttp401Error()
    {
        $this->parameterBag->method('get')
            ->willReturn(static::$api.static::$parameters);

        $this->client->method('httpSonarQube')
            ->willReturn(['code' => 401, 'erreur' => 'Unauthorized']);

        $result = $this->controller->BatchCollecteTodo('dummyMavenKey', 'dummyModeCollecte', 'dummyUtilisateurCollecte');

        // Assertions
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(401, $result['code']);
        $this->assertEquals('Unauthorized', $result['erreur']);

    }

    public function testBatchCollecteTodoDeleteError()
    {
        $this->parameterBag->method('get')
            ->willReturn(static::$api.static::$parameters);

        $this->client
            ->method('httpSonarQube')
            ->willReturn(['json' => ['paging' => ['total' => 1], 'issues' => [['rule' => static::$s1135, 'component' => 'component', 'line' => 10]]]]);

        $this->todoRepository->method('deleteTodoMavenKey')->willReturn(['code' => 500, 'erreur' => 'Internal server error']);

        // Calling the method to test
        $result = $this->controller->BatchCollecteTodo('dummyMavenKey', 'dummyModeCollecte', 'dummyUtilisateurCollecte');

        // Assertions
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Internal server error', $result['erreur']);

    }

    public function testBatchCollecteTodoInsertError()
    {
        $this->parameterBag
            ->method('get')
            ->willReturn(static::$api.static::$parameters);

        $this->client
            ->method('httpSonarQube')
            ->willReturn(['json' => ['paging' => ['total' => 1], 'issues' => [['rule' => static::$s1135, 'component' => 'component', 'line' => 10]]]]);

        $this->todoRepository
            ->method('deleteTodoMavenKey')
            ->willReturn(['code' => 200]);

        $this->todoRepository
            ->method('insertTodo')
            ->willReturn(['code' => 500, 'erreur' => 'Insert error']);

        $result = $this->controller->BatchCollecteTodo('dummyMavenKey', 'dummyModeCollecte', 'dummyUtilisateurCollecte');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Insert error', $result['erreur']);
    }
}
