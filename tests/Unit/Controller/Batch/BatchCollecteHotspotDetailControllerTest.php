<?php

declare(strict_types=1);

namespace Tests\Unit\Controller\Batch;

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

    public function testBatchCollecteHotspotDetailSuccess(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => '2024-08-09']]]);

        $this->hotspotsRepository
            ->method('selectHotspotsToReview')
            ->willReturn(['code' => 200, 'liste' => [['hotspot_key' => 'hotspot-key-1']]]);

        $this->hotspotDetailsRepository->method('deleteHotspotDetailsMavenKey')->willReturn(['code' => 200]);

        $this->hotspotDetailsRepository->method('insertHotspotDetails')->willReturn(['code' => 200]);

        $this->client
            ->method('http')
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

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');

        $this->assertEquals(200, $result['code']);
        $this->assertCount(1, $result['message']);
        $this->assertArrayHasKey('hotspot_key', $result['message'][0]);
        $this->assertEquals('hotspot-key-1', $result['message'][0]['hotspot_key']);
    }

    public function testBatchCollecteHotspotDetailProjectInfoError(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 500, 'erreur' => 'Erreur projet']);

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');

        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Erreur projet', $result['message']);
    }

    public function testBatchCollecteHotspotDetailEmptyHotspots(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => '2024-08-09']]]);

        $this->hotspotsRepository
            ->method('selectHotspotsToReview')
            ->willReturn(['code' => 200, 'liste' => []]);

        $this->hotspotDetailsRepository
            ->method('deleteHotspotDetailsMavenKey')
            ->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');

        $this->assertEquals(406, $result['code']);
        $this->assertEquals('Liste vide !!! ', $result['message']);
    }

    public function testBatchCollecteHotspotDetailInsertError(): void
    {
        $this->informationProjetRepository
            ->method('selectInformationProjetProjectVersion')
            ->willReturn(['code' => 200, 'info' => [['project_version' => '1.0', 'date' => '2024-08-09']]]);

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
            ->method('http')
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

        $result = $this->controller->batchCollecteHotspotDetail('maven-key-123', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');

        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Erreur insertion', $result['error'][0]);
        $this->assertEquals('insertHotspotDetails', $result['error']['requête : ']);
    }
}
