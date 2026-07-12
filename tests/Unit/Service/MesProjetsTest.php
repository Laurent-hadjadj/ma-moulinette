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

use App\Entity\ListeProjet;
use App\Repository\ListeProjetRepository;
use App\Service\MesProjets;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class MesProjetsTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var ListeProjetRepository&MockObject */
    private MockObject $repository;

    private MesProjets $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repository = $this->createMock(ListeProjetRepository::class);

        $this->em->expects($this->once())
            ->method('getRepository')
            ->with(ListeProjet::class)
            ->willReturn($this->repository);

        $this->service = new MesProjets($this->em);
    }

    public function testListeReturnsProjectsWhenRepositoryReturnsNonEmptyListe(): void
    {
        $projets = [
            ['maven_key' => 'fr.ma-moulinette:projet-a', 'name' => 'App'],
            ['maven_key' => 'fr.ma-moulinette:projet-b', 'name' => 'API'],
        ];

        $this->repository->expects($this->once())
            ->method('selectListeProjetByGroupe')
            ->with(['team-a'])
            ->willReturn(['code' => 200, 'liste' => $projets]);

        $this->assertSame(
            ['code' => 200, 'projets' => $projets],
            $this->service->liste(['team-a'])
        );
    }

    public function testListeReturns406WhenRepositoryReturnsEmptyListe(): void
    {
        $this->repository->expects($this->once())
            ->method('selectListeProjetByGroupe')
            ->willReturn(['code' => 200, 'liste' => []]);

        $result = $this->service->liste(['team-a']);

        $this->assertSame(406, $result['code']);
        $this->assertSame(MesProjets::$erreur406, $result['message']);
    }

    public function testListePropagatesRepositoryErrorWhenCodeIsNot200(): void
    {
        $this->repository->expects($this->once())
            ->method('selectListeProjetByGroupe')
            ->willReturn(['code' => 500, 'erreur' => 'DB down']);

        $this->assertSame(
            ['code' => 500, 'erreur' => 'DB down'],
            $this->service->liste(['team-a'])
        );
    }

    public function testListeSkipsGroupesEqualToLiteralNullString(): void
    {
        $this->repository->expects($this->once())
            ->method('selectListeProjetByGroupe')
            ->with(['team-a', 'team-b'])
            ->willReturn(['code' => 200, 'liste' => [['x' => 1]]]);

        // 'null' en tant que chaîne (sentinelle "pas de groupe") est ignoré
        $this->service->liste(['team-a', 'null', 'team-b']);
    }

    public function testListeLowercasesGroupesAndReplacesSpacesWithDashes(): void
    {
        $this->repository->expects($this->once())
            ->method('selectListeProjetByGroupe')
            ->with(['my-big-team', 'other-squad'])
            ->willReturn(['code' => 200, 'liste' => [['x' => 1]]]);

        $this->service->liste(['  My Big Team  ', 'OTHER SQUAD']);
    }

    public function testListeWithOnlyNullSentinelProducesEmptyClause(): void
    {
        $this->repository->expects($this->once())
            ->method('selectListeProjetByGroupe')
            ->with([])
            ->willReturn(['code' => 200, 'liste' => [['x' => 1]]]);

        $this->service->liste(['null']);
    }
}
