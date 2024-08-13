<?php

declare(strict_types=1);

namespace Tests\Unit\Controller\Batch;

use PHPUnit\Framework\TestCase;
use App\Controller\Batch\BatchCollecteHotspotController;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\HotspotsRepository;
use App\Repository\InformationProjetRepository;
use App\Service\Client;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class BatchCollecteHotspotControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $entityManager;

    /** @var Client&MockObject */
    private MockObject $client;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    /** @var HotspotsRepository&MockObject */
    private MockObject $hotspotsRepository;

    /** @var InformationProjetRepository&MockObject */
    private MockObject $informationProjetRepository;

    /** @var BatchCollecteHotspotController */
    private BatchCollecteHotspotController $controller;

    /** @var ContainerInterface&MockObject */
    private MockObject $container;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->hotspotsRepository = $this->createMock(HotspotsRepository::class);
        $this->informationProjetRepository = $this->createMock(InformationProjetRepository::class);
        $this->entityManager->method('getRepository')->willReturn($this->hotspotsRepository, $this->informationProjetRepository);

        // Création du mock pour ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Instanciation du contrôleur
        $this->controller = new BatchCollecteHotspotController($this->entityManager, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testVulnerabilityProbability(): void
    {
        $reflection = new \ReflectionClass(BatchCollecteHotspotController::class);
        $method = $reflection->getMethod('vulnerabilityProbability');
        $method->setAccessible(true);

        $this->assertEquals(1, $method->invokeArgs($this->controller, ['HIGH']));
        $this->assertEquals(2, $method->invokeArgs($this->controller, ['MEDIUM']));
        $this->assertEquals(3, $method->invokeArgs($this->controller, ['LOW']));
        $this->assertEquals(-1, $method->invokeArgs($this->controller, ['UNKNOWN']));
    }

    public function testBatchCollecteHotspotDeleteHotspotsFails(): void
    {
        $this->informationProjetRepository->method('selectInformationProjetProjectVersion')->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => '2024-08-09']]]);

        $this->client->method('http')->willReturn(['paging' => ['total' => 0], 'hotspots' => []]);

        // Simule l'échec de la suppression avec un code 500
        $this->hotspotsRepository
        ->method('deleteHotspotsMavenKey')
        ->willReturn(['code' => 500, 'erreur' => 'Internal Server Error']);

        $result = $this->controller->BatchCollecteHotspot('maven-key-123', 'manual', 'user123');

        // Vérifie que le code de retour est bien 500
        $this->assertEquals(500, $result['code']);

        // Vérifie que l'erreur contient les informations correctes
        $this->assertArrayHasKey('error', $result);
        $this->assertArrayHasKey('requête : ', $result['error']);
        $this->assertEquals('deleteHotspotsMavenKey', $result['error']['requête : ']);
        $this->assertEquals('Internal Server Error', $result['error'][0]);
    }


    public function testBatchCollecteHotspotSuccess(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => '2024-08-09']]]);

        $this->client
            ->method('http')
            ->willReturn(['paging' => ['total' => 2], 'hotspots' => [
                ['vulnerabilityProbability' => 'HIGH', 'key' => 'hotspot1'],
                ['vulnerabilityProbability' => 'LOW', 'key' => 'hotspot2']
            ]]);

        $this->hotspotsRepository->method('deleteHotspotsMavenKey')->willReturn(['code' => 200]);
        $this->hotspotsRepository->method('insertHotspots')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteHotspot('maven-key-123', 'manual', 'user123');
        $this->assertEquals(200, $result['code']);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $this->assertCount(4, $result['data']);
        $this->assertCount(2, $result['message']);
    }

    public function testBatchCollecteHotspotHttp404Error(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => '2024-08-09']]]);

        $this->client
            ->method('http')
            ->willReturn(['code' => 404, 'erreur' => 'Not Found']);

        $result = $this->controller->BatchCollecteHotspot('maven-key-123', 'manual', 'user123');
        $this->assertEquals(404, $result['code']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testBatchCollecteHotspotNoHotspotsFound(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => '2024-08-09']]]);

        $this->client
            ->method('http')
            ->willReturn(['paging' => ['total' => 0], 'hotspots' => []]);

        $this->hotspotsRepository
            ->method('insertHotspots')
            ->willReturn(['code' => 200]);

        $this->hotspotsRepository->method('deleteHotspotsMavenKey')->willReturn(['code' => 200]);
        $result = $this->controller->BatchCollecteHotspot('maven-key-123', 'manual', 'user123');
        $this->assertEquals(200, $result['code']);
        $this->assertCount(1, $result['message']);
        // Compare directement les objets DateTimeImmutable
        $expectedDateVersion = (new \DateTimeImmutable('2024-08-09'))->setTimezone(new \DateTimeZone('UTC'));
        $expectedDateEnregistrement = $result['message'][0]['date_enregistrement'];

        $this->assertEquals($expectedDateVersion->format(\DateTime::ATOM), $result['message'][0]['date_version']->format(\DateTime::ATOM));
        $this->assertEquals($expectedDateEnregistrement->format(\DateTime::ATOM), $result['message'][0]['date_enregistrement']->format(\DateTime::ATOM));

        $expectedMessage = [
        'maven_key' => 'maven-key-123',
        'version' => '1.0',
        'date_version' => $expectedDateVersion->format(\DateTime::ATOM),
        'hotspot_key' => 'NC',
        'security_category' => 'NC',
        'rule_key' => 'NC',
        'probability' => 'NC',
        'status' => 'NC',
        'resolution' => '',
        'niveau' => -1,
        'mode_collecte' => 'manual',
        'utilisateur_collecte' => 'user123',
        'date_enregistrement' => $expectedDateEnregistrement->format(\DateTime::ATOM)
        ];

        $actualMessage = $result['message'][0];
        $actualMessage['date_version'] = $actualMessage['date_version']->format(\DateTime::ATOM);
        $actualMessage['date_enregistrement'] = $actualMessage['date_enregistrement']->format(\DateTime::ATOM);

        $this->assertEquals($expectedMessage, $actualMessage);
        $this->assertCount(4, $result['data']);
    }

    public function testBatchCollecteHotspotInsertionError(): void
{
    $this->informationProjetRepository
        ->method('selectInformationProjetProjectVersion')
        ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => '2024-08-09']]]);

    $this->client
        ->method('http')
        ->willReturn(['paging' => ['total' => 1], 'hotspots' => [['vulnerabilityProbability' => 'HIGH']]]);

    $this->hotspotsRepository
        ->method('insertHotspots')
        ->willReturn(['code' => 500, 'erreur' => 'Database Error']);

    $this->hotspotsRepository->method('deleteHotspotsMavenKey')->willReturn(['code' => 200]);
    $result = $this->controller->BatchCollecteHotspot('maven-key-123', 'manual', 'user123');

    $this->assertEquals(500, $result['code']);
    $this->assertArrayHasKey('erreur', $result);
}
}
