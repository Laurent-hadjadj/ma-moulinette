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

    protected function setUp(): void
    {
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

        // Instanciation du contrôleur
        $this->controller = new BatchCollecteAnomalieDetailController($this->entityManager, $this->client, $this->serviceExtractName);
        $this->controller->setContainer($this->container);
    }

    public function testBatchCollecteAnomalieDetailSuccess()
    {
        $this->anomalieDetailsRepository->method('deleteAnomalieDetailsMavenKey')->willReturn(['code' => 200]);
        $this->anomalieDetailsRepository->method('insertAnomalieDetail')->willReturn(['code' => 200]);

        // Mocking client http response
        $this->client->method('httpSonarQube')->willReturn([
            'paging' => ['total' => 1],
            'facets' => [
                ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                ['property' => 'severities', 'values' => [['val' => 'CRITICAL', 'count' => 1]]],
                ['property' => 'severities', 'values' => [['val' => 'MAJOR', 'count' => 1]]],
                ['property' => 'severities', 'values' => [['val' => 'MINOR', 'count' => 1]]],
                ['property' => 'severities', 'values' => [['val' => 'INFO', 'count' => 1]]],
            ],
        ]);

        // Mocking ExtractName service
        $this->serviceExtractName->method('extractNameFromMavenKey')->willReturn('ProjectName');

        $result = $this->controller->BatchCollecteAnomalieDetail('maven_key', 'mode_collecte', 'mode_utilisateur');

        $this->assertEquals(200, $result['code']);
        $this->assertArrayHasKey('message', $result);
        $this->assertArrayHasKey('data', $result);
        $expectedData=[
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

    public function testBatchCollecteAnomalieDetailError()
    {
        // Mocking client http response
        $this->client->method('httpSonarQube')->willReturn([
                'paging' => ['total' => 1],
                'facets' => [
                    ['property' => 'severities', 'values' => [['val' => 'BLOCKER', 'count' => 1]]],
                    ['property' => 'severities', 'values' => [['val' => 'CRITICAL', 'count' => 1]]],
                    ['property' => 'severities', 'values' => [['val' => 'MAJOR', 'count' => 1]]],
                    ['property' => 'severities', 'values' => [['val' => 'MINOR', 'count' => 1]]],
                    ['property' => 'severities', 'values' => [['val' => 'INFO', 'count' => 1]]],
                ],
            ]);
        $this->anomalieDetailsRepository->method('deleteAnomalieDetailsMavenKey')->willReturn(['code' => 500, 'erreur' => 'Delete Error']);
        $this->anomalieDetailsRepository->method('insertAnomalieDetail')->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteAnomalieDetail('maven_key', 'mode_collecte', 'utilisateur_collecte');
        $this->assertEquals(500, $result['code']);
        $this->assertEquals(['Delete Error', 'requête : ' => 'deleteAnomalieDetailsMavenKey'], $result['erreur']);
    }

    public function testBatchCollecteAnomalieDetailNoIssues()
    {
        $this->anomalieDetailsRepository->method('deleteAnomalieDetailsMavenKey')->willReturn(['code' => 200]);
        $this->anomalieDetailsRepository->method('insertAnomalieDetail')->willReturn(['code' => 200]);

        // Mocking client http response
        $this->client->method('httpSonarQube')->willReturn([
            'paging' => ['total' => 0]
        ]);

        $result = $this->controller->BatchCollecteAnomalieDetail('mavenKey', 'manual', 'laurent.hadjadj@ma-petite-entreprise.fr');

        $this->assertEquals(200, $result['code']);
        $this->assertEquals("Pas d'anomalie trouvée", $result['message']);
        $this->assertEmpty($result['data']);
    }

}
