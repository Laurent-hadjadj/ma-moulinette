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

namespace App\Tests\Unit\Controller\Batch;

use App\Controller\Batch\BatchAutoController;
use App\Controller\Batch\CollecteController;
use App\Repository\BatchTraitementRepository;
use App\Service\ListeProjetPortefeuilleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;

#[AllowMockObjectsWithoutExpectations]
class BatchAutoControllerTest extends TestCase
{
    private const TOKEN = 'secret-token';

    /** @var CollecteController&MockObject */            private MockObject $collecte;
    /** @var ParameterBagInterface&MockObject */         private MockObject $params;
    /** @var EntityManagerInterface&MockObject */        private MockObject $em;
    /** @var LoggerInterface&MockObject */               private MockObject $logger;
    /** @var LoggerInterface&MockObject */               private MockObject $profiler;
    /** @var ListeProjetPortefeuilleService&MockObject */private MockObject $listeProjetService;
    /** @var BatchTraitementRepository&MockObject */     private MockObject $batchTraitementRepo;

    private BatchAutoController $controller;

    protected function setUp(): void
    {
        $this->collecte = $this->createMock(CollecteController::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->profiler = $this->createMock(LoggerInterface::class);
        $this->listeProjetService = $this->createMock(ListeProjetPortefeuilleService::class);
        $this->batchTraitementRepo = $this->createMock(BatchTraitementRepository::class);

        $this->em->method('getRepository')->willReturn($this->batchTraitementRepo);
        $this->params->method('get')->willReturnMap([
            ['api.client_token', self::TOKEN],
        ]);

        $this->controller = new BatchAutoController(
            $this->collecte,
            $this->params,
            $this->em,
            $this->logger,
            $this->profiler,
            $this->listeProjetService
        );
    }

    /* ============ traitementListe ============ */

    public function testListeReturns400OnInvalidJson(): void
    {
        $request = $this->jsonRequest('not-json');

        $response = $this->controller->traitementListe($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
        $this->assertSame('error', $data['type']);
    }

    public function testListeReturns400WhenTokenMissing(): void
    {
        $request = $this->jsonRequest(['foo' => 'bar']);

        $response = $this->controller->traitementListe($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testListeReturns403WhenTokenIsWrong(): void
    {
        $request = $this->jsonRequest(['token' => 'wrong']);

        $response = $this->controller->traitementListe($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testListeReturnsErrorWhenRepoFails(): void
    {
        $this->batchTraitementRepo->expects($this->once())
            ->method('selectBatchTraitementAutomatiqueListe')
            ->willReturn(['code' => 500, 'erreur' => 'db down']);

        $response = $this->controller->traitementListe($this->jsonRequest(['token' => self::TOKEN]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('db down', $data['trace']);
    }

    public function testListeHappyPath(): void
    {
        $this->batchTraitementRepo->expects($this->once())
            ->method('selectBatchTraitementAutomatiqueListe')
            ->willReturn(['code' => 200, 'liste' => [['id' => 1, 'nom' => 'T1']]]);

        $response = $this->controller->traitementListe($this->jsonRequest(['token' => self::TOKEN]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertCount(1, $data['liste_traitement']);
    }

    /* ============ traitementAuto ============ */

    public function testAutoReturns400WhenJsonIsInvalid(): void
    {
        $response = $this->controller->traitementAuto($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testAutoReturns400WhenFieldsAreMissing(): void
    {
        // token only, missing nom_traitement/portefeuille/traitement_id
        $response = $this->controller->traitementAuto($this->jsonRequest(['token' => self::TOKEN]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testAutoReturns403WhenTokenIsWrong(): void
    {
        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => 'bad',
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testAutoReturns404WhenProjectListIsEmpty(): void
    {
        $this->listeProjetService->expects($this->once())
            ->method('listeProjet')
            ->willReturn([
                'code' => 404,
                'type' => 'warning',
                'message' => 'empty',
                'erreur' => 'no rows',
            ]);

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $data['code']);
        $this->assertSame('warning', $data['type']);
    }

    public function testAutoReturnsErrorWhenUpdateFailsBeforeLoop(): void
    {
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['fr.ma-moulinette:ma-moulinette'],
        ]);

        $this->batchTraitementRepo->expects($this->once())
            ->method('updateBatchTraitement')
            ->willReturn(['code' => 500, 'erreur' => 'update fail']);

        $this->collecte->expects($this->never())->method('collecte');

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('update fail', $data['erreur']);
    }

    public function testAutoAbortsLoopWhenCollecteReturns500(): void
    {
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['fr.ma-moulinette:ma-moulinette', 'fr.ma-moulinette:projet-b'],
        ]);

        // First update OK (pre-loop), second update on 500 failure inside loop
        $this->batchTraitementRepo->method('updateBatchTraitement')->willReturn(['code' => 200]);

        $this->collecte->expects($this->once())
            ->method('collecte')
            ->willReturn(['code' => 500, 'compte_rendu' => 'boom']);

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertStringContainsString("collecte du projet fr.ma-moulinette:ma-moulinette", $data['message']);
    }

    public function testAutoHappyPathReturnsReferenceAfterFullLoop(): void
    {
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['fr.ma-moulinette:ma-moulinette'],
        ]);

        // Both updateBatchTraitement calls succeed (before + after loop)
        $this->batchTraitementRepo->method('updateBatchTraitement')->willReturn(['code' => 200]);

        $this->collecte->expects($this->once())
            ->method('collecte')
            ->willReturn(['code' => 200, 'compte_rendu' => 'ok']);

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertStringContainsString('succès', $data['message']);
    }

    public function testAutoReturnsErrorWhenFinalUpdateFails(): void
    {
        $this->listeProjetService->method('listeProjet')->willReturn([
            'code' => 200,
            'liste' => ['fr.ma-moulinette:ma-moulinette'],
        ]);

        $this->batchTraitementRepo->method('updateBatchTraitement')->willReturnOnConsecutiveCalls(
            ['code' => 200],
            ['code' => 500, 'erreur' => 'final update fail'],
        );

        $this->collecte->method('collecte')->willReturn(['code' => 200, 'compte_rendu' => 'ok']);

        $response = $this->controller->traitementAuto($this->jsonRequest([
            'token' => self::TOKEN,
            'nom_traitement' => 'T',
            'portefeuille' => 'P',
            'traitement_id' => '01HK0000000000000000000000',
        ]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    /* ============ helper ============ */

    private function jsonRequest(array|string $body): Request
    {
        $content = is_string($body) ? $body : json_encode($body, JSON_FORCE_OBJECT);
        return new Request([], [], [], [], [], [], $content);
    }
}
