<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteHotspotOwaspController;
use App\Repository\HotspotOwaspRepository;
use App\Repository\InformationProjetRepository;
use App\Entity\HotspotOwasp;
use App\Entity\InformationProjet;
use App\Service\ClientService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Container\ContainerInterface;
use PHPUnit\Framework\TestCase;

/**
 * [Description BatchCollecteHotspotOwaspControllerTest]
 */
class BatchCollecteHotspotOwaspControllerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ClientService $client;
    private BatchCollecteHotspotOwaspController $controller;
    private ContainerInterface $container;
    private ParameterBagInterface $parameterBag;

    private static $date = '2024-08-09';
    private static $mel = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $messageA0 = 'A0 : Effacement des données de la table hotspotOwasp pour le projet.';
    private static $httpError500 = 'Internal server error (Erreur 500).';

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);

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
        $this->assertEquals(9, $sonarVersion, 'Le paramètre sonar.version devrait être 9.');
    }

    public function testBatchCollecteHotspotOwaspMenaceA0Sonar8()
    {
        // Moquer le repository HotspotOwaspRepository
        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);

        $this->em->method('getRepository')->willReturn($hotspotOwaspRepositoryMock);

        // Configurer le mock pour retourner un résultat contrôlé
        $hotspotOwaspRepositoryMock->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 200]);

        // Mock uniquement la méthode `getParameter` dans le contrôleur existant
        $this->controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->setConstructorArgs([$this->em, $this->client, $this->parameterBag])
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Configurer le mock pour retourner '8' pour sonar.version
        $this->controller->method('getParameter')
            ->with('sonar.version')
            ->willReturn(8);

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a0');

        // Assert sur le résultat
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayNotHasKey('owasp_2021', $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(200, $result['code']);
        $this->assertEquals('effacement', $result['info']);
        $this->assertEquals(static::$messageA0, $result['message']);
    }

    public function testBatchCollecteHotspotOwaspMenaceA0Sonar9()
    {
        // Moquer le repository HotspotOwaspRepository
        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);

        $this->em->method('getRepository')->willReturn($hotspotOwaspRepositoryMock);

        // Configurer le mock pour retourner un résultat contrôlé
        $hotspotOwaspRepositoryMock->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 200]);

        // Mock uniquement la méthode `getParameter` dans le contrôleur existant
        $this->controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->setConstructorArgs([$this->em, $this->client, $this->parameterBag])
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Configurer le mock pour retourner '8' pour sonar.version
        $this->controller->method('getParameter')
            ->with('sonar.version')
            ->willReturn(9);

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a0');

        // Assert sur le résultat
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('owasp_2021', $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertEquals(200, $result['code']);
        $this->assertEquals([], $result['owasp_2021']);
        $this->assertEquals('effacement', $result['info']);
        $this->assertEquals('A0 : Effacement des données de la table hotspotOwasp pour le projet.', $result['message']);
    }

    public function testBatchCollecteHotspotOwaspDeleteError()
    {
        // Moquer le repository HotspotOwaspRepository
        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);

        $this->em->method('getRepository')->willReturn($hotspotOwaspRepositoryMock);

        // Configurer le mock pour retourner un résultat contrôlé
        $hotspotOwaspRepositoryMock->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 500, 'erreur' => static::$httpError500]);

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a0');

        // Assert sur le résultat
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
    }

    public function testBatchCollecteHotspotOwaspInsertError()
    {
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($informationProjetRepositoryMock);

        $informationProjetRepositoryMock->method('selectInformationProjetProjectVersion')
        ->willReturn(['code' => 500, 'erreur' => static::$httpError500]);

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');

        // Assert sur le résultat
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
    }

    public function testBatchCollecteHotspotOwaspWithEmptyData()
    {
        $this->client->method('httpSonarQube')
            ->willReturn([ 'json' =>
                ['hotspots' => [],
                'paging' => ['total' => 0]],
            ]);

        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
        $this->em->method('getRepository')->willReturn($informationProjetRepositoryMock);

        $hotspotOwaspRepositoryMock = $this->createMock(HotspotOwaspRepository::class);
        $this->em->method('getRepository')->willReturn($hotspotOwaspRepositoryMock);

        $informationProjetRepositoryMock->method('selectInformationProjetProjectVersion')
            ->willReturn([
                'code' => 200,
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        $hotspotOwaspRepositoryMock->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');

        $this->assertEquals(200, $result['code']);
        $this->assertEquals(0, $result['owasp_2017']);
        $this->assertEquals('enregistrement', $result['info']);
        $this->assertCount(0, $result['data']);
    }

    public function testBatchCollecteHotspotOwaspWithMissingProjectInformation()
    {
        $this->client->method('httpSonarQube')
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

        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');

        $this->assertEquals(404, $result['code']);
        $this->assertEquals('L\'appel à l\'API n\'a pas abouti (Erreur 404).', $result['message']);
    }

    public function testBatchCollecteHotspotOwasp2017HttpUnAuthorizedError(): void
    {
        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        // Configuration du mock pour HotspotOwaspRepository
        $hotspotOwaspRepositoryMock->expects($this->any())
            ->method('insertHotspotOwasp')
            ->with($this->callback(function($arg) {
                return is_array($arg) && !empty($arg) && isset($arg[0]['security_category']) && $arg[0]['security_category'] === 'Cat1';
            }))
            ->willReturn(['code' => 200]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10') !== false) {
                return ['code' => 401, 'erreur' => 'UnAuthorized', 'type' => 'owasp2017'];
            }
        });

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(401, $result['code']);
        $this->assertEquals('UnAuthorized', $result['erreur']);
        $this->assertEquals('owasp2017', $result['type']);
    }

    public function testBatchCollecteHotspotOwasp2017HttpForbiddenError(): void
    {
        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        // Configuration du mock pour HotspotOwaspRepository
        $hotspotOwaspRepositoryMock->expects($this->any())
            ->method('insertHotspotOwasp')
            ->with($this->callback(function($arg) {
                return is_array($arg) && !empty($arg) && isset($arg[0]['security_category']) && $arg[0]['security_category'] === 'Cat1';
            }))
            ->willReturn(['code' => 200]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10') !== false) {
                return ['code' => 403, 'erreur' => 'Forbidden', 'type' => 'owasp2017'];
            }
        });

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(403, $result['code']);
        $this->assertEquals('Forbidden', $result['erreur']);
        $this->assertEquals('owasp2017', $result['type']);
    }

    public function testBatchCollecteHotspotOwasp2017HttpNotFoundError(): void
    {
        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        // Configuration du mock pour HotspotOwaspRepository
        $hotspotOwaspRepositoryMock->expects($this->any())
            ->method('insertHotspotOwasp')
            ->with($this->callback(function($arg) {
                return is_array($arg) && !empty($arg) && isset($arg[0]['security_category']) && $arg[0]['security_category'] === 'Cat1';
            }))
            ->willReturn(['code' => 200]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10') !== false) {
                return ['code' => 404, 'erreur' => 'NotFound', 'type' => 'owasp2017'];
            }
        });

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(404, $result['code']);
        $this->assertEquals('NotFound', $result['erreur']);
        $this->assertEquals('owasp2017', $result['type']);

    }

    public function testBatchCollecteHotspotOwasp2017HttpInternalServerError(): void
    {
        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        // Configuration du mock pour HotspotOwaspRepository
        $hotspotOwaspRepositoryMock->expects($this->any())
            ->method('insertHotspotOwasp')
            ->with($this->callback(function($arg) {
                return is_array($arg) && !empty($arg) && isset($arg[0]['security_category']) && $arg[0]['security_category'] === 'Cat1';
            }))
            ->willReturn(['code' => 200]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10') !== false) {
                return ['code' => 500, 'erreur' => static::$httpError500, 'type' => 'owasp2017'];
            }
        });

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');

        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
        $this->assertEquals('owasp2017', $result['type']);

    }

    public function testBatchCollecteHotspotOwasp2021HttpUnAuthorizedError(): void
    {
        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        // Configuration du mock pour HotspotOwaspRepository
        $hotspotOwaspRepositoryMock->expects($this->any())
            ->method('insertHotspotOwasp')
            ->with($this->callback(function($arg) {
                return is_array($arg) && !empty($arg) && isset($arg[0]['security_category']) && $arg[0]['security_category'] === 'Cat1';
            }))
            ->willReturn(['code' => 200]);

        // Création du mock de `getParameter` pour retourner une version > 8
        $this->controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->setConstructorArgs([$this->em, $this->client, $this->parameterBag])
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Mock du paramètre 'sonar.version' > 8 pour entrer dans la condition
        $this->controller->method('getParameter')
            ->willReturnMap([
                ['sonar.version', 9],
                ['sonar.url', 'http://sonar.x1.il']
        ]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10=') !== false) {
                return ['code' => 200, 'data' => 'owaspTop10 processed'];
            }
            if (strpos($url, 'owaspTop10-2021=') !== false) {
                return ['code' => 401, 'erreur' => 'UnAuthorized', 'type' => 'owasp2021'];
            }
            return null;
        });

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(401, $result['code']);
        $this->assertEquals('UnAuthorized', $result['erreur']);
        $this->assertEquals('owasp2021', $result['type']);
    }

    public function testBatchCollecteHotspotOwasp2021HttpForbiddenError(): void
    {
        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        // Configuration du mock pour HotspotOwaspRepository
        $hotspotOwaspRepositoryMock->expects($this->any())
            ->method('insertHotspotOwasp')
            ->with($this->callback(function($arg) {
                return is_array($arg) && !empty($arg) && isset($arg[0]['security_category']) && $arg[0]['security_category'] === 'Cat1';
            }))
            ->willReturn(['code' => 200]);

        // Création du mock de `getParameter` pour retourner une version > 8
        $this->controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->setConstructorArgs([$this->em, $this->client, $this->parameterBag])
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Mock du paramètre 'sonar.version' > 8 pour entrer dans la condition
        $this->controller->method('getParameter')
            ->willReturnMap([
                ['sonar.version', 9],
                ['sonar.url', 'http://sonar.x2.il']
        ]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10=') !== false) {
                return ['code' => 200, 'data' => 'owaspTop10 processed'];
            }
            if (strpos($url, 'owaspTop10-2021=') !== false) {
                return ['code' => 403, 'erreur' => 'Forbidden', 'type' => 'owasp2021'];
            }
            return null;
        });

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(403, $result['code']);
        $this->assertEquals('Forbidden', $result['erreur']);
        $this->assertEquals('owasp2021', $result['type']);

    }

    public function testBatchCollecteHotspotOwasp2021HttpNotFoundError(): void
    {
        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        // Configuration du mock pour HotspotOwaspRepository
        $hotspotOwaspRepositoryMock->expects($this->any())
            ->method('insertHotspotOwasp')
            ->with($this->callback(function($arg) {
                return is_array($arg) && !empty($arg) && isset($arg[0]['security_category']) && $arg[0]['security_category'] === 'Cat1';
            }))
            ->willReturn(['code' => 200]);

        // Création du mock de `getParameter` pour retourner une version > 8
        $this->controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->setConstructorArgs([$this->em, $this->client, $this->parameterBag])
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Mock du paramètre 'sonar.version' > 8 pour entrer dans la condition
        $this->controller->method('getParameter')
            ->willReturnMap([
                ['sonar.version', 9],
                ['sonar.url', 'http://sonar.x3.il']
        ]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10=') !== false) {
                return ['code' => 200, 'data' => 'owaspTop10 processed'];
            }
            if (strpos($url, 'owaspTop10-2021=') !== false) {
                return ['code' => 404, 'erreur' => 'Not found', 'type' => 'owasp2021'];
            }
            return null;
        });

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(404, $result['code']);
        $this->assertEquals('Not found', $result['erreur']);
        $this->assertEquals('owasp2021', $result['type']);
    }

    public function testBatchCollecteHotspotOwasp2021HttpInternalServerError(): void
    {
        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        // Création du mock de `getParameter` pour retourner une version > 8
        $this->controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->setConstructorArgs([$this->em, $this->client, $this->parameterBag])
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Mock du paramètre 'sonar.version' > 8 pour entrer dans la condition
        $this->controller->method('getParameter')
            ->willReturnMap([
                ['sonar.version', 9],
                ['sonar.url', 'http://sonar.x3.il']
        ]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10=') !== false) {
                return ['code' => 200, 'data' => 'owaspTop10 processed'];
            }
            if (strpos($url, 'owaspTop10-2021=') !== false) {
                return ['code' => 500, 'erreur' => static::$httpError500, 'type' => 'owasp2021'];
            }
            return null;
        });

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertArrayHasKey('type', $result);
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
        $this->assertEquals('owasp2021', $result['type']);

    }

    public function testBatchCollecteHotspotOwasp2021WithHotspots(): void
    {
        // Mock du repository pour InformationProjet
        $informationProjetRepositoryMock = $this->createMock(InformationProjetRepository::class);
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        // Création du mock de `getParameter` pour retourner une version > 8
        $this->controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->setConstructorArgs([$this->em, $this->client, $this->parameterBag])
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Mock du paramètre 'sonar.version' > 8 pour entrer dans la condition
        $this->controller->method('getParameter')
            ->willReturnMap([
                ['sonar.version', 9],
                ['sonar.url', 'http://sonar.x4.il']
        ]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10=') !== false) {
                // Réponse pour OWASP 2017
                return [
                        'code' => 200,
                        'json' => [
                                    'paging' => [
                                        'total' => 10,
                                        'pageIndex' => 1,
                                        'pageSize' => 100,
                                    ],
                                    'hotspots' => [
                                        [
                                            'key' => 'AVsR_xs1KqKcYlEcpfW5',
                                            'component' => 'project-key:src/main/java/com/example/MyClass.java',
                                            'project' => 'project-key',
                                            'securityCategory' => 'OWASP_A1',
                                            'vulnerabilityProbability' => 'HIGH',
                                            'status' => 'TO_REVIEW',
                                            'line' => 42,
                                            'message' => 'This is a sample hotspot message.',
                                        ],
                                    ],
                        ],
                    ];
            }
            if (strpos($url, 'owaspTop10-2021=') !== false) {
                // Réponse pour OWASP 2021
                $this->assertEquals(59, strpos($url, 'owaspTop10-2021='));
                return [
                        'code' => 200,
                        'json' => [
                            'paging' => [
                                'total' => 5,
                                'pageIndex' => 1,
                                'pageSize' => 100,
                            ],
                            'hotspots' => [
                                [
                                    'key' => 'BVsR_xs1KqKcYlEcpfW6',
                                    'component' => 'project-key:src/main/java/com/example/AnotherClass.java',
                                    'project' => 'project-key',
                                    'securityCategory' => 'OWASP_A3',
                                    'vulnerabilityProbability' => 'MEDIUM',
                                    'status' => 'TO_REVIEW',
                                    'line' => 88,
                                    'message' => 'This is another sample hotspot message.',
                                ],
                        ],
                    ],
                ];
            }
            return [
                'code' => 404,
                'json' => [
                    'paging' => [
                        'total' => 0,
                        'pageIndex' => 1,
                        'pageSize' => 100,
                    ],
                    'hotspots' => [],
                ],
            ];
        });

        $hotspotOwaspRepositoryMock->expects($this->any())
        ->method('insertHotspotOwasp')
        ->with($this->callback(function ($arg) {
            return is_array($arg)
                && isset($arg[1]['referential_owasp'])
                && $arg[1]['referential_owasp'] === 2021
                && $arg[1]['maven_key'] === 'mavenKey'
                && $arg[1]['menace'] === 'a1';
        }))
        ->willReturn(['code' => 200]);

        // Exécuter la méthode
        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');

        // Assertions pour vérifier que la branche a bien été couverte
        $this->assertArrayHasKey('code', $result);
        $this->assertArrayHasKey('info', $result);
        $this->assertArrayHasKey('owasp_2017', $result);
        $this->assertArrayHasKey('owasp_2021', $result);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertEquals('enregistrement', $result['info']);
        $this->assertEquals(10, $result['owasp_2017']);
        $this->assertEquals(5, $result['owasp_2021']);
        $this->assertEquals(2017, $result['data'][0]['referential_owasp']);
        $this->assertEquals(2021, $result['data'][1]['referential_owasp']);
    }

    public function testBatchCollecteHotspotOwaspWithInsertFailure()
    {
        // Création du mock de `getParameter` pour retourner une version > 8
        $this->controller = $this->getMockBuilder(BatchCollecteHotspotOwaspController::class)
            ->setConstructorArgs([$this->em, $this->client, $this->parameterBag])
            ->onlyMethods(['getParameter'])
            ->getMock();

        // Mock du paramètre 'sonar.version' > 8 pour entrer dans la condition
        $this->controller->method('getParameter')
            ->willReturnMap([
                ['sonar.version', 8],
                ['sonar.url', 'http://sonar.x5.il']
        ]);

        // Création du mock du client pour la réponse
        $this->client->method('httpSonarQube')->willReturnCallback(function ($url) {
            if (strpos($url, 'owaspTop10=') !== false) {
                // Réponse pour OWASP 2017
                return [
                        'code' => 200,
                        'json' => [
                                    'paging' => [
                                        'total' => 10,
                                        'pageIndex' => 1,
                                        'pageSize' => 100,
                                    ],
                                    'hotspots' => [
                                        [
                                            'key' => 'AVsR_xs1KqKcYlEcpfW5',
                                            'component' => 'project-key:src/main/java/com/example/MyClass.java',
                                            'project' => 'project-key',
                                            'securityCategory' => 'OWASP_A1',
                                            'vulnerabilityProbability' => 'HIGH',
                                            'status' => 'TO_REVIEW',
                                            'line' => 42,
                                            'message' => 'This is a sample hotspot message.',
                                        ],
                                    ],
                        ],
                    ];
            }
            return [
                'code' => 404,
                'json' => [
                    'paging' => [
                        'total' => 0,
                        'pageIndex' => 1,
                        'pageSize' => 100,
                    ],
                    'hotspots' => [],
                ],
            ];
        });
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
                'info' => [['project_version' => '1.0', 'date' => static::$date]],
            ]);

        $hotspotOwaspRepositoryMock->method('deleteHotspotOwaspMavenKey')
            ->willReturn(['code' => 200]);

        $hotspotOwaspRepositoryMock->method('insertHotspotOwasp')
            ->willReturn(['code' => 500, 'erreur' => static::$httpError500]);

        $result = $this->controller->batchCollecteHotspotOwasp('mavenKey', 'collectMode', static::$mel, 'a1');
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpError500, $result['erreur']);
    }

}
