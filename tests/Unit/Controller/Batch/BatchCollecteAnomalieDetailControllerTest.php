<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use PHPUnit\Framework\TestCase;
use App\Controller\Batch\BatchCollecteAnomalieDetailController;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\AnomalieDetailsRepository;
use App\Service\ExtractName;
use App\Service\Client;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BatchCollecteAnomalieDetailControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $entityManager;

    /** @var Client&MockObject */
    private MockObject $client;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    /** @var AnomalieDetailsRepository&MockObject */
    private MockObject $anomalieDetailsRepository;

    /** @var ExtractName&MockObject */
    private MockObject $serviceExtractName;

    /** @var BatchCollecteAnomalieDetailsController */
    private BatchCollecteAnomalieDetailController $controller;

    /** @var ContainerInterface&MockObject */
    private MockObject $container;

    private static $localhost = 'http://localhost';
    private static $api = 'http://localhost/api/issues/search?';
    private static $httpError500 = 'Internal server error';

    protected function setUp(): void
    {
        parent::setUp();

        // Créez de nouveaux mocks pour chaque test
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->serviceExtractName = $this->createMock(ExtractName::class);
        $this->anomalieDetailsRepository = $this->createMock(AnomalieDetailsRepository::class);
        $this->entityManager->method('getRepository')->willReturn($this->anomalieDetailsRepository);

        // Création du mock pour ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Stubbing la méthode getParameter pour retourner l'URL du sonar
        $this->parameterBag->method('get')->with(BatchCollecteAnomalieDetailController::$sonarUrl)
            ->willReturn(static::$localhost);

        // Instanciation du contrôleur
        $this->controller = new BatchCollecteAnomalieDetailController($this->entityManager, $this->client, $this->serviceExtractName);
        $this->controller->setContainer($this->container);
    }

    public function testBatchCollecteAnomalieDetailUnauthorizedError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $expectedUrl = static::$api . http_build_query($queryParams);
        $mockErrorResponse = ['code' => 401, 'erreur' => 'Unauthorized'];

        // Configurer le mock pour retourner la réponse d'erreur
        $this->client->expects($this->once())
                ->method('httpSonarQube')
                ->with($this->equalTo($expectedUrl))
                ->willReturn($mockErrorResponse);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);

        $result = $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        // Vérifier le résultat attendu
        $expectedResult = ['code' => 401, 'erreur' => 'Unauthorized'];
        $this->assertEquals($expectedResult, $result);
    }

    public function testBatchCollecteAnomalieDetailForbiddenError()
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

    public function testBatchCollecteAnomalieDetailNotFoundError()
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

    public function testBatchCollecteAnomalieDetailOtherError()
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

        $expectedResult = ['code' => 500, 'erreur' => static::$httpError500];
        $this->assertEquals($expectedResult, $result);
    }

    public function testBatchCollecteAnomalieDetailMakeRequestError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $mockErrorResponse = ['code' => 500, 'erreur' => static::$httpError500];

        // Configurer le mock pour retourner la réponse d'erreur
        $this->client->expects($this->any())
                ->method('httpSonarQube')
                ->willReturn($mockErrorResponse);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);
        $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        $result = $this->controller->BatchCollecteAnomalieDetail('maven_key', 'mode_collecte', 'mode_utilisateur');
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
        $this->assertEquals('BUG', $result['type']);
    }

    public function testBatchCollecteAnomalieDetailDeleteError()
    {
        // Mocking client http response
        $this->client->method('httpSonarQube')->willReturn([
                'json' => ['paging' => ['total' => 1],
                'facets' => [
                    ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                    ['property' => 'severities', 'values' => [['val' => 'CRITICAL', 'count' => 1]]],
                    ['property' => 'severities', 'values' => [['val' => 'MAJOR', 'count' => 1]]],
                    ['property' => 'severities', 'values' => [['val' => 'MINOR', 'count' => 1]]],
                    ['property' => 'severities', 'values' => [['val' => 'INFO', 'count' => 1]]],
                ]],
            ]);
        $this->anomalieDetailsRepository->method('deleteAnomalieDetailsMavenKey')->willReturn(['code' => 500, 'erreur' => static::$httpError500]);
        $this->anomalieDetailsRepository->method('insertAnomalieDetail')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalieDetail('maven_key', 'mode_collecte', 'utilisateur_collecte');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
    }

    public function testBatchCollecteAnomalieDetailInsertError()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $mockErrorResponse = ['json' =>
            ['paging' => ['total' => 1],
                'facets' => [
                    ['property' => 'severities', 'values' => [
                        ['val' => 'BLOCKER', 'count' => 1],
                        ['val' => 'CRITICAL', 'count' => 1],
                        ['val' => 'MAJOR', 'count' => 1],
                        ['val' => 'MINOR', 'count' => 1],
                        ['val' => 'INFO', 'count' => 1] ]],
                    ]],
            ];

        // Configurer le mock pour retourner la réponse d'erreur
        $this->client->expects($this->atLeastOnce())
                ->method('httpSonarQube')
                ->willReturn($mockErrorResponse);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);
        $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        $this->anomalieDetailsRepository->method('deleteAnomalieDetailsMavenKey')->willReturn(['code' => 200]);
        $this->anomalieDetailsRepository->method('insertAnomalieDetail')->willReturn(['code' => 500, 'erreur' => static::$httpError500]);

        $result = $this->controller->BatchCollecteAnomalieDetail('maven_key', 'mode_collecte', 'mode_utilisateur');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
    }


    public function testBatchCollecteAnomalieDetailSuccess()
    {
        $queryParams = ['key' => 'value'];
        $tempoUrl = static::$localhost;
        $mockErrorResponse = ['json' =>
            ['paging' => ['total' => 1],
            'facets' => [
                ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                ['property' => 'severities', 'values' => [['val' => 'CRITICAL', 'count' => 1]]],
                ['property' => 'severities', 'values' => [['val' => 'MAJOR', 'count' => 1]]],
                ['property' => 'severities', 'values' => [['val' => 'MINOR', 'count' => 1]]],
                ['property' => 'severities', 'values' => [['val' => 'INFO', 'count' => 1]]],
            ]],
        ];

        // Configurer le mock pour retourner la réponse d'erreur
        $this->client->expects($this->any())
                ->method('httpSonarQube')
                ->willReturn($mockErrorResponse);

        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('makeRequest');
        $method->setAccessible(true);
        $method->invokeArgs($this->controller, [$queryParams, $tempoUrl]);

        $this->anomalieDetailsRepository->method('deleteAnomalieDetailsMavenKey')->willReturn(['code' => 200]);
        $this->anomalieDetailsRepository->method('insertAnomalieDetail')->willReturn(['code' => 200]);

        // Mocking ExtractName service
        $this->serviceExtractName->method('extractNameFromMavenKey')->willReturn('ProjectName');

        $result = $this->controller->BatchCollecteAnomalieDetail('maven_key', 'mode_collecte', 'mode_utilisateur');

        $this->assertEquals(200, $result['code']);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $expectedData = [
        'bug_blocker' => 1, 'bug_critical' => 1, 'bug_major' => 1, 'bug_minor' => 1,
        'bug_info' => 1, 'bug_critical' => 0, 'bug_major' => 0, 'bug_minor' => 0,
        'bug_info' => 0, 'vulnerability_blocker' => 1, 'vulnerability_critical' => 1,
        'vulnerability_major' => 1, 'vulnerability_minor' => 1, 'vulnerability_info' => 1,
        'vulnerability_critical' => 0, 'vulnerability_major' => 0, 'vulnerability_minor' => 0,
        'vulnerability_info' => 0, 'code_smell_blocker' => 1, 'code_smell_critical' => 1,
        'code_smell_major' => 1, 'code_smell_minor' => 1, 'code_smell_info' => 1,
        'code_smell_critical' => 0, 'code_smell_major' => 0, 'code_smell_minor' => 0,
        'code_smell_info' => 0];

        $this->assertEquals($expectedData, $result['data']);
    }

    public function testBatchCollecteAnomalieDetailNoIssues()
    {
        $this->anomalieDetailsRepository->method('deleteAnomalieDetailsMavenKey')->willReturn(['code' => 200]);
        $this->anomalieDetailsRepository->method('insertAnomalieDetail')->willReturn(['code' => 200]);

        // Mocking client http response
        $this->client->method('httpSonarQube')->willReturn([
            'json' => ['paging' => ['total' => 0]]
        ]);

        $result = $this->controller->BatchCollecteAnomalieDetail('mavenKey', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');

        $this->assertEquals(200, $result['code']);
        $this->assertEquals("Pas d'anomalie trouvée", $result['message']);
        $this->assertEmpty($result['data']);
    }
}
