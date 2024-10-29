<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchManuelController;
use App\Service\FileLogger;
use App\Service\RabbitMQService;
use App\Controller\Batch\CollecteController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\SecurityBundle\Security;

class BatchManuelControllerTest extends WebTestCase
{
    private $em;
    private $logger;
    private $collecte;
    private $security;
    private $rabbitMQService;
    private $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock dependencies
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(FileLogger::class);
        $this->collecte = $this->createMock(CollecteController::class);
        $this->security = $this->createMock(Security::class);
        $this->rabbitMQService = $this->createMock(RabbitMQService::class);

        // Instantiate the controller with mocked dependencies
        $this->controller = new BatchManuelController(
            $this->em,
            $this->logger,
            $this->collecte,
            $this->security,
            $this->rabbitMQService
        );
    }

    public function testGetMessageCount()
    {
        $queueName = 'testQueue';
        $messageCount = 5;

        // Mock RabbitMQService method
        $this->rabbitMQService->method('getMessageCount')->willReturn($messageCount);

        $response = $this->controller->getMessageCount($queueName);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(json_encode(['nombre' => $messageCount]), $response->getContent());
    }

    public function testSendMessage()
    {
        $queueName = 'testQueue';
        $message = 'Hello, RabbitMQ!';

        // Mock RabbitMQService methods
        $this->rabbitMQService->expects($this->once())->method('sendMessage')->with($queueName, $message);
        $this->rabbitMQService->expects($this->once())->method('close');

        $response = $this->controller->sendMessage($queueName);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals('Message sent to RabbitMQ!', $response->getContent());
    }

    public function testLireJournalWithInvalidData()
    {
        $request = new Request([], [], [], [], [], [], json_encode(['invalid' => 'data']));
        $response = $this->controller->lireJournal($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'code' => 400,
                'type' => 'alert',
                'reference' => BatchManuelController::$reference,
                'message' => BatchManuelController::$erreur400
            ]),
            $response->getContent()
        );
    }

    public function testLireJournalSuccess()
    {
        $portefeuille = 'testPortefeuille';
        $type = 'testType';
        $journalContent = ['recherche' => 'search result', 'content' => 'journal content'];

        // Mock FileLogger methods
        $this->logger->method('downloadContent')->willReturn($journalContent);

        $request = new Request([], [], [], [], [], [], json_encode(['portefeuille' => $portefeuille, 'type' => $type]));
        $response = $this->controller->lireJournal($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'code' => 200,
                'recherche' => $journalContent['recherche'],
                'journal' => $journalContent['content']
            ]),
            $response->getContent()
        );
    }

    public function testEffaceJournalWithInvalidData()
    {
        $request = new Request([], [], [], [], [], [], json_encode(['invalid' => 'data']));
        $response = $this->controller->effaceJournal($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'code' => 400,
                'type' => 'alert',
                'reference' => BatchManuelController::$reference,
                'message' => BatchManuelController::$erreur400
            ]),
            $response->getContent()
        );
    }

    public function testEffaceJournalSuccess()
    {
        $portefeuille = 'testPortefeuille';
        $type = 'testType';

        // Mock FileLogger methods
        $this->logger->expects($this->once())->method('log')->with($portefeuille, $type, 'delete');

        $request = new Request([], [], [], [], [], [], json_encode(['portefeuille' => $portefeuille, 'type' => $type]));
        $response = $this->controller->effaceJournal($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['code' => 200]),
            $response->getContent()
        );
    }

    public function testListeProjetWithValidData()
    {
        $titrePortefeuille = 'testTitle';
        $portefeuille = 'testPortefeuille';
        $result = ['code' => 200, 'liste' => ['project1', 'project2']];

        // Mock repository methods
        $batchTraitementRepository = $this->createMock(\App\Repository\BatchTraitementRepository::class);
        $portefeuilleRepository = $this->createMock(\App\Repository\PortefeuilleRepository::class);

        $batchTraitementRepository->method('SelectBatchTraitement')->willReturn(['code' => 200, 'liste' => ['project1']]);
        $portefeuilleRepository->method('selectPortefeuille')->willReturn(['code' => 200, 'liste' => ['["project1", "project2"]']]);

        $this->em->method('getRepository')->willReturnMap([
            [Portefeuille::class, $portefeuilleRepository],
            [BatchTraitement::class, $batchTraitementRepository]
        ]);

        $result = $this->controller->listeProjet($titrePortefeuille, $portefeuille);
        $this->assertEquals(['code' => 200, 'project1', 'project2'], $result);
    }

    public function testTraitementManuelWithoutRole()
    {
        $request = new Request([], [], [], [], [], [], json_encode(['titre_portefeuille' => 'testTitle', 'portefeuille' => 'testPortefeuille']));

        // Mock denyAccessUnlessGranted to simulate lack of ROLE_BATCH
        $this->controller->method('denyAccessUnlessGranted')->will($this->throwException(new \Symfony\Component\Security\Core\Exception\AccessDeniedException()));

        $response = $this->controller->traitementManuel($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode([
                'code' => 403,
                'message' => 'L’utilisateur essaye d’accéder à la page sans avoir le rôle ROLE_BATCH'
            ]),
            $response->getContent()
        );
    }

    public function testTraitementManuelSuccess()
    {
        $request = new Request([], [], [], [], [], [], json_encode(['titre_portefeuille' => 'testTitle', 'portefeuille' => 'testPortefeuille']));

        // Mock security methods
        $this->security->method('getUser')->willReturn((object) ['getCourriel' => 'test@example.com']);

        // Mock CollecteController method
        $this->collecte->method('collecte')->willReturn(['code' => 200]);

        // Mock listeProjet method
        $this->controller->method('listeProjet')->willReturn(['code' => 200, 'project1']);

        $response = $this->controller->traitementManuel($request);
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString(
            json_encode(['code' => 200]),
            $response->getContent()
        );
    }
}
