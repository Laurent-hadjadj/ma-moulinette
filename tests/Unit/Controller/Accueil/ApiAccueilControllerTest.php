<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Accueil;

use App\Controller\Accueil\ApiAccueilController;
use App\Entity\{ListeProjet, Properties};
use App\Repository\{ListeProjetRepository, PropertiesRepository};
use App\Service\{ClientService, UrlBuilderService};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class ApiAccueilControllerTest extends TestCase
{
    /** @var ClientService&MockObject */            private MockObject $client;
    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var EntityManagerInterface&MockObject */   private MockObject $em;
    /** @var UrlBuilderService&MockObject */        private MockObject $urlBuilder;
    /** @var ListeProjetRepository&MockObject */    private MockObject $listeProjetRepo;
    /** @var PropertiesRepository&MockObject */     private MockObject $propertiesRepo;
    /** @var ParameterBagInterface&MockObject */    private MockObject $params;

    private ApiAccueilController $controller;

    protected function setUp(): void
    {
        $this->client = $this->createMock(ClientService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->listeProjetRepo = $this->createMock(ListeProjetRepository::class);
        $this->propertiesRepo = $this->createMock(PropertiesRepository::class);
        $this->params = $this->createMock(ParameterBagInterface::class);

        $this->em->method('getRepository')->willReturnMap([
            [ListeProjet::class, $this->listeProjetRepo],
            [Properties::class, $this->propertiesRepo],
        ]);

        $this->urlBuilder->method('build')->willReturn('https://sonar/api/...');
        $this->params->method('get')->willReturn('https://sonar.example.com');

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'parameter_bag'
        );
        $container->method('get')->willReturnMap([
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new ApiAccueilController($this->client, $this->logger, $this->em, $this->urlBuilder);
        $this->controller->setContainer($container);
    }

    /* ============ apiSonarStatus ============ */

    public function testApiSonarStatusReturnsErrorWhenDown(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 503, 'erreur' => 'timeout']);

        $response = $this->controller->apiSonarStatus(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(503, $data['code']);
        $this->assertSame('critical', $data['type']);
    }

    public function testApiSonarStatusHappyPath(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 200, 'json' => ['status' => 'UP']]);

        $response = $this->controller->apiSonarStatus(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    /* ============ accueilProjetListe ============ */

    public function testAccueilProjetListeReturnsErrorWhenSonarFails(): void
    {
        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => 503, 'erreur' => 'down']);

        $response = $this->controller->accueilProjetListe();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(503, $data['code']);
    }

    public function testAccueilProjetListeReturns404WhenEmpty(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200, 'json' => ['components' => []],
        ]);

        $response = $this->controller->accueilProjetListe();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $data['code']);
    }

    public function testAccueilProjetListeReturnsErrorWhenDeleteFails(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['components' => [
                ['key' => 'k1', 'name' => 'P1', 'tags' => [], 'visibility' => 'public'],
            ]],
        ]);
        $this->listeProjetRepo->expects($this->once())
            ->method('deleteListeProjet')
            ->willReturn(['code' => 500, 'erreur' => 'delete fail']);

        $response = $this->controller->accueilProjetListe();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('delete fail', $data['trace']);
    }

    public function testAccueilProjetListeSkipsSvnProjects(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['components' => [
                ['key' => 'fr.ma-moulinette:ma-moulinette', 'name' => 'App', 'tags' => ['TeamA'], 'visibility' => 'public'],
                ['key' => 'fr.ma-moulinette:projet-legacy-SVN', 'name' => 'Legacy', 'tags' => [], 'visibility' => 'private'],
            ]],
        ]);
        $this->listeProjetRepo->method('deleteListeProjet')->willReturn(['code' => 200]);
        $this->propertiesRepo->method('updatePropertiesProjet')->willReturn(['code' => 200]);

        $this->em->expects($this->once())->method('persist');

        $response = $this->controller->accueilProjetListe();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(1, $data['nombre']);
        $this->assertSame(1, $data['public']);
        $this->assertSame(0, $data['private']);
    }

    public function testAccueilProjetListeReturnsErrorWhenUpdateFails(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['components' => [
                ['key' => 'k1', 'name' => 'P1', 'tags' => [], 'visibility' => 'public'],
            ]],
        ]);
        $this->listeProjetRepo->method('deleteListeProjet')->willReturn(['code' => 200]);
        $this->propertiesRepo->expects($this->once())
            ->method('updatePropertiesProjet')
            ->willReturn(['code' => 500, 'erreur' => 'update fail']);

        $response = $this->controller->accueilProjetListe();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    /* ============ accueilProjetTags ============ */

    public function testAccueilProjetTagsReturnsErrorOnFailure(): void
    {
        $this->listeProjetRepo->expects($this->once())
            ->method('countListeProjetTags')
            ->willReturn(['code' => 500, 'erreur' => 'fail']);

        $response = $this->controller->accueilProjetTags();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testAccueilProjetTagsHappyPath(): void
    {
        $this->listeProjetRepo->method('countListeProjetTags')->willReturn([
            'code' => 200, 'nombre' => [['tag' => 12]],
        ]);

        $response = $this->controller->accueilProjetTags();
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(12, $data['nombre_tag']);
    }
}
