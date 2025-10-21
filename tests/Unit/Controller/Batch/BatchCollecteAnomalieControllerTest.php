<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use PHPUnit\Framework\TestCase;
use App\Controller\Batch\BatchCollecteAnomalieController;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ClientService;
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

    /** @var ClientService&MockObject */
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
    private static $frontend = '-presentation';
    private static $backend = '-api';
    private static $autre = '-batchs';
    private static $inconnue = '-twig';
    private static $httpError500 =  'Internal server error';

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
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
        $mockResponse = ['json' => ['response']];

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

        $expectedResult = ['response'];
        $this->assertEquals($expectedResult, $result);
    }

    public function testMakeRequestUnauthorizedError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $expectedUrl = static::$api . http_build_query($queryParams);
        $mockErrorResponse = ['code' => 401, 'erreur' => 'Unauthorized'];

        // Configurer le mock pour retourner la réponse d'erreur
        $this->client->method('httpSonarQube')->with($this->equalTo($expectedUrl))->willReturn($mockErrorResponse);

        // Utilisation de la réflexion pour accéder à la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        // Appeler la méthode privée
        $result = $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        // Vérifier le résultat attendu
        $expectedResult = ['code' => 401, 'erreur' => 'Unauthorized'];
        $this->assertEquals($expectedResult, $result);
    }

    public function testMakeRequestForbiddenError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $expectedUrl = static::$api . http_build_query($queryParams);
        $mockErrorResponse = ['code' => 403, 'erreur' => 'Forbidden'];

        $this->client->method('httpSonarQube')
            ->with($expectedUrl)
            ->willReturn($mockErrorResponse);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        // Vérifier le résultat attendu
        $expectedResult = ['code' => 403, 'erreur' => 'Forbidden'];
        $this->assertEquals($expectedResult, $result);
    }

    public function testMakeRequestNotFoundError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $expectedUrl = static::$api . http_build_query($queryParams);
        $mockErrorResponse = ['code' => 404, 'erreur' => 'Not Found'];

        $this->client->method('httpSonarQube')
            ->with($expectedUrl)
            ->willReturn($mockErrorResponse);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        // Vérifier le résultat attendu
        $expectedResult = ['code' => 404, 'erreur' => 'Not Found'];
        $this->assertEquals($expectedResult, $result);
    }

    public function testMakeRequestOtherError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $expectedUrl = static::$api . http_build_query($queryParams);
        $mockErrorResponse = ['code' => 500, 'erreur' => static::$httpError500];

        $this->client->method('httpSonarQube')
            ->with($expectedUrl)
            ->willReturn($mockErrorResponse);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        $expectedResult = ['code' => 500, 'erreur' => 'Internal server error'];
        $this->assertEquals($expectedResult, $result);
    }

    public function testBatchCollecteAnomalieSuccess()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturn(['json' => [
            'paging' => ['total' => 1],
            'total' => 1,
            'effortTotal' => 120,
            'facets' => [
                ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                ['property' => 'types', 'values' => [['val' => 'BUG', 'count' => 2]]],
                ['property' => 'directories', 'values' => [
                    ['val' => static::$frontend, 'count' => 1],
                    ['val' => static::$backend, 'count' => 2],
                    ['val' => static::$autre, 'count' => 3],
                    ['val' => static::$inconnue, 'count' => 50]]],
                ['property' => 'inconnue', 'message' => "Ce cas n'est pas possible"],
            ],
        ]]);

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
            'frontend' => 1,
            'backend' => 2,
            'autre' => 3,
            'inconnue' => 50,
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
            'frontend' => 1,
            'backend' => 2,
            'autre' => 3,
            'inconnue' => 50,
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
        $this->client->method('httpSonarQube')->willReturn(['json' =>
            ['paging' => ['total' => 1],
            'total' => 1,
            'effortTotal' => 120,
            'facets' => [
                ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                ['property' => 'types', 'values' => [['val' => 'BUG', 'count' => 2]]],
                ['property' => 'directories', 'values' => [['val' => static::$frontend, 'count' => 3]]],
            ]],
        ]);

        // Configuration pour simuler une erreur lors de la suppression
        $this->anomalieRepository->method('deleteAnomalieMavenKey')->willReturn(['code' => 500, 'erreur' => 'Delete failed']);

        $result = $this->controller->BatchCollecteAnomalie('mavenKey', 'traitement_manuel', static::$mel);

        // Vérification du retour en cas d'erreur
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Delete failed', $result['erreur']);
    }

    public function testBatchCollecteAnomalieInsertError()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturn(['json' => [
            'paging' => ['total' => 1],
            'total' => 1,
            'effortTotal' => 120,
            'facets' => [
                ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                ['property' => 'types', 'values' => [['val' => 'BUG', 'count' => 2]]],
                ['property' => 'directories', 'values' => [['val' => static::$frontend, 'count' => 3]]],
            ]],
        ]);

        // Configuration pour simuler une erreur lors de l'insertion
        $this->anomalieRepository->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);
        $this->anomalieRepository->method('insertAnomalie')->willReturn(['code' => 500, 'erreur' => 'Insert failed']);

        $result = $this->controller->BatchCollecteAnomalie('mavenKey', 'traitement_manuel', static::$mel);

        // Vérification du retour en cas d'erreur
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Insert failed', $result['erreur']);
    }

    public function testBatchCollecteAnomalieGeneralError()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'directories') !== false) {
                return ['code' => 500, 'erreur' => static::$httpError500];
            }
        });

        // simulation pour la requête delete et insert
        $this->anomalieRepository->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);
        $this->anomalieRepository->method('insertAnomalie')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalie('mavenKey', 'traitement_manuel', static::$mel);

        // Vérification du retour en cas d'erreur
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
        $this->assertEquals('general', $result['type']);
    }

    public function testBatchCollecteAnomalieBugError()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'directories') !== false) {
                return ['json' => []];
            }
            if (strpos($url, 'BUG') !== false) {
                return ['code' => 500, 'erreur' => static::$httpError500];
            }
        });

        // simulation pour la requête delete et insert
        $this->anomalieRepository->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);
        $this->anomalieRepository->method('insertAnomalie')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalie('mavenKey', 'traitement_manuel', static::$mel);

        // Vérification du retour en cas d'erreur
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
        $this->assertEquals('BUG', $result['type']);
    }

    public function testBatchCollecteAnomalieVulnerabilityError()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'directories') !== false) {
                return ['json' => []];
            }
            if (strpos($url, 'BUG') !== false) {
                return ['json' => []];
            }
            if (strpos($url, 'VULNERABILITY') !== false) {
                return ['code' => 500, 'erreur' => static::$httpError500];
            }
        });

        // simulation pour la requête delete et insert
        $this->anomalieRepository->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);
        $this->anomalieRepository->method('insertAnomalie')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalie('mavenKey', 'traitement_manuel', static::$mel);

        // Vérification du retour en cas d'erreur
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
        $this->assertEquals('VULNERABILITY', $result['type']);
    }

    public function testBatchCollecteAnomalieCodeSmellError()
    {
        // Création du Stub pour la méthode getParameter pour retourner une "dummy" URL
        $this->parameterBag->method('get')->willReturn(static::$api);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'directories') !== false) {
                return ['json' => []];
            }
            if (strpos($url, 'BUG') !== false) {
                return ['json' => []];
            }
            if (strpos($url, 'VULNERABILITY') !== false) {
                return ['json' => []];
            }
            if (strpos($url, 'CODE_SMELL') !== false) {
                return ['code' => 500, 'erreur' => static::$httpError500];
            }
        });

        // simulation pour la requête delete et insert
        $this->anomalieRepository->method('deleteAnomalieMavenKey')->willReturn(['code' => 200]);
        $this->anomalieRepository->method('insertAnomalie')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalie('mavenKey', 'traitement_manuel', static::$mel);

        // Vérification du retour en cas d'erreur
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
        $this->assertEquals('CODE_SMELL', $result['type']);
    }
}
