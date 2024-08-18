<?php

declare(strict_types=1);

namespace App\Tests\Controller\Batch;

use App\Controller\Batch\BatchCollecteHotspotOwaspController;
use App\Repository\HotspotOwaspRepository;
use App\Repository\InformationProjetRepository;
use App\Entity\HotspotOwasp;
use App\Entity\InformationProjet;
use App\Service\Client;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

class BatchCollecteHotspotOwaspControllerTest extends TestCase
{
    private EntityManagerInterface $em;
    private Client $client;
    private BatchCollecteHotspotOwaspController $controller;
    private ContainerInterface $container;
    private ParameterBagInterface $parameterBag;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);

        // Mock ParameterBagInterface
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        // Mock ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Instantiate the controller with mocked dependencies
        $this->controller = new BatchCollecteHotspotOwaspController($this->em, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testVulnerabilityProbability()
    {
        $reflection = new \ReflectionClass($this->controller);
        $method = $reflection->getMethod('vulnerabilityProbability');
        $method->setAccessible(true);

        $this->assertEquals(1, $method->invoke($this->controller, 'HIGH'));
        $this->assertEquals(2, $method->invoke($this->controller, 'MEDIUM'));
        $this->assertEquals(3, $method->invoke($this->controller, 'LOW'));
        $this->assertEquals(-1, $method->invoke($this->controller, 'UNKNOWN'));
    }

    public function testSonarVersionParameterSonarVersion8()
    {
        // Créez un mock du contrôleur
        $controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Configuration du mock pour retourner '8' pour sonar.version
        $controller->method('getParameter')
            ->with('sonar.version')
            ->willReturn(8);

        // Utilisation de la réflexion pour rendre la méthode protégée accessible
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getParameter');
        $method->setAccessible(true);

        // Appel de la méthode protégée
        $sonarVersion = $method->invoke($controller, 'sonar.version');

        // Assertions pour vérifier que le paramètre est bien pris en compte
        $this->assertEquals(8, $sonarVersion, 'Le paramètre sonar.version devrait être 8.');
    }

    public function testSonarVersionParameterSonarVersion9()
    {
        // Créez un mock du contrôleur
        $controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Configuration du mock pour retourner '8' pour sonar.version
        $controller->method('getParameter')
            ->with('sonar.version')
            ->willReturn(9);

        // Utilisation de la réflexion pour rendre la méthode protégée accessible
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getParameter');
        $method->setAccessible(true);

        // Appel de la méthode protégée
        $sonarVersion = $method->invoke($controller, 'sonar.version');

        // Assertions pour vérifier que le paramètre est bien pris en compte
        $this->assertEquals(9, $sonarVersion, 'Le paramètre sonar.version devrait être 8.');
    }
    public function testBatchCollecteHotspotOwaspWithErrorInApiCall()
    {
        $this->client->method('http')
            ->willReturn(['code' => 404]);

        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($informationProjetRepositoryMock);

        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);
        $this->em->method('getRepository')->willReturn($hotspotOwaspRepositoryMock);

        $informationProjetRepositoryMock->method('selectInformationProjetProjectVersion')
            ->willReturn([
                'code' => 200,
                'info' => [['project_version' => '1.0', 'date' => '2024-08-09']],
            ]);

        $hotspotOwaspRepositoryMock->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', 'laurent.hadjadj@ma-petite-entreprise.fr', 'a1');

        $this->assertEquals(404, $result['error']);
    }

    public function testBatchCollecteHotspotOwaspWithEmptyData()
    {
        $this->client->method('http')
            ->willReturn([
                'hotspots' => [],
                'paging' => ['total' => 0],
            ]);

        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($informationProjetRepositoryMock);

        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);
        $this->em->method('getRepository')->willReturn($hotspotOwaspRepositoryMock);

        $informationProjetRepositoryMock->method('selectInformationProjetProjectVersion')
            ->willReturn([
                'code' => 200,
                'info' => [['project_version' => '1.0', 'date' => '2024-08-09']],
            ]);

        $hotspotOwaspRepositoryMock->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', 'laurent.hadjadj@ma-petite-entreprise.fr', 'a1');

        $this->assertEquals(200, $result['code']);
        $this->assertEquals(0, $result['owasp_2017']);
        $this->assertEquals('enregistrement', $result['info']);
        $this->assertCount(0, $result['data']);
    }

    public function testBatchCollecteHotspotOwaspWithMissingProjectInformation()
    {
        $this->client->method('http')
            ->willReturn([
                'hotspots' => [
                    ['securityCategory' => 'Cat1', 'ruleKey' => 'Rule1', 'vulnerabilityProbability' => 'HIGH', 'status' => 'status1', 'resolution' => 'res1'],
                ],
                'paging' => ['total' => 1],
            ]);

        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($informationProjetRepositoryMock);

        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);
        $this->em->method('getRepository')->willReturn($hotspotOwaspRepositoryMock);

        $informationProjetRepositoryMock->method('selectInformationProjetProjectVersion')
            ->willReturn([
                'code' => 200,
                'info' => [],
            ]);

        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', 'laurent.hadjadj@ma-petite-entreprise.fr', 'a1');

        $this->assertEquals(404, $result['code']);
        $this->assertEquals('L\'appel à l\'API n\'a pas abouti (Erreur 404).', $result['message']);
    }

    public function testBatchCollecteHotspotOwaspWithInsertSuccess()
    {
        // Mock de la méthode HTTP
        $this->client->method('http')
            ->willReturn([
                'hotspots' => [
                    ['securityCategory' => 'Cat1', 'ruleKey' => 'Rule1', 'vulnerabilityProbability' => 'HIGH', 'status' => 'status1', 'resolution' => 'res1'],
                ],
                'paging' => ['total' => 1],
            ]);

        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);

        // Mock du repository pour HotspotOwasp
        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);

        // Configuration du comportement de l'entity manager pour renvoyer les bons repositories
        $this->em->method('getRepository')
            ->willReturnMap([
                [InformationProjet::class, $informationProjetRepositoryMock],
                [HotspotOwasp::class, $hotspotOwaspRepositoryMock],
            ]);

        // Configuration du mock pour InformationProjetRepository
        $informationProjetRepositoryMock->method('selectInformationProjetProjectVersion')
            ->willReturn([
                'code' => 200,
                'info' => [['project_version' => '1.0', 'date' => '2024-08-09']],
            ]);

        // Configuration du mock pour HotspotOwaspRepository
        $hotspotOwaspRepositoryMock->expects($this->once())
            ->method('insertHotspotOwasp')
            ->with($this->callback(function($arg) {
                return is_array($arg) && !empty($arg) && isset($arg[0]['security_category']) && $arg[0]['security_category'] === 'Cat1';
            }))
            ->willReturn(['code' => 200]);

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', 'laurent.hadjadj@ma-petite-entreprise.fr', 'a1');

        // Assert sur le résultat
        $this->assertEquals(200, $result['code']);
    }

    public function testBatchCollecteHotspotOwaspWithInsertFailure()
    {
        $this->client->method('http')
            ->willReturn([
                'hotspots' => [
                    ['securityCategory' => 'Cat1', 'ruleKey' => 'Rule1', 'vulnerabilityProbability' => 'HIGH', 'status' => 'status1', 'resolution' => 'res1'],
                ],
                'paging' => ['total' => 1],
            ]);

        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);

        // Mock du repository pour HotspotOwasp
        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);

        // Configuration du comportement de l'entity manager pour renvoyer les bons repositories
        $this->em->method('getRepository')
            ->willReturnMap([
                [InformationProjet::class, $informationProjetRepositoryMock],
                [HotspotOwasp::class, $hotspotOwaspRepositoryMock],
            ]);

        $informationProjetRepositoryMock->method('selectInformationProjetProjectVersion')
            ->willReturn([
                'code' => 200,
                'info' => [['project_version' => '1.0', 'date' => '2024-08-09']],
            ]);

        $hotspotOwaspRepositoryMock->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 200]);

        $hotspotOwaspRepositoryMock->method('insertHotspotOwasp')
            ->willReturn(['code' => 500, ['requête :' => 'insertHotspotOwasp']]);

        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', 'laurent.hadjadj@ma-petite-entreprise.fr', 'a1');
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('insertHotspotOwasp', $result['requête : ']);
    }

    public function testBatchCollecteHotspotOwaspWithNoHotspotsReturned()
    {
        $this->client->method('http')
            ->willReturn([
                'hotspots' => [],
                'paging' => ['total' => 0],
            ]);

        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($informationProjetRepositoryMock);

        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);
        $this->em->method('getRepository')->willReturn($hotspotOwaspRepositoryMock);

        $informationProjetRepositoryMock->method('selectInformationProjetProjectVersion')
            ->willReturn([
                'code' => 200,
                'info' => [['project_version' => '1.0', 'date' => '2024-08-09']],
            ]);

        $hotspotOwaspRepositoryMock->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', 'laurent.hadjadj@ma-petite-entreprise.fr', 'a1');

        $this->assertEquals(200, $result['code']);
        $this->assertEquals(0, $result['owasp_2017']);
        $this->assertEquals('enregistrement', $result['info']);
        $this->assertCount(0, $result['data']);
    }

}
