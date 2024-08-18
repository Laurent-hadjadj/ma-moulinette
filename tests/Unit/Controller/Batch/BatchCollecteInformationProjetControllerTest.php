<?php

declare(strict_types=1);

namespace App\Tests\Controller\Batch;

use App\Controller\Batch\BatchCollecteInformationProjetController;
use App\Service\Client;
use App\Service\IsValideMavenKey;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class BatchCollecteInformationProjetControllerTest extends TestCase
{
    private IsValideMavenKey $isValidMavenKey;
    private EntityManagerInterface $em;
    private Client $client;
    private BatchCollecteInformationProjetController $controller;
    private ContainerInterface $container;
    private ParameterBagInterface $parameterBag;

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

    public function testControleVersionProjetSuccess()
    {
        $mavenKey = 'some-maven-key';

        // Simule une réponse valide du service HTTP
        $sonarResult = [
            'analyses' => [
                ['projectVersion' => '1.0.0', 'date' => '2024-08-09', 'key' => 'key']
            ]
        ];

        // Simule les retours du client HTTP
        $this->client->method('http')->willReturn($sonarResult);
        // Simule les retours des méthodes de validation Maven
        $this->isValidMavenKey->method('isValideInformation')->willReturn([
            'code' => 200,
            'request' => ['version' => '1.0.0']
        ]);

        $this->isValidMavenKey->method('isValideHistorique')->willReturn([
            'code' => 200,
            'request' => ['version' => '1.0.0']
        ]);

        // Exécute la méthode à tester
        $result = $this->controller->controleVersionProjet($mavenKey);
        $this->assertEquals(200, $result['code']);
        $this->assertEquals("Le projet est présent en base et sur le serveur", $result['message']);
        $this->assertArrayHasKey('data-sonarqube', $result);
        $this->assertArrayHasKey('data-baseInformation', $result);
        $this->assertArrayHasKey('data-baseHistorique', $result);
    }

    public function testControleVersionProjetNotFound()
    {
        $mavenKey = 'some-maven-key';
        $this->client->method('http')->willReturn(['code' => 404]);

        // Simule les retours des méthodes de validation Maven
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 404]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 404]);

        // Exécute la méthode à tester
        $result = $this->controller->controleVersionProjet($mavenKey);

        // Assertions pour vérifier que tout s'est passé comme prévu
        $this->assertEquals(404, $result['code']);
        $this->assertEquals("Le projet n'existe pas sur le serveur SonarQube", $result['message']);
    }

    public function testControleVersionProjetUnauthorized()
    {
        $mavenKey = 'some-maven-key';
        $this->client->method('http')->willReturn(['code' => 401]);

        // Simule les retours des méthodes de validation Maven
        $this->isValidMavenKey->method('isValideInformation')->willReturn(['code' => 200]);
        $this->isValidMavenKey->method('isValideHistorique')->willReturn(['code' => 200]);

        $result = $this->controller->controleVersionProjet($mavenKey);

        $this->assertEquals(401, $result['code']);
        $this->assertEquals("Le serveur SonarQube n'autorise pas l'utilisateur à se connecter à cette API.", $result['message']);
    }

    public function testBatchInformationVersion()
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
            'version' => [['analyse_key' => 'key', 'projet' => '1.0.0', 'date' => '2024-08-09']]
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
            'projet' => '1.0.0',
            'date' => '2024-08-09',
        ], $result);
    }

}
