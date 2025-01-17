<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use PHPUnit\Framework\TestCase;
use App\Controller\Batch\BatchCollecteAnomalieController;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Client;
use App\Service\ExtractName;
use App\Service\DateTools;
use App\Repository\AnomalieRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BatchCollecteAnomalieControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $entityManager;

    /** @var Client&MockObject */
    private MockObject $client;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    /** @var AnomalieRepository&MockObject */
    private MockObject $anomalieRepository;

    /** @var BatchCollecteAnomalieController */
    private BatchCollecteAnomalieController $controller;

    /** @var ExtractName&MockObject */
    private MockObject $serviceExtractName;

    /** @var DateTools&MockObject */
    private MockObject $serviceDateTools;

    /** @var ContainerInterface&MockObject */
    private MockObject $container;

    private static $localhost = 'http://localhost';
    private static $api = 'http://localhost/api/issues/search?';
    private static $mel = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $frontend = 'frontend/';

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->anomalieRepository = $this->createMock(AnomalieRepository::class);
        $this->entityManager->method('getRepository')->willReturn($this->anomalieRepository);
        $this->serviceExtractName = $this->createMock(ExtractName::class);
        $this->serviceDateTools = $this->createMock(DateTools::class);

        // Création du mock pour ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Instanciation du contrôleur
        $this->controller = new BatchCollecteAnomalieController($this->entityManager, $this->client, $this->serviceExtractName, $this->serviceDateTools);
        $this->controller->setContainer($this->container);
    }

    public function testMakeRequestSuccess()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;

        // Réponse simulée pour une requête 200
        $mockResponse = ['code' => 200, ''];

        // Construire l'URL attendue
        $expectedUrl = static::$api . http_build_query($queryParams);

        // Configurez le mock pour retourner la réponse simulée
        $this->client->method('httpSonarQube')->with($this->equalTo($expectedUrl))->willReturn($mockResponse);

        // Utilisation de la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        // Appeler la méthode privée
        $result = $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);
        $this->assertEquals($mockResponse, $result);
    }

    public function testMakeRequestUnauthorizedError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;

        // Réponse simulée pour une erreur 401
        $mockErrorResponse = ['erreur' => 401, 'Unauthorized'];

        // Construire l'URL attendue
        $expectedUrl = static::$api . http_build_query($queryParams);

        // Configurer le mock pour retourner la réponse d'erreur
        $this->client->method('httpSonarQube')->with($this->equalTo($expectedUrl))->willReturn($mockErrorResponse);

        // Utilisation de la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        // Appeler la méthode privée
        $result = $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);
        $this->assertEquals($mockErrorResponse, $result);
    }

    public function testMakeRequestNotFoundError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $mockErrorResponse = ['code' => 404, 'erreur' => 'Not Found'];

        $this->client->method('httpSonarQube')
            ->with('http://localhost/api/issues/search?key=value')
            ->willReturn($mockErrorResponse);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        $this->assertEquals(['erreur' => 404, 'Not Found'], $result);
    }

    public function testMakeRequestOtherError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $mockErrorResponse = ['code' => 500, 'erreur' => 'Internal Server Error'];

        $this->client->method('httpSonarQube')
            ->with('http://localhost/api/issues/search?key=value')
            ->willReturn($mockErrorResponse);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        $this->assertEquals($mockErrorResponse, $result);
    }

    public function testBatchCollecteAnomalieSuccess()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturn([
            'paging' => ['total' => 1],
            'total' => 1,
            'effortTotal' => 120,
            'facets' => [
                ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                ['property' => 'types', 'values' => [['val' => 'BUG', 'count' => 2]]],
                ['property' => 'directories', 'values' => [['val' => static::$frontend, 'count' => 3]]],
            ],
        ]);

        // Préparation des données de retour pour la suppression et l'insertion
        $this->anomalieRepository->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);
        $this->anomalieRepository->method('insertAnomalie')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalie('mavenKey', 'manual', static::$mel);

        // Assertions
        $this->assertEquals(200, $result['code']);
        $this->assertEquals("Nombre d'anomalie : 1", $result['info']);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);

        $this->assertEquals([
            'maven_key' => 'mavenKey',
            'project_name' => '',
            'anomalie_total' => 1,
            'dette' => '',
            'dette_minute' => 120,
            'dette_reliability' => '',
            'dette_reliability_minute' => 120,
            'dette_vulnerability' => '',
            'dette_vulnerability_minute' => 120,
            'dette_code_smell' => '',
            'dette_code_smell_minute' => 120,
            'frontend' => 0,
            'backend' => 0,
            'autre' => 0,
            'blocker' => 1,
            'critical' => 0,
            'major' => 0,
            'info' => 0,
            'minor' => 0,
            'bug' => 2,
            'vulnerability' => 0,
            'code_smell' => 0,
            'mode_collecte' => 'manual',
            'utilisateur_collecte' => static::$mel,
            'date_enregistrement' => $result['message']['date_enregistrement']
        ], $result['message']);

        $this->assertEquals([
            'violations' => 1,
            'dette' => 120,
            'nombre_bug' => 2,
            'nombre_vulnerability' => 0,
            'nombre_code_smell' => 0,
            'frontend' => 0,
            'backend' => 0,
            'autre' => 0,
            'nombre_anomalie_bloquant' => 1,
            'nombre_anomalie_critique' => 0,
            'nombre_anomalie_info' => 0,
            'nombre_anomalie_majeur' => 0,
            'nombre_anomalie_mineur' => 0
        ], $result['data']);
    }

    public function testBatchCollecteAnomalieDeleteError()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturn([
            'paging' => ['total' => 1],
            'total' => 1,
            'effortTotal' => 120,
            'facets' => [
                ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                ['property' => 'types', 'values' => [['val' => 'BUG', 'count' => 2]]],
                ['property' => 'directories', 'values' => [['val' => static::$frontend, 'count' => 3]]],
            ],
        ]);

        // Configuration pour simuler une erreur lors de la suppression
        $this->anomalieRepository->method('deleteAnomalieMavenKey')->willReturn(['code' => 500, 'erreur' => 'Delete failed']);

        $result = $this->controller->BatchCollecteAnomalie('mavenKey', 'manual', static::$mel);

        // Vérification du retour en cas d'erreur
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Delete failed', $result['erreur'][0]);
    }

    public function testBatchCollecteAnomalieInsertError()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturn([
            'paging' => ['total' => 1],
            'total' => 1,
            'effortTotal' => 120,
            'facets' => [
                ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                ['property' => 'types', 'values' => [['val' => 'BUG', 'count' => 2]]],
                ['property' => 'directories', 'values' => [['val' => static::$frontend, 'count' => 3]]],
            ],
        ]);

        // Configuration pour simuler une erreur lors de l'insertion
        $this->anomalieRepository->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);
        $this->anomalieRepository->method('insertAnomalie')->willReturn(['code' => 500, 'erreur' => 'Insert failed']);

        $result = $this->controller->BatchCollecteAnomalie('mavenKey', 'manual', static::$mel);

        // Vérification du retour en cas d'erreur
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Insert failed', $result['erreur'][0]);
    }
}
