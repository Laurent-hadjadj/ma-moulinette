<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteInformationProjetController;
use App\service\ClientService;
use App\Service\IsValideMavenKey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

/**
 * [Description BatchCollecteInformationProjetControllerTest]
 */
class BatchCollecteInformationProjetControllerTest extends TestCase
{
    private IsValideMavenKey $isValidMavenKey;
    private EntityManagerInterface $em;
    private ClientService $client;
    private BatchCollecteInformationProjetController $controller;
    private ContainerInterface $container;
    private ParameterBagInterface $parameterBag;

    private static $projectVersion = '1.0.0';
    private static $date = '2024-08-09';
    private static $httpError500 = 'Internal server error';

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->isValidMavenKey = $this->createMock(IsValideMavenKey::class);

        // Mock ParameterBagInterface
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->parameterBag->method('get')->with('sonar.url')->willReturn('http://localhost');

        // Mock ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Instantiate the controller with mocked dependencies
        $this->controller = new BatchCollecteInformationProjetController($this->em, $this->isValidMavenKey, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testControlVersionProjetSuccess()
    {
        $mavenKey = 'some-maven-key';

        // Simule une réponse valide du service HTTP
        $sonarResult = [
            "code" => 200,
            "json" => [
                "paging" => [
                    "pageIndex" => 1,
                    "pageSize" => 100,
                    "total" => 7
                ],
            "analyses" =>  [
                [
                    "key" => "AZHg16-k35m6uta00gtR",
                    "date" => "2024-09-11T13:27:21+0200",
                    "events" => [
                        [
                            "key" => "AZHg170r35m6uta00kXs",
                            "category" => "VERSION",
                            "name" => "4.2.1-RELEASE",
                        ],
                        [
                            "key" => "AZHg170p35m6uta00kXr",
                            "category" => "QUALITY_PROFILE",
                            "name" => "Changes in 'Profil v2.0.0 (2021)' (Java)",
                        ]
                    ],
                    "projectVersion" => "4.2.1-RELEASE",
                    "manualNewCodePeriodBaseline" => false
                ]
            ]]];

        // Simule les retours du client HTTP
        $this->client->method('httpSonarQube')->willReturn($sonarResult);

        // Simule les retours des méthodes de validation Maven
        $this->isValidMavenKey->method('isValideInformation')->willReturn([
            'code' => 200,
            'request' => ['version' => static::$projectVersion]
        ]);

        $this->isValidMavenKey->method('isValideHistorique')->willReturn([
            'code' => 200,
            'request' => ['version' => static::$projectVersion]
        ]);

        // Exécute la méthode à tester
        $result = $this->controller->controlVersionProjet($mavenKey);

        $this->assertEquals(200, $result['code']);
        $this->assertEquals("Le projet est présent en base et sur le serveur", $result['message']);
        $this->assertArrayHasKey('data-sonarqube', $result);
        $this->assertArrayHasKey('data-baseInformation', $result);
        $this->assertArrayHasKey('data-baseHistorique', $result);
    }

    public function testControlVersionProjetNotInBase()
    {
        $mavenKey = 'some-maven-key';

        // Simule une réponse valide du service HTTP
        $sonarResult = [
            "code" => 200,
            "json" => [
                "paging" => [
                    "pageIndex" => 1,
                    "pageSize" => 100,
                    "total" => 7
                ],
            "analyses" =>  [
                [
                    "key" => "AZHg16-k35m6uta00gtR",
                    "date" => "2024-09-11T13:27:21+0200",
                    "events" => [
                        [
                            "key" => "AZHg170r35m6uta00kXs",
                            "category" => "VERSION",
                            "name" => "4.2.1-RELEASE",
                        ],
                        [
                            "key" => "AZHg170p35m6uta00kXr",
                            "category" => "QUALITY_PROFILE",
                            "name" => "Changes in 'Profil v2.0.0 (2021)' (Java)",
                        ]
                    ],
                    "projectVersion" => "4.2.1-RELEASE",
                    "manualNewCodePeriodBaseline" => false
                ]
            ]]];

        // Simule les retours du client HTTP
        $this->client->method('httpSonarQube')->willReturn($sonarResult);

        // Simule les retours des méthodes de validation Maven
        $this->isValidMavenKey->method('isValideInformation')->willReturn([
            'code' => 404,
            'request' => []
        ]);

        $this->isValidMavenKey->method('isValideHistorique')->willReturn([
            'code' => 404,
            'request' => []
        ]);

        // Exécute la méthode à tester
        $result = $this->controller->controlVersionProjet($mavenKey);

        $this->assertEquals(202, $result['code']);
        $this->assertEquals("Le projet est présent en base mais pas sur le serveur.", $result['message']);
        $this->assertArrayHasKey('data-sonarqube', $result);
        $this->assertArrayHasKey('data-baseInformation', $result);
        $this->assertArrayHasKey('data-baseHistorique', $result);
    }

    public function testControlVersionProjetNotFound()
    {
        $mavenKey = 'some-maven-key';
        $this->client->method('httpSonarQube')->willReturn(['code' => 404]);

        // Simule les retours des méthodes de validation Maven en base de données
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => '']);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 200, 'request' => '']);

        /* On a un code 404 et pas de réponse json */
        $mockResponse = ['code' => 404];
        $isFound = isset($mockResponse['json']) ? true : false;
        $isNotFound  = ($mockResponse['code'] == 404) ? true : false;

        $result = $this->controller->controlVersionProjet($mavenKey);

        /** tests */
        $this->assertEquals(404, $mockResponse['code']);
        $this->assertFalse($isFound, 'IsFound doit ête false.');
        $this->assertTrue($isNotFound, 'IsNotFound est égale à 404');
        $this->assertEquals(404, $result['code']);
        $this->assertEquals("Le projet n'existe pas sur le serveur SonarQube (Erreur 404).", $result['message']);
    }

    public function testControlVersionProjetUnauthorized()
    {
        $mavenKey = 'some-maven-key';
        $this->client->method('httpSonarQube')->willReturn(['code' => 401]);

        // Simule les retours des méthodes de validation Maven en base de données
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => '']);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 200, 'request' => '']);

        /* On a un code 401 et pas de réponse json */
        $mockResponse = ['code' => 401];
        $isFound = isset($mockResponse['json']) ? true : false;
        $isNotAuthorize  = ($mockResponse['code'] == 401) ? true : false;

        $result = $this->controller->controlVersionProjet($mavenKey);

        /** tests */
        $this->assertEquals(401, $mockResponse['code']);
        $this->assertFalse($isFound, 'IsFound doit ête false.');
        $this->assertTrue($isNotAuthorize, 'IsNotAuthorize est égale à 401');
        $this->assertEquals(401, $result['code']);
        $this->assertEquals("Le serveur SonarQube n'autorise pas l'utilisateur à se connecter à cette API (Erreur 401).", $result['message']);
    }

    public function testControlVersionProjetUnAvailable()
    {
        $mavenKey = 'some-maven-key';
        $this->client->method('httpSonarQube')->willReturn(['code' => 503]);

        // Simule les retours des méthodes de validation Maven en base de données
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => '']);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 200, 'request' => '']);

        /* On a un code 503 et pas de réponse json */
        $mockResponse = ['code' => 503];
        $isFound = isset($mockResponse['json']) ? true : false;
        $isNotAvailable = ($mockResponse['code'] == 503) ? true : false;

        $result = $this->controller->controlVersionProjet($mavenKey);

        /** tests */
        $this->assertEquals(503, $mockResponse['code']);
        $this->assertFalse($isFound, 'IsFound doit ête false');
        $this->assertTrue($isNotAvailable, 'IsNotAvailable est égale à 503');
        $this->assertEquals(503, $result['code']);
        $this->assertEquals("La résolution DNS n'a pas permis d'accéder au serveur SonarQube (Erreur 503).", $result['message']);
    }

    public function testControlVersionProjetGenericError()
    {
        $mavenKey = 'some-maven-key';
        $this->client->method('httpSonarQube')->willReturn(['code' => 200]);

        // Simule les retours des méthodes de validation Maven en base de données
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200, 'request' => '']);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 200, 'request' => '']);

        /* On a un code 401 et pas de réponse json */
        $mockResponse = ['code' => 500,];
        $isFound = isset($mockResponse['json']) ? true : false;
        $isNotAuthorize = ($mockResponse['code'] == 401) ? true : false;
        $isNotNotFound = ($mockResponse['code'] == 404) ? true : false;
        $isNotAvailable = ($mockResponse['code'] == 503) ? true : false;

        $result = $this->controller->controlVersionProjet($mavenKey);

        /** tests */
        $this->assertEquals(500, $mockResponse['code']);
        $this->assertFalse($isFound, 'IsFound doit ête false');
        $this->assertFalse($isNotAuthorize, 'IsNotAuthorize est égale à 401');
        $this->assertFalse($isNotNotFound, 'IsNotFound est égale à 404');
        $this->assertFalse($isNotAvailable, 'IsNotAvailable est égale à 503');
        $this->assertEquals(500, $result['code']);
        $this->assertEquals("Une erreur inattendue est survenue (Erreur500).", $result['message']);
    }

    public function testBatchInformationVersionCountAllProjet()
    {
        $mavenKey = 'some-maven-key';

        $repositoryMock = $this->createMock(\App\Repository\InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($repositoryMock);

        // Simuler les résultats des différentes méthodes du repository
        $repositoryMock->method('countInformationProjetAllType')->willReturn([
            'code' => 500, 'erreur' => static::$httpError500]);

        // Utilisation de la réflexion pour appeler la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('batchInformationVersion');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $mavenKey);

        // Vérifier les résultats
        $this->assertEquals(['code' => 500, 'erreur' => static::$httpError500], $result);
    }

    public function testBatchInformationVersionCountAllRelease()
    {
        $mavenKey = 'some-maven-key';

        $repositoryMock = $this->createMock(\App\Repository\InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($repositoryMock);

        $repositoryMock->method('countInformationProjetAllType')->willReturn([
            'code' => 200,
            'nombre' => [['total' => 10]]
        ]);

        // Simuler les résultats des différentes méthodes du repository
        $repositoryMock->method('countInformationProjetType')->willReturn([
            'code' => 500, 'erreur' => static::$httpError500]);

        // Utilisation de la réflexion pour appeler la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('batchInformationVersion');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $mavenKey);

        // Vérifier les résultats
        $this->assertEquals(['code' => 500, 'erreur' => static::$httpError500], $result);
    }

    public function testBatchInformationVersionCountAllSnapshot()
    {
        $mavenKey = 'some-maven-key';

        $repositoryMock = $this->createMock(\App\Repository\InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($repositoryMock);

        $repositoryMock->method('countInformationProjetAllType')->willReturn([
            'code' => 200,
            'nombre' => [['total' => 10]]
        ]);

        $repositoryMock->expects($this->exactly(2))
        ->method('countInformationProjetType')
        ->willReturnCallback(function ($map) use ($mavenKey) {
            if (strtoupper($map['type']) === 'RELEASE') {
                $this->assertEquals($mavenKey, $map['maven_key']);
                return [
                    'code' => 200,
                    'nombre' => [['total' => 10]],
                ];
            } elseif (strtoupper($map['type']) === 'SNAPSHOT') {
                $this->assertEquals($mavenKey, $map['maven_key']);
                return ['code' => 500, 'erreur' => static::$httpError500];
            }
            return null;
        });

        // Utilisation de la réflexion pour appeler la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('batchInformationVersion');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $mavenKey);

        // Vérifier les résultats
        $this->assertEquals(['code' => 500, 'erreur' => static::$httpError500], $result);
    }

    public function testBatchInformationVersionLast()
    {
        $mavenKey = 'some-maven-key';

        $repositoryMock = $this->createMock(\App\Repository\InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($repositoryMock);

        $repositoryMock->method('countInformationProjetAllType')->willReturn([
            'code' => 200,
            'nombre' => [['total' => 10]]
        ]);

        $repositoryMock->method('countInformationProjetType')->willReturnMap([
            [['maven_key' => $mavenKey, 'type' => 'RELEASE'], ['code' => 200, 'nombre' => [['total' => 5]]]],
            [['maven_key' => $mavenKey, 'type' => 'SNAPSHOT'], ['code' => 200, 'nombre' => [['total' => 2]]]]
        ]);
        $repositoryMock->method('selectInformationProjetVersionLast')->willReturn(['code' => 500, 'erreur' => static::$httpError500]);
        // Utilisation de la réflexion pour appeler la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('batchInformationVersion');
        $method->setAccessible(true);

        $result = $method->invoke($this->controller, $mavenKey);

        // Vérifier les résultats
        $this->assertEquals(['code' => 500, 'erreur' => static::$httpError500], $result);
    }

    public function testBatchInformationVersionSuccess()
    {
        $mavenKey = 'some-maven-key';

        $repositoryMock = $this->createMock(\App\Repository\InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($repositoryMock);

        // Simuler les résultats des différentes méthodes du repository
        $repositoryMock->method('countInformationProjetAllType')->willReturn([
            'code' => 200,
            'nombre' => [['total' => 10]]
        ]);
        $repositoryMock->method('countInformationProjetType')->willReturnMap([
            [['maven_key' => $mavenKey, 'type' => 'RELEASE'], ['code' => 200, 'nombre' => [['total' => 5]]]],
            [['maven_key' => $mavenKey, 'type' => 'SNAPSHOT'], ['code' => 200, 'nombre' => [['total' => 2]]]]
        ]);
        $repositoryMock->method('selectInformationProjetVersionLast')->willReturn([
            'code' => 200,
            'version' => [['analyse_key' => 'key', 'projet' => static::$projectVersion, 'date' => static::$date]]
        ]);

        // Utilisation de la réflexion pour appeler la méthode privée
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('batchInformationVersion');
        $method->setAccessible(true);
        $result = $method->invoke($this->controller, $mavenKey);

        // Vérifier les résultats
        $this->assertEquals([
            'analyse_key' => 'key',
            'release' => 5,
            'snapshot' => 2,
            'autre' => 3,
            'projet' => static::$projectVersion,
            'date' => static::$date,
        ], $result);
    }

    public function testBatchCollecteInformationWhenControlVersionProjetFails()
    {
        $mavenKey = 'test-maven-key';

        // Mock de la méthode controlVersionProjet pour renvoyer une erreur
        $this->controller = $this->getMockBuilder(BatchCollecteInformationProjetController::class)
            ->setConstructorArgs([$this->em, $this->isValidMavenKey, $this->client])
            ->onlyMethods(['controlVersionProjet'])
            ->getMock();

        $this->controller->method('controlVersionProjet')
            ->with($mavenKey)
            ->willReturn([
                'code' => 404,
                'message' => 'Project not found'
            ]);

        // Appeler la méthode batchCollecteInformation
        $result = $this->controller->batchCollecteInformation($mavenKey, 'COLLECTE', 'test-user');

        // Assertions
        $this->assertEquals([
            'code' => 404,
            'message' => 'Project not found'
        ], $result);
    }

    public function testBatchCollecteInformationExtractsSonarQubeData()
    {
        $mavenKey = 'test-maven-key';

        // Mock du repository InformationProjet
        $informationProjetRepositoryMock = $this->createMock(\App\Repository\InformationProjetRepository::class);
        $this->em->method('getRepository')
            ->with(\App\Entity\InformationProjet::class)
            ->willReturn($informationProjetRepositoryMock);

        // Mock des méthodes du repository
        $informationProjetRepositoryMock->method('deleteInformationProjetMavenKey')
            ->willReturn(['code' => 200]);

        $informationProjetRepositoryMock->method('insertInformationProjet')
            ->willReturn(['code' => 200]);

        // Mock du contrôleur avec les deux méthodes
        $this->controller = $this->getMockBuilder(BatchCollecteInformationProjetController::class)
            ->setConstructorArgs([$this->em, $this->isValidMavenKey, $this->client])
            ->onlyMethods(['controlVersionProjet', 'batchInformationVersion'])
            ->getMock();

        // Mocker la méthode controlVersionProjet
        $this->controller->method('controlVersionProjet')
            ->with($mavenKey)
            ->willReturn([
                'code' => 200,
                'data-sonarqube' => [
                    'json' => [
                        'analyses' => [
                            [
                                'projectVersion' => '1.3.3',
                                'date' => '2024-01-15T12:34:56Z',
                                'key' => 'sonarqube-analysis-key'
                            ]
                        ]
                    ]
                ]
            ]);

        // Mocker la méthode batchInformationVersion
        $this->controller->method('batchInformationVersion')
            ->with($mavenKey)
            ->willReturn([
                'code' => 200,
                'analyse_key' => 'test-analysis-key',
                'release' => 2,
                'snapshot' => 0,
                'autre' => 'N.C',
                'projet' => '1.3.3',
                'date' => '2025-01-15T12:34:56Z'
            ]);

        // Appeler la méthode batchCollecteInformation
        $result = $this->controller->batchCollecteInformation($mavenKey, 'COLLECTE', 'test-user');

        // Assertions sur la sortie
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(200, $result['code']);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);

        // Vérification des données extraites de SonarQube
        $sonarQubeData = $result['data'];
        $this->assertEquals('test-analysis-key', $sonarQubeData['analyse_key']); // Adapter ici
        $this->assertEquals('1.3.3', $sonarQubeData['version']);
        $this->assertEquals('2025-01-15T12:34:56Z', $sonarQubeData['date_version']);
    }

    public function testBatchCollecteInformationHandlesDeleteError()
    {
        $mavenKey = 'test-maven-key';
        $modeCollecte = 'COLLECTE';
        $utilisateurCollecte = 'test-user';

        // Création du mock pour le repository
        $informationProjetRepositoryMock = $this->createMock(\App\Repository\InformationProjetRepository::class);

        // Simuler le retour de deleteInformationProjetMavenKey
        $informationProjetRepositoryMock->method('deleteInformationProjetMavenKey')
            ->willReturn([
                'code' => 500,
                'erreur' => 'Erreur de suppression'
            ]);

        // Mock de la méthode controlVersionProjet
        $this->controller = $this->getMockBuilder(BatchCollecteInformationProjetController::class)
            ->setConstructorArgs([$this->em, $this->isValidMavenKey, $this->client])
            ->onlyMethods(['controlVersionProjet'])
            ->getMock();

        // Simuler le retour de controlVersionProjet pour des données valides
        $this->controller->method('controlVersionProjet')
            ->with($mavenKey)
            ->willReturn([
                'code' => 200,
                'message' => 'OK',
                'data-sonarqube' => [
                    'json' => [
                        'analyses' => [
                            [
                                'projectVersion' => '1.3.3',
                                'date' => '2024-01-15T12:34:56Z',
                                'key' => 'sonarqube-analysis-key'
                            ]
                        ]
                    ]
                ]
            ]);

        // Mock du service EntityManager pour qu'il retourne le repository mock
        $this->em->method('getRepository')
            ->with(\App\Entity\InformationProjet::class)
            ->willReturn($informationProjetRepositoryMock);

        // Appel à la méthode batchCollecteInformation avec les paramètres requis
        $result = $this->controller->batchCollecteInformation($mavenKey, $modeCollecte, $utilisateurCollecte);

        // Assertions pour vérifier la gestion de l'erreur de suppression
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals('Erreur de suppression', $result['erreur']);
    }

    public function testBatchCollecteInformationUpToDate()
    {
        $mavenKey = 'test-maven-key';
        $modeCollecte = 'Traitement Manuel';
        $utilisateurCollecte = 'test-user';

         // Mock de la méthode controlVersionProjet
        $this->controller = $this->getMockBuilder(BatchCollecteInformationProjetController::class)
            ->setConstructorArgs([$this->em, $this->isValidMavenKey, $this->client])
            ->onlyMethods(['controlVersionProjet', 'batchInformationVersion'])
            ->getMock();

        // Mock de controlVersionProjet pour simuler un code 200 (différent de 202)
        $this->controller->method('controlVersionProjet')
            ->with($mavenKey)
            ->willReturn([
                'code' => 200,
                'message' => 'Valid project',
                'data-sonarqube' => [
                    'json' => [
                        'analyses' => [
                            [
                                'projectVersion' => '1.3.3',
                                'date' => '2024-01-15T12:34:56Z',
                                'key' => 'SonarQube-analysis-key'
                            ]
                        ]
                    ]
                ],
                'data-baseHistorique' => [
                    'version' => '1.2.0',
                    'date_version' => '2023-12-01',
                    'analyse_key' => 'SonarQube-analysis-key',
                    'name' => 'project-name'
                ]
            ]);

        // Simuler la réponse de batchInformationVersion
        $this->controller->method('batchInformationVersion')
        ->with($mavenKey)
        ->willReturn([
            'code' => 200,
            'analyse_key' => 'test-analysis-key',
            'release' => 2,
            'snapshot' => 0,
            'autre' => 'N.C',
            'projet' => '1.4.3',
            'date' => '2025-01-15T12:34:56Z'
        ]);

        // Mock du repository InformationProjet
        $informationProjetRepositoryMock = $this->createMock(\App\Repository\InformationProjetRepository::class);
        $this->em->method('getRepository')
            ->with(\App\Entity\InformationProjet::class)
            ->willReturn($informationProjetRepositoryMock);

        // Mock des méthodes du repository
        $informationProjetRepositoryMock->method('deleteInformationProjetMavenKey')
            ->willReturn(['code' => 200]);

        $informationProjetRepositoryMock->method('insertInformationProjet')
            ->willReturn(['code' => 200]);

        // Appeler la méthode batchCollecteInformation
        $result = $this->controller->batchCollecteInformation($mavenKey, $modeCollecte, $utilisateurCollecte);

        // Assertions sur le résultat
        $this->assertArrayHasKey('code', $result);
        $this->assertEquals(100, $result['code']);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals('Le projet est à jour', $result['message']);

        // Vérification des données retournées
        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('SonarQube', $result['data']);
        $this->assertArrayHasKey('Locale', $result['data']);
        $this->assertEquals('1.2.0', $result['data']['Locale']['version']);
        $this->assertEquals('SonarQube-analysis-key', $result['data']['Locale']['key-analyse']);
    }

    public function testBatchCollecteInformationHandlesInsertError()
{
    $mavenKey = 'test-maven-key';

    // Mock du contrôleur
    $this->controller = $this->getMockBuilder(BatchCollecteInformationProjetController::class)
        ->setConstructorArgs([$this->em, $this->isValidMavenKey, $this->client])
        ->onlyMethods(['controlVersionProjet', 'batchInformationVersion'])
        ->getMock();

    // Simuler le retour de controlVersionProjet avec des données valides
    $this->controller->method('controlVersionProjet')
        ->with($mavenKey)
        ->willReturn([
            'code' => 200,
            'data-sonarqube' => [
                'json' => [
                    'analyses' => [
                        [
                            'projectVersion' => '1.3.3',
                            'date' => '2024-01-15T12:34:56Z',
                            'key' => 'sonarqube-analysis-key'
                        ]
                    ]
                ]
            ]
        ]);

    // Simuler le retour de batchInformationVersion
    $this->controller->method('batchInformationVersion')
        ->with($mavenKey)
        ->willReturn([
            'code' => 200,
            'analyse_key' => 'test-analysis-key',
            'release' => 2,
            'snapshot' => 0,
            'autre' => 'N.C',
            'projet' => '1.4.3',
            'date' => '2025-01-15T12:34:56Z'
        ]);

    // Mock du repository InformationProjet
    $informationProjetRepositoryMock = $this->createMock(\App\Repository\InformationProjetRepository::class);
    $this->em->method('getRepository')
        ->with(\App\Entity\InformationProjet::class)
        ->willReturn($informationProjetRepositoryMock);

    // Simuler la suppression réussie
    $informationProjetRepositoryMock->method('deleteInformationProjetMavenKey')
        ->willReturn(['code' => 200]);

    // Simuler un échec lors de l'insertion
    $informationProjetRepositoryMock->method('insertInformationProjet')
        ->willReturn([
            'code' => 500,
            'erreur' => 'Erreur lors de l\'insertion des données'
        ]);

    // Appeler la méthode batchCollecteInformation
    $result = $this->controller->batchCollecteInformation($mavenKey, 'COLLECTE', 'test-user');

    // Assertions sur la sortie
    $this->assertArrayHasKey('code', $result);
    $this->assertEquals(500, $result['code']);
    $this->assertArrayHasKey('erreur', $result);
    $this->assertEquals('Erreur lors de l\'insertion des données', $result['erreur']);

}

}
