<?php

declare(strict_types=1);

namespace Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteNoSonarController;
use App\Service\Client;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\NoSonarRepository;
use Psr\Container\ContainerInterface;

class BatchCollecteNoSonarControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $entityManager;

    /** @var Client&MockObject */
    private MockObject $client;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    /** @var NoSonarRepository&MockObject */
    private MockObject $noSonarRepository;

    /** @var BatchCollecteNoSonarController */
    private BatchCollecteNoSonarController $controller;

    /** @var ContainerInterface&MockObject */
    private MockObject $container;

    private static $parameters='componentKeys=DummyMavenKey&rules=java:S1309,java:NoSonar&p=1&ps=500';

    protected function setUp(): void
    {
        // Création du mock pour EntityManagerInterface
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // Création du mock pour Client
        $this->client = $this->createMock(Client::class);

        // Création du mock pour ParameterBagInterface
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        // Création du mock pour NoSonarRepository
        $this->noSonarRepository = $this->createMock(NoSonarRepository::class);

        // Stubbing la méthode getRepository pour retourner le mock de NoSonarRepository
        $this->entityManager->method('getRepository')->willReturn($this->noSonarRepository);

        // Création du mock pour ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Stubbing la méthode getParameter pour retourner l'URL du sonar
        $this->parameterBag->method('get')->with(BatchCollecteNoSonarController::$sonarUrl)
            ->willReturn('http://localhost/api/issues/search?'.static::$parameters);

        // Instanciation du contrôleur
        $this->controller = new BatchCollecteNoSonarController($this->entityManager, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testBatchCollecteNoSonarSuccess(): void
    {
        // Configuration du mock Client pour retourner une réponse API correcte
        $this->client
            ->method('http')
            ->willReturn([
                'code' => 200,
                'paging' => ['total' => 2],
                'issues' => [
                    ['rule' => 'java:S1309', 'component' => 'component1', 'line' => 10],
                    ['rule' => 'java:NoSonar', 'component' => 'component2', 'line' => 20],
                ]
            ]);

        // Configuration du mock NoSonarRepository pour retourner un code 200 pour delete et insert
        $this->noSonarRepository
            ->method('deleteNoSonarMavenKey')
            ->willReturn(['code' => 200]);
        $this->noSonarRepository
            ->method('insertNoSonar')
            ->willReturn(['code' => 200]);

        // Exécution de la méthode à tester
        $result = $this->controller->BatchCollecteNoSonar('testKey', 'testMode', 'testUser');

        // Vérification des résultats
        $this->assertEquals(200, $result['code']);
        $this->assertEquals(['suppress_warning' => 1, 'no_sonar' => 1], $result['message']);
        $this->assertEquals(['suppress_warning' => 1, 'no_sonar' => 1], $result['data']);
    }

    public function testBatchCollecteNoSonarHttpError(): void
    {
        // Configuration du mock Client pour retourner une erreur HTTP
        $this->client
            ->method('http')
            ->willReturn(['code' => 401, 'erreur' => 'Unauthorized']);

        // Exécution de la méthode à tester
        $result = $this->controller->BatchCollecteNoSonar('testKey', 'testMode', 'testUser');

        // Vérification des résultats
        $this->assertEquals(401, $result['code']);
        $this->assertEquals(['Unauthorized'], $result['error']);
    }

    public function testBatchCollecteNoSonarDeleteError(): void
    {
        // Configuration du mock Client pour retourner une réponse API correcte
        $this->client
            ->method('http')
            ->willReturn([
                'code' => 200,
                'paging' => ['total' => 1],
                'issues' => [
                    ['rule' => 'java:S1309', 'component' => 'component1', 'line' => 10],
                ]
            ]);

        // Configuration du mock NoSonarRepository pour retourner une erreur pour delete
        $this->noSonarRepository
            ->method('deleteNoSonarMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'Deletion failed']);

        // Exécution de la méthode à tester
        $result = $this->controller->BatchCollecteNoSonar('testKey', 'testMode', 'testUser');

        // Vérification des résultats
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(['Deletion failed', 'requête : ' => 'deleteNoSonarMavenKey'], $result['error']);
    }

    public function testBatchCollecteNoSonarInsertError(): void
    {
        // Configuration du mock Client pour retourner une réponse API correcte
        $this->client
            ->method('http')
            ->willReturn([
                'code' => 200,
                'paging' => ['total' => 1],
                'issues' => [
                    ['rule' => 'java:S1309', 'component' => 'component1', 'line' => 10],
                ]
            ]);

        // Configuration du mock NoSonarRepository pour retourner une erreur pour insert
        $this->noSonarRepository
            ->method('deleteNoSonarMavenKey')
            ->willReturn(['code' => 200]);
        $this->noSonarRepository
            ->method('insertNoSonar')
            ->willReturn(['code' => 500, 'erreur' => 'Insertion failed']);

        // Exécution de la méthode à tester
        $result = $this->controller->BatchCollecteNoSonar('testKey', 'testMode', 'testUser');

        // Vérification des résultats
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(['Insertion failed', 'requête : ' => 'insertNoSonar'], $result['error']);
    }
}
