<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteMesureController;
use App\Repository\MesuresRepository;
use App\service\ClientService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Container\ContainerInterface;

/**
 * [Description BatchCollecteMesureControllerTest]
 */
class BatchCollecteMesureControllerTest extends TestCase
{
    private EntityManagerInterface $em;
    private ClientService $client;
    private BatchCollecteMesureController $controller;
    private ContainerInterface $container;
    private ParameterBagInterface $parameterBag;

    private static $mel = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $nclocDistributionValue = 'java=60;php=40';

    protected function setUp(): void
    {
        // Mock EntityManagerInterface
        $this->em = $this->createMock(EntityManagerInterface::class);

        // Mock Client
        $this->client = $this->createMock(Client::class);

        // Mock ParameterBagInterface
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->parameterBag->method('get')->with('sonar.url')->willReturn('http://localhost');

        // Mock ContainerInterface
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // Instantiate the controller with mocked dependencies
        $this->controller = new BatchCollecteMesureController($this->em, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testBatchCollecteMesureHttpError(): void
    {
        // Mock the HTTP client to return an error
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 404,
            'erreur' => 'Not Found'
        ]);

        $result = $this->controller->BatchCollecteMesure('some-maven-key', 'COLLECTE', static::$mel);
        $this->assertEquals(['code' => 404, 'erreur' => 'Not Found'], $result);
    }

    public function testBatchCollecteMesureDeleteError(): void
    {
        // Mock the HTTP client to return valid data
        $this->client->method('httpSonarQube')->will($this->returnValueMap([
            ['http://localhost/api/components/app?component=some-maven-key', ['measures' => ['lines' => 100, 'coverage' => 75, 'duplicationDensity' => 5, 'tests' => 20, 'issues' => 3]]],
            ['http://localhost/api/measures/component?component=some-maven-key&metricKeys=ncloc,ncloc_language_distribution', ['component' => ['measures' => [['metric' => 'ncloc', 'value' => 1500], ['metric' => 'ncloc_language_distribution', 'value' => static::$nclocDistributionValue],
            ['metric' => 'classes', 'value' => 100],
            ['metric' => 'functions', 'value' => 100],
            ['metric' => 'files', 'value' => 100]]]]],
            ['http://localhost/api/measures/component?component=some-maven-key&metricKeys=sqale_debt_ratio', ['component' => ['measures' => [['metric' => 'sqale_debt_ratio', 'value' => '1.23']]]]]
        ]));

        // Mock the repository to return an error on delete
        $mesuresRepository = $this->createMock(MesuresRepository::class);
        $mesuresRepository->method('deleteMesuresMavenKey')->willReturn(['code' => 500, 'erreur' => 'Delete error']);
        $this->em->method('getRepository')->willReturn($mesuresRepository);

        $result = $this->controller->BatchCollecteMesure('some-maven-key', 'COLLECTE', static::$mel);
        $this->assertEquals(['code' => 500, 'erreur' => 'Delete error'], $result);
    }

    public function testBatchCollecteMesureInsertError(): void
    {
        // Define a return value map for the mocked http() method
        $this->client->method('httpSonarQube')->willReturnOnConsecutiveCalls(
            [   'json' => [
                'projectName' => 'ma-moulinette',
                'measures' => [
                    'lines' => 100,
                    'coverage' => 75,
                    'duplicationDensity' => 5,
                    'tests' => 20,
                    'issues' => 3
                ]]
            ],
            [
                'json' => [
                'component' => [
                    'measures' => [
                        ['metric' => 'ncloc', 'value' => 1500],
                        ['metric' => 'ncloc_language_distribution', 'value' => static::$nclocDistributionValue],
                        ['metric' => 'classes', 'value' => 100],
                        ['metric' => 'functions', 'value' => 100],
                        ['metric' => 'files', 'value' => 100]
                    ]]
                ]
            ],
            [   'json' =>[
                'component' => [
                    'measures' => [
                        ['metric' => 'sqale_debt_ratio', 'value' => '1.23']
                    ]
                ]]
            ]
        );

        // Mock the repository to succeed on delete but fail on insert
        $mesuresRepository = $this->createMock(MesuresRepository::class);
        $mesuresRepository->method('deleteMesuresMavenKey')->willReturn(['code' => 200]);
        $mesuresRepository->method('insertMesures')->willReturn(['code' => 500, 'erreur' => 'Insert error']);
        $this->em->method('getRepository')->willReturn($mesuresRepository);

        // Invoke the controller method
        $result = $this->controller->BatchCollecteMesure('some-maven-key', 'COLLECTE', static::$mel);

        // Assert that the result matches the expected error format
        $this->assertEquals(['code' => 500, 'erreur' => 'Insert error'], $result);
    }

    public function testBatchCollecteMesureSuccess(): void
    {
        // Mock the HTTP client to return valid data
        $this->client->method('httpSonarQube')->willReturnOnConsecutiveCalls(
            [ 'json' => [
                'projectName' => 'ma-moulinette',
                'measures' => [
                    'lines' => 100,
                    'coverage' => 75,
                    'duplicationDensity' => 5.0,
                    'tests' => 20,
                    'issues' => 3
                ]]
            ],
            [   'json'=>[
                'component' => [
                    'measures' => [
                        ['metric' => 'ncloc', 'value' => 1500],
                        ['metric' => 'ncloc_language_distribution', 'value' => static::$nclocDistributionValue],
                        ['metric' => 'classes', 'value' => 100],
                        ['metric' => 'functions', 'value' => 100],
                        ['metric' => 'files', 'value' => 100]
                    ]
                ]]
            ],
            [   'json' => [
                'component' => [
                    'measures' => [
                        ['metric' => 'sqale_debt_ratio', 'value' => '1.23']
                    ]]
                ]
            ]
        );

        // Mock the repository to succeed on delete and insert
        $mesuresRepository = $this->createMock(MesuresRepository::class);
        $mesuresRepository->method('deleteMesuresMavenKey')->willReturn(['code' => 200]);
        $mesuresRepository->method('insertMesures')->willReturn(['code' => 200]);
        $this->em->method('getRepository')->willReturn($mesuresRepository);

        $result = $this->controller->BatchCollecteMesure('some-maven-key', 'COLLECTE', static::$mel);

        $currentDateTimeParis = (new \DateTimeImmutable())->setTimezone(new \DateTimeZone("UTC"))->format('Y-m-d H:i:sO');

        $expectedData = [
            'nom_projet' => 'ma-moulinette',
            'nombre_ligne' => 100,
            'nombre_ligne_code' => 1500,
            'language_distribution' => ['java' => 60, 'php' => 40],
            'coverage' => 75.0,
            'sqale_debt_ratio' => 1.23,
            'duplicated_lines_density' => 5.0,
            'tests' => 20,
            'issues' => 3
        ];


        $expectedResult = [
            'code' => 200,
            'message' => [
                'maven_key' => 'some-maven-key',
                'project_name' => 'ma-moulinette',
                'lines' => 100,
                'ncloc' => 1500,
                'classes' => 100,
                'functions' => 100,
                'files' => 100,
                'language_distribution' => ['java' => 60, 'php' => 40],
                'sqale_debt_ratio' => 1.23,
                'coverage' => 75,
                'duplicated_lines_density' => 5.0,
                'tests' => 20,
                'issues' => 3,
                'mode_collecte' => 'COLLECTE',
                'utilisateur_collecte' => static::$mel,
                'date_enregistrement' => $currentDateTimeParis
            ],
            'data' => $expectedData
        ];

        if ($result['message']['date_enregistrement'] instanceof \DateTimeImmutable) {
            $result['message']['date_enregistrement'] = $result['message']['date_enregistrement']
                ->setTimezone(new \DateTimeZone('UTC')) // Normalisez en UTC
                ->format('Y-m-d H:i:sO');
        }

        $this->assertEquals($expectedResult, $result);
    }
}
