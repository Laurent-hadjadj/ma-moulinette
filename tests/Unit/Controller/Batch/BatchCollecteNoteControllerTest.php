<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteNoteController;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ClientService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\NotesRepository;
use App\Repository\HotspotsRepository;
use Psr\Container\ContainerInterface;

/**
 * [Description BatchCollecteNoteControllerTest]
 */
class BatchCollecteNoteControllerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ClientService $client;
    private ParameterBagInterface $parameterBag;
    private NotesRepository $notesRepository;
    private HotspotsRepository $hotspotsRepository;
    private BatchCollecteNoteController $controller;
    private ContainerInterface $container;

    private static $mel = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $httpErreur500 = 'Internal server error';
    protected function setUp(): void
    {
        // Mocks setup
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);
        $this->notesRepository = $this->createMock(NotesRepository::class);
        $this->hotspotsRepository = $this->createMock(HotspotsRepository::class);

        // Return value map for getRepository
        $this->entityManager->method('getRepository')
            ->will($this->returnValueMap([
                [\App\Entity\Notes::class, $this->notesRepository],
                [\App\Entity\Hotspots::class, $this->hotspotsRepository],
            ]));

        // Container mock setup
        $this->container = $this->createMock(ContainerInterface::class);
        $this->container->method('has')->with('parameter_bag')->willReturn(true);
        $this->container->method('get')->with('parameter_bag')->willReturn($this->parameterBag);

        // ParameterBag mock setup
        $this->parameterBag->method('get')->with('sonar.url')->willReturn('http://localhost');

        // Controller instantiation
        $this->controller = new BatchCollecteNoteController($this->entityManager, $this->client);
        $this->controller->setContainer($this->container);
    }

    public function testBatchCollecteNoteHttpUnauthorizedError()
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 401, 'erreur' => 'Unauthorized']);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', static::$mel, 'quality');

        $this->assertEquals(['code' => 401, 'erreur' => 'Unauthorized'], $result);
    }

    public function testBatchCollecteNoteHttpForbiddenError()
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 403, 'erreur' => 'Forbidden']);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', static::$mel, 'quality');

        $this->assertEquals(['code' => 403, 'erreur' => 'Forbidden'], $result);
    }

    public function testBatchCollecteNoteHttpNotFoundError()
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 404, 'erreur' => 'Not Found']);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', static::$mel, 'quality');

        $this->assertEquals(['code' => 404, 'erreur' => 'Not Found'], $result);
    }

    public function testBatchCollecteNoteHttpInternalServerError()
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 500, 'erreur' => 'Internal serveur error']);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', static::$mel, 'quality');

        $this->assertEquals(['code' => 500, 'erreur' => 'Internal serveur error'], $result);
    }

    public function testBatchCollecteNoteDeleteError()
    {
        $this->client->method('httpSonarQube')->willReturn([
            'component' => ['measures' => [['value' => 3.5]]]
        ]);

        $this->notesRepository->method('deleteNotesMavenKey')->willReturn(['code' => 500, 'erreur' => 'Delete error']);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', static::$mel, 'quality');

        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Delete error', $result['erreur']);
    }

    public function testBatchCollecteNoteInsertError()
    {
        $this->client->method('httpSonarQube')->willReturn([
            'json' => ['component' => ['measures' => [['value' => 3.5]]]]
        ]);

        $this->notesRepository->method('deleteNotesMavenKey')->willReturn(['code' => 200]);
        $this->notesRepository->method('insertNotes')->willReturn(['code' => 500, 'erreur' => 'Insert error']);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', static::$mel, 'quality');

        $this->assertEquals(500, $result['code']);
        $this->assertEquals('Insert error', $result['erreur']);
    }

    public function testBatchCollecteNoteSuccess()
    {
        $this->client->method('httpSonarQube')->willReturn([
            'json' => ['component' => ['measures' => [['value' => 4.2]]]]
        ]);

        $this->notesRepository->method('deleteNotesMavenKey')->willReturn(['code' => 200]);
        $this->notesRepository->method('insertNotes')->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', static::$mel, 'quality');
        $expectedData = ['note_quality' => 'D'];

        $this->assertEquals(['code' => 200, 'message' => ['value' => 'D'], 'data' => $expectedData], $result);
    }

    public function testBatchCollecteNoteHotspotSuccess()
    {
        $mavenKey = 'some-maven-key';

        // Mock des retours pour countHotspotsStatus
        $this->hotspotsRepository->method('countHotspotsStatus')->willReturnMap([
            [['maven_key' => $mavenKey, 'status' => 'TO_REVIEW'], ['code' => 200, 'to_review' => 10]],
            [['maven_key' => $mavenKey, 'status' => 'REVIEWED'], ['code' => 200, 'reviewed' => 8]],
        ]);

        $result = $this->controller->BatchCollecteNoteHotspot($mavenKey);

        $this->assertEquals(200, $result['code']);
        $this->assertEquals('A', $result['message']['note_hotspot']);
        $this->assertEquals('A', $result['data']['note_hotspot']);
    }

    public function testBatchCollecteNoteHotspotErrorToReview()
    {
        $mavenKey = 'some-maven-key';

        // Mock des retours pour countHotspotsStatus
        $this->hotspotsRepository->method('countHotspotsStatus')->willReturnMap([
            [['maven_key' => $mavenKey, 'status' => 'TO_REVIEW'], ['code' => 500, 'erreur' => static::$httpErreur500]],
        ]);

        $result = $this->controller->BatchCollecteNoteHotspot($mavenKey);

        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpErreur500, $result['erreur']);
    }

    public function testBatchCollecteNoteHotspotErrorReviewed()
    {
        $mavenKey = 'some-maven-key';

        // Mock des retours pour countHotspotsStatus
        $this->hotspotsRepository->method('countHotspotsStatus')->willReturnMap([
            [['maven_key' => $mavenKey, 'status' => 'TO_REVIEW'], ['code' => 200, 'to_review' => 10]],
            [['maven_key' => $mavenKey, 'status' => 'REVIEWED'], ['code' => 500, 'erreur' => static::$httpErreur500]],
        ]);

        $result = $this->controller->BatchCollecteNoteHotspot($mavenKey);

        $this->assertEquals(500, $result['code']);
        $this->assertEquals(static::$httpErreur500, $result['erreur']);
    }

    public function testBatchCollecteNoteHotspotNoteB()
    {
        $mavenKey = 'some-maven-key';

        // Mock des retours pour countHotspotsStatus
        $this->hotspotsRepository->method('countHotspotsStatus')->willReturnMap([
            [['maven_key' => $mavenKey, 'status' => 'TO_REVIEW'], ['code' => 200, 'to_review' => 10]],
            [['maven_key' => $mavenKey, 'status' => 'REVIEWED'], ['code' => 200, 'reviewed' => 7]],
        ]);

        $result = $this->controller->BatchCollecteNoteHotspot($mavenKey);

        $this->assertEquals(200, $result['code']);
        $this->assertEquals('B', $result['message']['note_hotspot']);
        $this->assertEquals('B', $result['data']['note_hotspot']);
    }

    public function testBatchCollecteNoteHotspotNoteC()
    {
        $mavenKey = 'some-maven-key';

        // Mock des retours pour countHotspotsStatus
        $this->hotspotsRepository->method('countHotspotsStatus')->willReturnMap([
            [['maven_key' => $mavenKey, 'status' => 'TO_REVIEW'], ['code' => 200, 'to_review' => 10]],
            [['maven_key' => $mavenKey, 'status' => 'REVIEWED'], ['code' => 200, 'reviewed' => 5]],
        ]);

        $result = $this->controller->BatchCollecteNoteHotspot($mavenKey);

        $this->assertEquals(200, $result['code']);
        $this->assertEquals('C', $result['message']['note_hotspot']);
        $this->assertEquals('C', $result['data']['note_hotspot']);
    }

    public function testBatchCollecteNoteHotspotNoteD()
    {
        $mavenKey = 'some-maven-key';

        // Mock des retours pour countHotspotsStatus
        $this->hotspotsRepository->method('countHotspotsStatus')->willReturnMap([
            [['maven_key' => $mavenKey, 'status' => 'TO_REVIEW'], ['code' => 200, 'to_review' => 10]],
            [['maven_key' => $mavenKey, 'status' => 'REVIEWED'], ['code' => 200, 'reviewed' => 3]],
        ]);

        $result = $this->controller->BatchCollecteNoteHotspot($mavenKey);

        $this->assertEquals(200, $result['code']);
        $this->assertEquals('D', $result['message']['note_hotspot']);
        $this->assertEquals('D', $result['data']['note_hotspot']);
    }

    public function testBatchCollecteNoteHotspotNoteE()
    {
        $mavenKey = 'some-maven-key';

        // Mock des retours pour countHotspotsStatus
        $this->hotspotsRepository->method('countHotspotsStatus')->willReturnMap([
            [['maven_key' => $mavenKey, 'status' => 'TO_REVIEW'], ['code' => 200, 'to_review' => 10]],
            [['maven_key' => $mavenKey, 'status' => 'REVIEWED'], ['code' => 200, 'reviewed' => 2]],
        ]);

        $result = $this->controller->BatchCollecteNoteHotspot($mavenKey);

        $this->assertEquals(200, $result['code']);
        $this->assertEquals('E', $result['message']['note_hotspot']);
        $this->assertEquals('E', $result['data']['note_hotspot']);
    }
}
