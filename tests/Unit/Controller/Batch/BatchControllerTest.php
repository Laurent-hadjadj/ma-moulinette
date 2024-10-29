<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchController;
use App\Entity\BatchTraitement;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class BatchControllerTest extends WebTestCase
{
    private $em;
    private $controller;
    private $flashBag;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Mock EntityManagerInterface
        $this->em = $this->createMock(EntityManagerInterface::class);
        
        // Mock FlashBagInterface
        $this->flashBag = $this->createMock(FlashBagInterface::class);
        
        // Instantiate the controller with mocked dependencies
        $this->controller = new BatchController($this->em);
    }

    public function testTraitementSuiviWithoutRole()
    {
        $request = new Request();

        // Mock the isGranted method to simulate lack of ROLE_BATCH
        $this->controller->method('isGranted')->willReturn(false);
        
        // Mock the getParameter method to return dummy data
        $this->controller->method('getParameter')->willReturnMap([
            ['csrf.salt', 'dummy_salt'],
            ['version', '1.0']
        ]);
        
        // Mock the render method to return a dummy response
        $response = $this->controller->traitementSuivi($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('Vous devez avoir le rôle', $response->getContent());
    }

    public function testTraitementSuiviWithErrorFetchingLastBatch()
    {
        $request = new Request();

        // Mock the isGranted method to simulate having ROLE_BATCH
        $this->controller->method('isGranted')->willReturn(true);
        
        // Mock the getParameter method to return dummy data
        $this->controller->method('getParameter')->willReturnMap([
            ['csrf.salt', 'dummy_salt'],
            ['version', '1.0']
        ]);

        // Mock the repository methods to return errors
        $batchTraitementRepository = $this->createMock(\App\Repository\BatchTraitementRepository::class);
        $batchTraitementRepository->method('selectBatchTraitementDateEnregistrementLast')->willReturn(['code' => 500, 'erreur' => 'Error fetching last batch']);
        $batchTraitementRepository->method('selectBatchTraitementLast')->willReturn(['code' => 200, 'liste' => []]);
        $this->em->method('getRepository')->willReturn($batchTraitementRepository);

        // Mock the addFlash method
        $this->controller->method('addFlash')->willReturn(null);

        $response = $this->controller->traitementSuivi($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('Nous avons rencontré une erreur inattendue', $response->getContent());
    }

    public function testTraitementSuiviWithNoBatchFound()
    {
        $request = new Request();

        // Mock the isGranted method to simulate having ROLE_BATCH
        $this->controller->method('isGranted')->willReturn(true);
        
        // Mock the getParameter method to return dummy data
        $this->controller->method('getParameter')->willReturnMap([
            ['csrf.salt', 'dummy_salt'],
            ['version', '1.0']
        ]);

        // Mock the repository methods to return valid but empty data
        $batchTraitementRepository = $this->createMock(\App\Repository\BatchTraitementRepository::class);
        $batchTraitementRepository->method('selectBatchTraitementDateEnregistrementLast')->willReturn(['code' => 200, 'liste' => []]);
        $batchTraitementRepository->method('selectBatchTraitementLast')->willReturn(['code' => 200, 'liste' => []]);
        $this->em->method('getRepository')->willReturn($batchTraitementRepository);

        // Mock the addFlash method
        $this->controller->method('addFlash')->willReturn(null);

        $response = $this->controller->traitementSuivi($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('Aucun traitement trouvé pour aujourd\'hui.', $response->getContent());
    }

    public function testTraitementSuiviSuccess()
    {
        $request = new Request();

        // Mock the isGranted method to simulate having ROLE_BATCH
        $this->controller->method('isGranted')->willReturn(true);
        
        // Mock the getParameter method to return dummy data
        $this->controller->method('getParameter')->willReturnMap([
            ['csrf.salt', 'dummy_salt'],
            ['version', '1.0']
        ]);

        // Mock the repository methods to return valid data
        $batchTraitementRepository = $this->createMock(\App\Repository\BatchTraitementRepository::class);
        $batchTraitementRepository->method('selectBatchTraitementDateEnregistrementLast')->willReturn([
            'code' => 200,
            'liste' => [['date' => '2024-08-09 12:00:00']]
        ]);
        $batchTraitementRepository->method('selectBatchTraitementLast')->willReturn([
            'code' => 200,
            'liste' => [
                [
                    'debut' => '2024-08-09 10:00:00',
                    'fin' => '2024-08-09 11:00:00',
                    'resultat' => 1,
                    'demarrage' => 'Auto',
                    'titre' => 'Traitement 1',
                    'portefeuille' => 'Portefeuille 1',
                    'projet' => 'Projet 1',
                    'responsable' => 'Responsable 1'
                ]
            ]
        ]);
        $this->em->method('getRepository')->willReturn($batchTraitementRepository);

        // Mock the addFlash method
        $this->controller->method('addFlash')->willReturn(null);

        $response = $this->controller->traitementSuivi($request);
        $this->assertInstanceOf(Response::class, $response);
        $this->assertStringContainsString('Tout va bien !', $response->getContent());
    }
}
