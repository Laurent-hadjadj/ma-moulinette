<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Client;
use App\Service\ExtractName;
use App\Repository\HotspotsRepository;
use App\Repository\HotspotDetailsRepository;
use App\Repository\InformationProjetRepository;
use App\Controller\Batch\BatchCollecteHotspotDetailController;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BatchCollecteHotspotDetailControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $entityManager;

    /** @var Client&MockObject */
    private MockObject $client;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    /** @var HotspotsRepository&MockObject */
    private MockObject $hotspotsRepository;

    /** @var HotspotDetailsRepository&MockObject */
    private MockObject $hotspotDetailsRepository;

    /** @var InformationProjetRepository&MockObject */
    private MockObject $informationProjetRepository;

    /** @var ExtractName&MockObject */
    private MockObject $serviceExtractName;

    /** @var BatchCollecteHotspotDetailController */
    private BatchCollecteHotspotDetailController $controller;

    /** @var ContainerInterface&MockObject */
    private MockObject $container;

    private static $date = '2024-08-09';
    private static $mel = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $api = '/api/hotspots/show?';
    private static $httpError500 = 'Internal Server Error';

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->hotspotsRepository = $this->createMock(HotspotsRepository::class);
        $this->hotspotDetailsRepository = $this->createMock(HotspotDetailsRepository::class);
        $this->informationProjetRepository = $this->createMock(InformationProjetRepository::class);
        $this->serviceExtractName = $this->createMock(ExtractName::class);

        /** Stubbing la méthode getRepository pour retourner les mocks */
        $this->entityManager->method('getRepository')->willReturn($this->hotspotsRepository, $this->hotspotDetailsRepository, $this->informationProjetRepository);

        // Création du mock pour ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Instanciation du contrôleur
        $this->controller = new BatchCollecteHotspotDetailController($this->serviceExtractName, $this->entityManager, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testHotspotDetailHttpUnauthorizedError()
    {
        $queryParams = ['hotspot' => 'hotspotKey'];
        $expectedUrl = static::$api . http_build_query($queryParams);
        $mockErrorResponse = ['code' => 401, 'erreur' => 'UnAuthorized'];

        $this->client->method('httpSonarQube')->with($this->equalTo($expectedUrl))->willReturn($mockErrorResponse);
        $result = $this->controller->hotspotDetail('mavenKey', 'hotspotKey');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(401, $result['code']);
        $this->assertEquals('UnAuthorized', $result['erreur']);
    }

    public function testHotspotDetailHttpForbiddenError()
    {
        $queryParams = ['hotspot' => 'hotspotKey'];
        $expectedUrl = static::$api . http_build_query($queryParams);
        $mockErrorResponse = ['code' => 403, 'erreur' => 'Forbidden'];

        $this->client->method('httpSonarQube')->with($this->equalTo($expectedUrl))->willReturn($mockErrorResponse);
        $result = $this->controller->hotspotDetail('mavenKey', 'hotspotKey');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(403, $result['code']);
        $this->assertEquals('Forbidden', $result['erreur']);
    }

    public function testHotspotDetailHttpNotFoundError()
    {
        $queryParams = ['hotspot' => 'hotspotKey'];
        $expectedUrl = static::$api . http_build_query($queryParams);
        $mockErrorResponse = ['code' => 404, 'erreur' => 'Not found'];

        $this->client->method('httpSonarQube')->with($this->equalTo($expectedUrl))->willReturn($mockErrorResponse);
        $result = $this->controller->hotspotDetail('mavenKey', 'hotspotKey');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(404, $result['code']);
        $this->assertEquals('Not found', $result['erreur']);
    }

    public function testDetailHttpInternalServerError()
    {
        $queryParams = ['hotspot' => 'hotspotKey'];
        $expectedUrl = static::$api . http_build_query($queryParams);
        $mockErrorResponse = ['code' => 500, 'erreur' => static::$httpError500];

        $this->client->method('httpSonarQube')->with($this->equalTo($expectedUrl))->willReturn($mockErrorResponse);
        $result = $this->controller->hotspotDetail('mavenKey', 'hotspotKey');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
    }

    public function testHotspotDetailWhenDataIsMissing()
    {
        // Simuler une réponse sans les données attendues
        $this->client->method('httpSonarQube')
            ->willReturn([]);

        $result = $this->controller->hotspotDetail('mavenKey', 'hotspotKey');

        $this->assertEquals([
            'security_category' =>  'NC',
            'severity' => 'NC',
            'niveau' => 'NC',
            'status' => 'NC',
            'resolution' => 'NC',
            'frontend' => 'NC',
            'backend' => 'NC',
            'autre' => 'NC',
            'file_name' => 'NC',
            'file_path' => 'NC',
            'line' => 'NC',
            'rule_key' => 'NC',
            'rule_name' => 'NC',
            'message' => 'Aucune données trouvée pour ce projet.',
            'hotspot_key' => 'NC',
        ], $result);
    }

    public function testHotspotDetailWithValidData()
    {
        $this->client->method('httpSonarQube')
            ->willReturn(['json' => [
                            'rule' => [
                                'securityCategory' => 1,
                                'vulnerabilityProbability' => 'HIGH',
                                'key' => 'rule-123',
                                'name' => 'SQL Injection'
                            ],
                            'component' => [
                                'path' => 'assets/js/app-password.js',
                                'name' => 'app-password.js',
                                'key' => 'fr.ma-petite-entreprise:ma-moulinette:assets/js/app-password.js'
                            ],
                            'status' => 'OPEN',
                            'message' => 'This is a sample message',
                            'key' => 'hotspot-456',
                            'line' => 15
                        ]]);

        $result = $this->controller->hotspotDetail('mavenKey', 'hotspotKey');

        $this->assertEquals('HIGH', $result['severity']);
        $this->assertEquals(1, $result['niveau']);
        $this->assertEquals(1, $result['frontend']);
        $this->assertEquals(0, $result['backend']);
        $this->assertEquals(0, $result['autre']);
        $this->assertEquals('app-password.js', $result['file_name']);
        $this->assertEquals('assets/js/app-password.js', $result['file_path']);
        $this->assertEquals(15, $result['line']);
        $this->assertEquals('rule-123', $result['rule_key']);
        $this->assertEquals('SQL Injection', $result['rule_name']);
    }

    public function testHotspotDetailWithBackend()
    {
        $this->client->method('httpSonarQube')
            ->willReturn(['json' => [
                            'rule' => [
                                'securityCategory' => 2,
                                'vulnerabilityProbability' => 'MEDIUM',
                                'key' => 'rule-124',
                                'name' => 'SQL Injection2'
                            ],
                            'component' => [
                                'path' => 'controller/home/accueilController.php',
                                'name' => 'accueilController.php',
                                'key' => 'fr.ma-petite-entreprise:ma-moulinette:controller/home/accueilController.php'
                            ],
                            'status' => 'OPEN',
                            'message' => 'This is a sample message2',
                            'key' => 'hotspot-457',
                            'line' => 25
                        ]]);

        $result = $this->controller->hotspotDetail('mavenKey', 'hotspotKey');

        $this->assertEquals('MEDIUM', $result['severity']);
        $this->assertEquals(2, $result['niveau']);
        $this->assertEquals(0, $result['frontend']);
        $this->assertEquals(1, $result['backend']);
        $this->assertEquals(0, $result['autre']);
        $this->assertEquals('accueilController.php', $result['file_name']);
        $this->assertEquals('controller/home/accueilController.php', $result['file_path']);
        $this->assertEquals(25, $result['line']);
        $this->assertEquals('rule-124', $result['rule_key']);
        $this->assertEquals('SQL Injection2', $result['rule_name']);
    }


    public function testHotspotDetailWithNoPath()
    {
        $this->client->method('httpSonarQube')
            ->willReturn(['json' => [
                            'rule' => [
                                'securityCategory' => 3,
                                'vulnerabilityProbability' => 'LOW',
                                'key' => 'rule-223',
                                'name' => 'SQL Injection3'
                            ],
                            'component' => [
                                'name' => 'majCool.java',
                                'key' => 'fr.ma-petite-entreprise:ma-moulinette:batch/majCool.java'
                            ],
                            'status' => 'OPEN',
                            'message' => 'This is a sample message',
                            'key' => 'hotspot-556',
                            'line' => 35
                        ]]);

        $result = $this->controller->hotspotDetail('mavenKey', 'hotspotKey');

        $this->assertEquals('LOW', $result['severity']);
        $this->assertEquals(3, $result['niveau']);
        $this->assertEquals(0, $result['frontend']);
        $this->assertEquals(0, $result['backend']);
        $this->assertEquals(1, $result['autre']);
        $this->assertEquals('majCool.java', $result['file_name']);
        $this->assertEquals('fr.ma-petite-entreprise:ma-moulinette:batch/majCool.java', $result['file_path']);
        $this->assertEquals(35, $result['line']);
        $this->assertEquals('rule-223', $result['rule_key']);
        $this->assertEquals('SQL Injection3', $result['rule_name']);
    }


    public function testHotspotDetailWithSeverityCalculation()
    {
        // Simuler une réponse avec une probabilité de vulnérabilité
        $this->client->method('httpSonarQube')
            ->willReturn([
                'json' => [
                    'rule' => [
                        'securityCategory' => 3,
                        'vulnerabilityProbability' => 'LOW',
                        'name' => 'no-name',
                        'key' => '123'
                    ],
                    'component' => [
                        'path' => 'root/ts/password.ts',
                        'name' => 'no-name',
                        'key' => 'fr.x:tetris/ts/password.ts'

                        ],
                    'status' => 'OPEN',
                    'message' => 'message',
                    'key' => 'hotspot-x',
                    'line' => 1
                ]
            ]);

        $result = $this->controller->hotspotDetail('mavenKey', 'hotspotKey');
        $this->assertEquals('LOW', $result['severity']);
        $this->assertEquals(3, $result['niveau']);
    }

    public function testBatchCollecteHotspotDetailProjectInfoError(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 500, 'erreur' => static::$httpError500]);

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', static::$mel);

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
    }

    public function testBatchCollecteHotspotDetailProjectInfoEmpty(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'json' => ['info' => []]]);

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', static::$mel);

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(404, $result['code']);
        $this->assertEquals("Aucune information n'a été trouvée.", $result['message']);
    }

    public function testBatchCollecteHotspotDetailSelectProjetError(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => static::$date]]]);

        $this->hotspotsRepository
            ->method('selectHotspotsToReview')
            ->willReturn(['code' => 500, 'erreur' => static::$httpError500]);

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', static::$mel);

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
    }

    public function testBatchCollecteHotspotDetailDeleteError(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => static::$date]]]);

        $this->hotspotsRepository
            ->method('selectHotspotsToReview')
            ->willReturn(['code' => 200, 'liste' => [['hotspot_key' => 'hotspot-key-1']]]);

        $this->hotspotDetailsRepository
            ->method('deleteHotspotDetailsMavenKey')
            ->willReturn(['code' => 500, 'erreur' => static::$httpError500]);

        $this->client
            ->method('httpSonarQube')
            ->willReturn([
                'rule' => [
                    'securityCategory' => 'SECURITY',
                    'vulnerabilityProbability' => 'HIGH',
                    'key' => 'rule-key',
                    'name' => 'Rule Name2',
                ],
                'status' => 'TO_REVIEW',
                'component' => [
                    'name' => 'Component Name2',
                    'path' => 'Component Path2',
                ],
                'message' => 'Hotspot message2',
                'key' => 'hotspot-key-1',
                'line' => 42
            ]);

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', static::$mel);

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
    }

    public function testBatchCollecteHotspotDetailInsertError(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => static::$date]]]);

        $this->hotspotsRepository
            ->method('selectHotspotsToReview')
            ->willReturn(['code' => 200, 'liste' => [['hotspot_key' => 'hotspot-key-1']]]);

        $this->hotspotDetailsRepository
            ->method('deleteHotspotDetailsMavenKey')
            ->willReturn(['code' => 200]);

        $this->hotspotDetailsRepository
            ->method('insertHotspotDetails')
            ->willReturn(['code' => 500, 'erreur' => 'Erreur insertion']);

            $this->client
            ->method('httpSonarQube')
            ->willReturn([
                'rule' => [
                    'securityCategory' => 'SECURITY',
                    'vulnerabilityProbability' => 'HIGH',
                    'key' => 'rule-key',
                    'name' => 'Rule Name',
                ],
                'status' => 'TO_REVIEW',
                'component' => [
                    'name' => 'Component Name',
                    'path' => 'Component Path',
                ],
                'message' => 'Hotspot message',
                'key' => 'hotspot-key-1',
                'line' => 42
            ]);

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', static::$mel);

        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Erreur insertion', $result['erreur']);
    }

    public function testBatchCollecteHotspotDetailSuccess(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => static::$date]]]);

        $this->hotspotsRepository
            ->method('selectHotspotsToReview')
            ->willReturn(['code' => 200, 'liste' => [['hotspot_key' => 'hotspot-key-1']]]);

        $this->hotspotDetailsRepository->method('deleteHotspotDetailsMavenKey')->willReturn(['code' => 200]);

        $this->hotspotDetailsRepository->method('insertHotspotDetails')->willReturn(['code' => 200]);

        $this->client
            ->method('httpSonarQube')
            ->willReturn([
                    'rule' => [
                    'securityCategory' => 'SECURITY',
                    'vulnerabilityProbability' => 'HIGH',
                    'key' => 'rule-key',
                    'name' => 'Rule Name',
                ],
                'status' => 'TO_REVIEW',
                'component' => [
                    'name' => 'Component Name',
                    'path' => 'Component Path',
                ],
                'message' => 'Hotspot message',
                'key' => 'NC',
                'line' => 42
            ]);

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', static::$mel);

        $this->assertEquals(200, $result['code']);
        $this->assertCount(1, $result['message']);
        $this->assertArrayHasKey('hotspot_key', $result['message'][0]);
        $this->assertEquals('NC', $result['message'][0]['hotspot_key']);
    }

    public function testBatchCollecteHotspotDetailEmptyHotspots(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => static::$date]]]);

        $this->hotspotsRepository
            ->method('selectHotspotsToReview')
            ->willReturn(['code' => 200, 'liste' => []]);

        $this->hotspotDetailsRepository
            ->method('deleteHotspotDetailsMavenKey')
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', static::$mel);

        $this->assertEquals(406, $result['code']);
        $this->assertEquals('Liste vide !!!', $result['message']);
    }

}
