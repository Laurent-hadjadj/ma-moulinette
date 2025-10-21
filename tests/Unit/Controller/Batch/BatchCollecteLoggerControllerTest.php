<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteLoggerController;
use PHPUnit\Framework\MockObject\MockObject;
use App\service\ClientService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\LoggerRepository;
use Psr\Container\ContainerInterface;

/**
 * [Description BatchCollecteLoggerControllerTest]
 */
class BatchCollecteLoggerControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $entityManager;

    /** @var Client&MockObject */
    private MockObject $client;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    /** @var LoggerRepository&MockObject */
    private MockObject $loggerRepository;

    /** @var BatchCollecteLoggerController */
    private BatchCollecteLoggerController $controller;

    /** @var ContainerInterface&MockObject */
    private MockObject $container;

    private static $localhost = 'http://localhost';
    private static $api = 'http://localhost/api/issues/search?';
    private static $mel = 'laurent.hadjadj@ma-petite-entreprise.fr';

    protected function setUp(): void
    {
        // Création du mock pour EntityManagerInterface
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        // Création du mock pour Client
        $this->client = $this->createMock(Client::class);

        // Création du mock pour ParameterBagInterface
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        // Création du mock pour LoggerRepository
        $this->loggerRepository = $this->createMock(LoggerRepository::class);

        // Stubbing la méthode getRepository pour retourner le mock de LoggerRepository
        $this->entityManager->method('getRepository')->willReturn($this->loggerRepository);

        // Création du mock pour ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Instanciation du contrôleur
        $this->controller = new BatchCollecteLoggerController($this->entityManager, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testBatchCollecteLoggerPluginDisabled(): void
    {
        // On valide la méthode deleteLoggerMavenKey
        $this->loggerRepository->method('deleteLoggerMavenKey')->willReturn(['code' => 200]);
        // On valide la méthode insertLogger
        $this->loggerRepository->method('insertLogger')->willReturn(['code' => 200]);

        // Mock the parameter to simulate that the logger plugin is not activated
        $this->parameterBag->method('get')->willReturnMap([
            ['track.logger.method', false],
            ['sonar.url', static::$localhost]
        ]);

        // Call the method that uses the parameter
        $result = $this->controller->BatchCollecteLogger('some-maven-key', 'COLLECTE', 'laurent.hadjadj@ma-petite-entreprise.com');

        // Assert that the result is as expected
        $this->assertEquals(['code' => 404, 'message' => "La collecte des LOGGERS n'a pas été lancée. (TRACK_LOGGER_METHOD=false).", 'data' => ''], $result);
    }

    public function testMakeRequestSuccess()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;

        // Réponse simulée pour une requête 401
        $mockResponse = ['total' => -1];

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
        $this->assertEquals($mockResponse['total'], $result['total']);
    }

    public function testMakeRequestUnauthorizedError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;

        // Réponse simulée pour une erreur 401
        $mockErrorResponse = ['code' => 401, 'erreur' => 'Unauthorized'];

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
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(401, $result['code']);
        $this->assertEquals('Unauthorized', $result['erreur']);
    }

    public function testMakeRequestForbiddenError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;

        // Réponse simulée pour une erreur 403
        $mockErrorResponse = ['code' => 403, 'erreur' => 'Forbidden'];

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
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(403, $result['code']);
        $this->assertEquals('Forbidden', $result['erreur']);
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
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(404, $result['code']);
        $this->assertEquals('Not Found', $result['erreur']);
    }

    public function testMakeRequestInternalError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $mockErrorResponse = ['code' => 500, 'erreur' => 'Internal Server Error'];

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

        // Vérifier que la réponse contient les clés 'code' et 'erreur'
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);

        // Vérifier les valeurs des clés 'code' et 'erreur'
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Internal Server Error', $result['erreur']);
    }

    public function testMakeRequestOtherError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $mockErrorResponse = ['code' => 400, 'erreur' => 'Bad request'];

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

        // Vérifier que la réponse contient les clés 'code' et 'erreur'
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);

        // Vérifier les valeurs des clés 'code' et 'erreur'
        $this->assertEquals(400, $result['code']);
        $this->assertEquals('Bad request', $result['erreur']);
    }

    public function testBatchCollecteLoggerError()
    {
        // Le plugin Logger est activé. L'URL du serveur est défini
        $this->parameterBag->method('get')->willReturnMap([
            ['track.logger.method', true],
            ['sonar.url', static::$localhost]
        ]);

        $expectedResult = ['code' => 404, 'erreur' => 'Not Found'];
        $this->client->method('httpSonarQube')->willReturn($expectedResult);

        $result = $this->controller->BatchCollecteLogger('some-maven-key', 'COLLECTE', static::$mel);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(404, $result['code']);
        $this->assertEquals('Not Found', $result['erreur']);
    }

    public function testBatchCollecteLoggerTrackInfoMethodError()
    {
        // Le plugin Logger est activé. L'URL du serveur est défini
        $this->parameterBag->method('get')->willReturnMap([
            ['track.logger.method', true],
            ['sonar.url', static::$localhost]
        ]);

        // Simuler une réponse d'erreur pour 'track-info-method'
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'track-info-method') !== false) {
                return ['code' => 500, 'erreur' => 'Internal Server Error'];
            }
            return [];
        });

        $result = $this->controller->BatchCollecteLogger('some-maven-key', 'COLLECTE', static::$mel);

        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('tracker', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Internal Server Error', $result['erreur']);
        $this->assertEquals('track-info-method', $result['tracker']);
    }

    public function testBatchCollecteLoggerTrackWarnMethodError()
    {
        // Le plugin Logger est activé. L'URL du serveur est défini
        $this->parameterBag->method('get')->willReturnMap([
            ['track.logger.method', true],
            ['sonar.url', static::$localhost]
        ]);

        // Simuler une réponse d'erreur pour 'track-info-method'
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'track-info-method') !== false) {
                return ['code' =>200, 'erreur' => '', 'total' => 1];
            } elseif (strpos($url, 'track-warn-method') !== false) {
                return ['code' => 401, 'erreur' => 'Unauthorized'];
            }
            return [];
        });

        $result = $this->controller->BatchCollecteLogger('some-maven-key', 'COLLECTE', static::$mel);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('tracker', $result);
        $this->assertEquals(401, $result['code']);
        $this->assertEquals('Unauthorized', $result['erreur']);
        $this->assertEquals('track-warn-method', $result['tracker']);
    }

    public function testBatchCollecteLoggerTrackErrorMethodError()
    {
        // Le plugin Logger est activé. L'URL du serveur est défini
        $this->parameterBag->method('get')->willReturnMap([
            ['track.logger.method', true],
            ['sonar.url', static::$localhost]
        ]);

        // Simuler une réponse d'erreur pour 'track-info-method'
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'track-info-method') !== false) {
                return ['code' => 200, 'erreur' => '', 'total' => 1];
            } elseif (strpos($url, 'track-warn-method') !== false) {
                return ['code' => 200, 'erreur' => '', 'total' => 1];
            } elseif (strpos($url, 'track-error-method') !== false) {
                return ['code' => 403, 'erreur' => 'Forbidden'];
            }
            return [];
        });

        $result = $this->controller->BatchCollecteLogger('some-maven-key', 'COLLECTE', static::$mel);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('tracker', $result);
        $this->assertEquals(403, $result['code']);
        $this->assertEquals('Forbidden', $result['erreur']);
        $this->assertEquals('track-error-method', $result['tracker']);
    }

    public function testBatchCollecteLoggerTrackDebugMethodError()
    {
        // Le plugin Logger est activé. L'URL du serveur est défini
        $this->parameterBag->method('get')->willReturnMap([
            ['track.logger.method', true],
            ['sonar.url', static::$localhost]
        ]);

        // Simuler une réponse d'erreur pour 'track-info-method'
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'track-info-method') !== false) {
                return ['code' => 200, 'erreur' => '', 'total' => 1];
            } elseif (strpos($url, 'track-warn-method') !== false) {
                return ['code' => 200, 'erreur' => '', 'total' => 1];
            } elseif (strpos($url, 'track-error-method') !== false) {
                return ['code' => 200, 'erreur' => '', 'total' => 1];
            } elseif (strpos($url, 'track-debug-method') !== false) {
                return ['code' => 404, 'erreur' => 'Not found'];
            }
            return [];
        });

        $result = $this->controller->BatchCollecteLogger('some-maven-key', 'COLLECTE', static::$mel);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('tracker', $result);
        $this->assertEquals(404, $result['code']);
        $this->assertEquals('Not found', $result['erreur']);
        $this->assertEquals('track-debug-method', $result['tracker']);
    }

    public function testBatchCollecteLoggerSuccess()
    {
        // Le plugin Logger est activé. L'URL du serveur est défini
        $this->parameterBag->method('get')->willReturnMap([
            ['track.logger.method', true],
            ['sonar.url', static::$localhost]
        ]);

        // On valide la méthode deleteLoggerMavenKey
        $this->loggerRepository->method('deleteLoggerMavenKey')->willReturn(['code' => 200]);
        $this->loggerRepository->method('insertLogger')->willReturn(['code' => 200]);

        $this->client->method('httpSonarQube')->willReturn(['total' => 1]);
        $this->client->method('httpSonarQube')->willReturnOnConsecutiveCalls(
            ['total' => 5],
            ['total' => 3],
            ['total' => 1],
            ['total' => 0]);

        $result = $this->controller->BatchCollecteLogger('some-maven-key', 'COLLECTE', static::$mel);
        $expectedDateEnregistrement = $result['message']['date_enregistrement'];
        $this->assertEquals($expectedDateEnregistrement->format(\DateTime::ATOM), $result['message']['date_enregistrement']->format(\DateTime::ATOM));

        $expectedResult = [
            'code' => 200,
            'message' => [
                'maven_key' => 'some-maven-key',
                'logger_info' => 1,
                'logger_warn' => 1,
                'logger_error' => 1,
                'logger_debug' => 1,
                'mode_collecte' => 'COLLECTE',
                'utilisateur_collecte' => static::$mel,
                'date_enregistrement' => $expectedDateEnregistrement
            ],
            'data' => [
                'maven_key' => 'some-maven-key',
                'logger_info' => ['total' => 1],
                'logger_warn' => ['total' => 1],
                'logger_error' => ['total' => 1],
                'logger_debug' => ['total' => 1]
            ]
        ];

        $this->assertEquals($expectedResult, $result);
    }

    public function testBatchCollecteLoggerDeleteError()
    {
        // Le plugin Logger est activé. L'URL du serveur est défini
        $this->parameterBag->method('get')->willReturnMap([
            ['track.logger.method', true],
            ['sonar.url', static::$localhost]
        ]);

        $this->client->method('httpSonarQube')->willReturn(['total' => 5]);

        $this->loggerRepository->method('deleteLoggerMavenKey')->willReturn(['code' => 500, 'erreur' => 'Error deleting logger data']);
        $this->loggerRepository->method('insertLogger')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteLogger('some-maven-key', 'COLLECTE', static::$mel);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(500, $result['code']);
    }

    public function testBatchCollecteLoggerInsertError()
    {
        // Le plugin Logger est activé. L'URL du serveur est défini
        $this->parameterBag->method('get')->willReturnMap([
            ['track.logger.method', true],
            ['sonar.url', static::$localhost]
        ]);

        $this->client->method('httpSonarQube')->willReturn(['total' => -1]);
        $this->loggerRepository->method('deleteLoggerMavenKey')->willReturn(['code' => 200]);
        $this->loggerRepository->method('insertLogger')->willReturn(['code' => 500, 'erreur' => 'Erreur 500']);

        $result = $this->controller->BatchCollecteLogger('some-maven-key', 'COLLECTE', static::$mel);
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(500, $result['code']);
    }
}
