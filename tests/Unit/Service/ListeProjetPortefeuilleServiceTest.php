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

namespace App\Tests\Unit\Service;

use App\Entity\Portefeuille;
use App\Repository\PortefeuilleRepository;
use App\Service\ListeProjetPortefeuilleService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ListeProjetPortefeuilleServiceTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var PortefeuilleRepository&MockObject */
    private MockObject $repository;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    private ListeProjetPortefeuilleService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(PortefeuilleRepository::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(Portefeuille::class)
            ->willReturn($this->repository);

        $this->service = new ListeProjetPortefeuilleService($this->em, $this->logger);
    }

    public function testListeProjetReturnsDecodedMavenKeysOnSuccess(): void
    {
        $mavenKeys = ['com.acme:app', 'com.acme:api', 'com.acme:ui'];
        $repoResult = [
            'code' => 200,
            'liste' => [
                ['liste' => json_encode($mavenKeys)],
            ],
        ];

        $this->repository->expects($this->once())
            ->method('selectPortefeuille')
            ->with([
                'portefeuille' => 'Mon PF',
                'groupe_fonctionnel' => 'pf-alpha',
            ])
            ->willReturn($repoResult);

        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        $this->assertSame(
            ['code' => 200, 'liste' => $mavenKeys],
            $this->service->listeProjet('Mon PF', 'pf-alpha')
        );
    }

    public function testListeProjetReturnsEmptyArrayWhenEncodedJsonIsInvalid(): void
    {
        // JSON invalide → json_decode renvoie null → l'opérateur ?? [] prend le relais
        $this->repository->expects($this->once())
            ->method('selectPortefeuille')
            ->willReturn([
                'code' => 200,
                'liste' => [['liste' => 'not-valid-json']],
            ]);

        $this->logger->expects($this->never())->method('error');
        $this->logger->expects($this->never())->method('warning');

        $this->assertSame(
            ['code' => 200, 'liste' => []],
            $this->service->listeProjet('t', 'p')
        );
    }

    public function testListeProjetReturnsErrorAndLogsWhenRepositoryCodeIsNot200(): void
    {
        $this->repository->expects($this->once())
            ->method('selectPortefeuille')
            ->willReturn([
                'code' => 500,
                'message' => 'DB unavailable',
                'erreur' => 'SQLSTATE[XX]',
            ]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '[ListeProjetPortefeuilleService] ❌ Échec de la requête selectPortefeuille',
                $this->callback(function (array $ctx) {
                    return $ctx['code'] === 500
                        && $ctx['message'] === 'DB unavailable'
                        && $ctx['erreur'] === 'SQLSTATE[XX]'
                        && $ctx['portefeuille'] === 'My PF';
                })
            );
        $this->logger->expects($this->never())->method('warning');

        $this->assertSame(
            [
                'code' => 500,
                'type' => 'error',
                'message' => "Le portefeuille de projet n'est pas accessible (Erreur 500).",
                'erreur' => 'SQLSTATE[XX]',
            ],
            $this->service->listeProjet('My PF', 'pf-x')
        );
    }

    public function testListeProjetReturns404AndLogsWarningWhenListeIsEmpty(): void
    {
        $this->repository->expects($this->once())
            ->method('selectPortefeuille')
            ->willReturn([
                'code' => 200,
                'liste' => [], // ← liste vide
            ]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with(
                '[ListeProjetPortefeuilleService] ⚠️ La liste des traitements ne contient pas votre portefeuille !',
                $this->callback(fn (array $ctx) => $ctx['code'] === 200 && $ctx['portefeuille'] === 'My PF')
            );
        $this->logger->expects($this->never())->method('error');

        $this->assertSame(
            [
                'code' => 404,
                'type' => 'warning',
                'message' => "Votre portefeuille ne contient pas ce projet (Erreur 404).",
                'erreur' => 404,
            ],
            $this->service->listeProjet('My PF', 'pf-x')
        );
    }

    public function testListeProjetErrorPathUsesDefaultMessagesWhenRepositoryPayloadIsMinimal(): void
    {
        // Pas de 'message' ni 'erreur' dans la réponse du repository
        $this->repository->expects($this->once())
            ->method('selectPortefeuille')
            ->willReturn(['code' => 503]);

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->anything(),
                $this->callback(function (array $ctx) {
                    return $ctx['message'] === 'Aucun message remonté.'
                        && $ctx['erreur'] === 'Aucune erreur remontée.';
                })
            );

        $result = $this->service->listeProjet('t', 'p');

        $this->assertSame(503, $result['code']);
        $this->assertSame('error', $result['type']);
        $this->assertNull($result['erreur']);
    }
}
