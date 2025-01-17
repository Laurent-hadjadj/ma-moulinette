<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use PHPUnit\Framework\TestCase;
use App\Controller\Batch\BatchCollecteOwaspController;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Client;
use App\Entity\Owasp;
use App\Entity\InformationProjet;
use App\Repository\OwaspRepository;
use App\Repository\InformationProjetRepository; // Assurez-vous d'inclure ce use
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class BatchCollecteOwaspControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $entityManager;

    /** @var Client&MockObject */
    private MockObject $client;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    /** @var OwaspRepository&MockObject */
    private MockObject $owaspRepository;

    /** @var InformationProjetRepository&MockObject */
    private MockObject $informationProjetRepository;

    /** @var BatchCollecteOwaspController */
    private BatchCollecteOwaspController $controller;

    /** @var ContainerInterface&MockObject */
    private MockObject $container;

    private static $parameters = 'componentKeys=DummyMavenKey&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10';

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->owaspRepository = $this->createMock(OwaspRepository::class);
        $this->informationProjetRepository = $this->createMock(InformationProjetRepository::class);

        // Stubbing la méthode getRepository pour retourner le mock approprié
        $this->entityManager->method('getRepository')->willReturnMap([
                [Owasp::class, $this->owaspRepository],
                [InformationProjet::class, $this->informationProjetRepository],
            ]);

        // Création du mock pour ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Stubbing la méthode getParameter pour retourner l'URL du sonar
        $this->parameterBag->method('get')->with(BatchCollecteOwaspController::$sonarUrl)
            ->willReturn('http://localhost/api/issues/search?' . static::$parameters);

        // Instanciation du contrôleur
        $this->controller = new BatchCollecteOwaspController($this->entityManager, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testBatchCollecteOwaspSuccess()
    {
        $this->parameterBag->method('get')->with(BatchCollecteOwaspController::$sonarUrl)->willReturn('http://localhost/api/issues/search?' . static::$parameters);
        $this->informationProjetRepository->method('selectInformationProjetProjectVersion')->willReturn([
            'code' => 200,
            'info' => [['date' => '2024-08-01', 'project_version' => '1.0.0']]
        ]);

        $this->client->method('httpSonarQube')->willReturn([
            'total' => 10,
            'effortTotal' => 100,
            'facets' => [[
                'values' => [['val' => 'a1', 'count' => 5], ['val' => 'a2', 'count' => 5]]
            ]],
            'issues' => [
                ['status' => 'OPEN', 'tags' => ['owasp-a1'], 'severity' => 'BLOCKER'],
                ['status' => 'OPEN', 'tags' => ['owasp-a2'], 'severity' => 'CRITICAL'],
            ]
        ]);

        $this->owaspRepository->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepository->method('insertOwasp')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteOwasp('mavenKey', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');
        $this->assertEquals(200, $result['code']);
        $this->assertEquals(10, $result['nombre']);
    }

    public function testBatchCollecteOwaspHttpError()
    {
        $this->parameterBag->method('get')->with(BatchCollecteOwaspController::$sonarUrl)->willReturn('http://localhost/api/issues/search?' . static::$parameters);
        $this->client->method('httpSonarQube')->willReturn(['code' => 404, 'erreur' => 'Not Found']);

        $result = $this->controller->BatchCollecteOwasp('mavenKey', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');
        $this->assertEquals(404, $result['code']);
        $this->assertEquals('Not Found', $result['erreur']);
    }

    public function testBatchCollecteOwaspSelectError()
    {
        // Configurez le mock pour renvoyer une réponse avec un code d'erreur
        $this->parameterBag->method('get')->with(BatchCollecteOwaspController::$sonarUrl)->willReturn('http://localhost/api/issues/search?' . static::$parameters);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Owasp::class, $this->owaspRepository],
            [InformationProjet::class, $this->informationProjetRepository],
        ]);

        // Configurez le mock pour renvoyer une erreur dans selectInformationProjetProjectVersion
        $this->informationProjetRepository->method('selectInformationProjetProjectVersion')->willReturn([
            'code' => 500,
            'erreur' => 'Database error'
        ]);

        // Exécutez la méthode que vous voulez tester
        $result = $this->controller->BatchCollecteOwasp('mavenKey', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');

        // Vérifiez que le code retourné est celui attendu
        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Database error', $result['message']);
    }

    public function testBatchCollecteOwaspNoInfoFound()
    {
        // Configurez le mock pour renvoyer une réponse avec info vide
        $this->parameterBag->method('get')->with(BatchCollecteOwaspController::$sonarUrl)->willReturn('http://localhost/api/issues/search?' . static::$parameters);
        $this->entityManager->method('getRepository')->willReturnMap([
            [Owasp::class, $this->owaspRepository],
            [InformationProjet::class, $this->informationProjetRepository],
        ]);

        // Configurez le mock pour renvoyer une réponse où info est vide
        $this->informationProjetRepository->method('selectInformationProjetProjectVersion')->willReturn([
            'code' => 200,
            'info' => []  // info vide
        ]);

        // Configurez également le mock pour la réponse du client HTTP, même si ce n'est pas directement utilisé ici
        $this->client->method('httpSonarQube')->willReturn([
            'total' => 0,
            'effortTotal' => 0,
            'facets' => [],
            'issues' => []
        ]);

        // Configurez le mock pour le dépôt Owasp
        $this->owaspRepository->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepository->method('insertOwasp')->willReturn(['code' => 200]);

        // Exécutez la méthode que vous voulez tester
        $result = $this->controller->BatchCollecteOwasp('mavenKey', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');

        // Vérifiez que le code retourné est celui attendu
        $this->assertEquals(404, $result['code']);
        $this->assertEquals(BatchCollecteOwaspController::$erreur404, $result['message']);
    }

    public function testBatchCollecteOwaspDeleteError()
    {
        $this->parameterBag->method('get')->with(BatchCollecteOwaspController::$sonarUrl)->willReturn('http://localhost/api/issues/search?' . static::$parameters);

        // on récupère les résultats du client HTTP
        $this->client->method('httpSonarQube')->willReturn([
            'total' => 10,
            'effortTotal' => 100,
            'facets' => [
                [
                    'values' => [
                        ['val' => 'a1', 'count' => 5],
                        ['val' => 'a2', 'count' => 5],
                    ]
                ]
            ],
            'issues' => [
                ['status' => 'OPEN', 'tags' => ['owasp-a1'], 'severity' => 'BLOCKER'],
                ['status' => 'OPEN', 'tags' => ['owasp-a2'], 'severity' => 'CRITICAL'],
            ]
        ]);

        // On retourne un objet info
        $this->informationProjetRepository->method('selectInformationProjetProjectVersion')->willReturn(['code' => 200, 'info' => [['project_version' => '1.2.0-RELEASE',
        'date' => '2024-07-10 15:26:07+02']]]);
        // On plante la suppression des données pour la mavenKey
        $this->owaspRepository->method('deleteOwaspMavenKey')->willReturn(['code' => 500, 'erreur' => 'Deletion failed']);

        $result = $this->controller->BatchCollecteOwasp('mavenKey', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');
         // Vérifiez que le code retourné est celui attendu
        $this->assertEquals(500, $result['code']);
        $this->assertArrayHasKey('erreur', $result);
        $this->assertIsArray($result['erreur']);
        $this->assertCount(2, $result['erreur']);
        $this->assertEquals('Deletion failed', $result['erreur'][0]);
    }

    public function testBatchCollecteOwaspInsertError()
    {
        $this->parameterBag->method('get')->with(BatchCollecteOwaspController::$sonarUrl)->willReturn('http://localhost/api/issues/search?' . static::$parameters);

        $this->informationProjetRepository->method('selectInformationProjetProjectVersion')->willReturn(['code' => 200, 'info' => [['project_version' => '1.2.0-RELEASE',
                'date' => '2024-07-10 15:26:07+02']]]);
        $this->owaspRepository->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->client->method('httpSonarQube')->willReturn([
            'total' => 10,
            'effortTotal' => 100,
            'facets' => [
                [
                    'values' => [
                        ['val' => 'a1', 'count' => 5],
                        ['val' => 'a2', 'count' => 5],
                    ]
                ]
            ],
            'issues' => [
                ['status' => 'OPEN', 'tags' => ['owasp-a1'], 'severity' => 'BLOCKER'],
                ['status' => 'OPEN', 'tags' => ['owasp-a2'], 'severity' => 'CRITICAL'],
            ]
        ]);

        $this->owaspRepository->method('insertOwasp')->willReturn(['code' => 500, 'erreur' => 'Insertion failed']);

        // Exécutez la méthode que vous voulez tester
        $result = $this->controller->BatchCollecteOwasp('mavenKey', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');
        $this->assertEquals(500, $result['code']);

        // Vérifiez le message d'erreur retourné
        $this->assertArrayHasKey('erreur', $result);
        $this->assertEquals(['Insertion failed', BatchCollecteOwaspController::$request => 'insertNote'], $result['erreur']);
    }
}
