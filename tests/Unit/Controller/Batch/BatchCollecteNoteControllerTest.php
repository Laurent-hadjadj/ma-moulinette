<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchCollecteNoteController;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Repository\NotesRepository;
use App\Repository\HotspotsRepository;
use Psr\Container\ContainerInterface;

class BatchCollecteNoteControllerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private Client $client;
    private ParameterBagInterface $parameterBag;
    private NotesRepository $notesRepository;
    private HotspotsRepository $hotspotsRepository;
    private BatchCollecteNoteController $controller;
    private ContainerInterface $container;

    protected function setUp(): void
    {
        // Mocks setup
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(Client::class);
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

    public function testBatchCollecteNoteHttpError()
    {
        $this->client->method('http')->willReturn(['code' => 404, 'erreur' => 'Not Found']);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', 'laurent.hadjadj@ma-petite-entreprise.fr', 'quality');

        $this->assertEquals(['code' => 404, 'error' => ['Not Found']], $result);
    }

    public function testBatchCollecteNoteDeleteError()
    {
        $this->client->method('http')->willReturn([
            'component' => ['measures' => [['value' => 3.5]]]
        ]);

        $this->notesRepository->method('deleteNotesMavenKey')->willReturn(['code' => 500, 'erreur' => 'Delete error']);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', 'laurent.hadjadj@ma-petite-entreprise.fr', 'quality');

        $this->assertEquals(500, $result['code']);
        $this->assertEquals(['Delete error', 'requête : ' => 'deleteNoteMavenKey'], $result['error']);
    }

    public function testBatchCollecteNoteInsertError()
    {
        $this->client->method('http')->willReturn([
            'component' => ['measures' => [['value' => 3.5]]]
        ]);

        $this->notesRepository->method('deleteNotesMavenKey')->willReturn(['code' => 200]);
        $this->notesRepository->method('insertNotes')->willReturn(['code' => 500, 'erreur' => 'Insert error']);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', 'laurent.hadjadj@ma-petite-entreprise.fr', 'quality');

        $this->assertEquals(500, $result['code']);
        $this->assertEquals(['Insert error', 'requête : ' => 'insertNote'], $result['error']);
    }

    public function testBatchCollecteNoteSuccess()
    {
        $this->client->method('http')->willReturn([
            'component' => ['measures' => [['value' => 4.2]]]
        ]);

        $this->notesRepository->method('deleteNotesMavenKey')->willReturn(['code' => 200]);
        $this->notesRepository->method('insertNotes')->willReturn(['code' => 200]);

        $result = $this->controller->batchCollecteNote('some-maven-key', 'COLLECTE', 'laurent.hadjadj@ma-petite-entreprise.fr', 'quality');
        $expectedData = ['note_quality' => 'D'];

        $this->assertEquals(['code' => 200, 'message' => ['value' => 'D'], 'data' => $expectedData], $result);
    }

    public function testBatchCollecteNoteHotspotCountError()
    {
        $this->hotspotsRepository->method('countHotspotsStatus')
            ->withConsecutive(
                [['maven_key' => 'some-maven-key', 'status' => 'TO_REVIEW']],
                [['maven_key' => 'some-maven-key', 'status' => 'REVIEWED']]
            )
            ->willReturnOnConsecutiveCalls(
                ['code' => 500, 'erreur' => 'Error fetching to_review'],
                ['code' => 500, 'erreur' => 'Error fetching reviewed']
            );

        $result = $this->controller->BatchCollecteNoteHotspot('some-maven-key');

        $this->assertEquals(['code' => 500, 'error' => ['Error fetching to_review', 'requête : ' => 'countHotspotsStatus(TO_REVIEW)']], $result);
    }

    public function testBatchCollecteNoteHotspotSuccess()
    {
        $this->hotspotsRepository->method('countHotspotsStatus')
            ->withConsecutive(
                [['maven_key' => 'some-maven-key', 'status' => 'TO_REVIEW']],
                [['maven_key' => 'some-maven-key', 'status' => 'REVIEWED']]
            )
            ->willReturnOnConsecutiveCalls(
                ['code' => 200, 'nombre' => [['nombre' => 20]]],
                ['code' => 200, 'nombre' => [['nombre' => 15]]]
            );

        $result = $this->controller->BatchCollecteNoteHotspot('some-maven-key');
        $expectedData = ['note_hotspot' => 'A'];

        $this->assertEquals(['code' => 200, 'message' => ['note_hotspot' => 'A'], 'data' => $expectedData], $result);
    }
}
