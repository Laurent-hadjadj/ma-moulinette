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

namespace App\Tests\Unit\Service;

use App\Entity\Historique;
use App\Entity\InformationProjet;
use App\Repository\HistoriqueRepository;
use App\Repository\InformationProjetRepository;
use App\Service\IsValideMavenKey;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

/**
 * [Description IsValideMavenKeyTest]
 */
class IsValideMavenKeyTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private IsValideMavenKey $service;

    private static string $notFound = 'Not Found';

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->service = new IsValideMavenKey($this->entityManager);
    }

    // MODIF 2026-05-16 : expects($this->once())
    // ajoute avant ->method(...)->with(...) sur les 4 tests ci-dessous.
    // PHPUnit 14 supprime l'usage de with() sans expects() explicite.

    public function testIsValideInformationSuccess(): void
    {
        $mavenKey = 'valid-maven-key';

        $repository = $this->createMock(InformationProjetRepository::class);
        $repository->expects($this->once())->method('selectInformationProjetIsValide')->with(['maven_key' => $mavenKey])
            ->willReturn(['code' => 200, 'is_valide' => true]);

        $this->entityManager->expects($this->once())->method('getRepository')->with(InformationProjet::class)
            ->willReturn($repository);

        $result = $this->service->isValideInformation($mavenKey);

        $this->assertEquals(['code' => 200, 'request' => true], $result);
    }

    public function testIsValideInformationError(): void
    {
        $mavenKey = 'invalid-maven-key';

        $repository = $this->createMock(InformationProjetRepository::class);
        $repository->expects($this->once())->method('selectInformationProjetIsValide')->with(['maven_key' => $mavenKey])
            ->willReturn(['code' => 404, 'erreur' => self::$notFound]);

        $this->entityManager->expects($this->once())->method('getRepository')->with(InformationProjet::class)
            ->willReturn($repository);

        $result = $this->service->isValideInformation($mavenKey);

        $this->assertEquals(['code' => 404, 'request' => self::$notFound], $result);
    }

    public function testIsValideHistoriqueSuccess(): void
    {
        $mavenKey = 'valid-maven-key';

        $repository = $this->createMock(HistoriqueRepository::class);
        $repository->expects($this->once())->method('selectHistoriqueIsValide')->with(['maven_key' => $mavenKey])
            ->willReturn(['code' => 200, 'is_valide' => true]);

        $this->entityManager->expects($this->once())->method('getRepository')->with(Historique::class)
            ->willReturn($repository);

        $result = $this->service->isValideHistorique($mavenKey);

        $this->assertEquals(['code' => 200, 'request' => true], $result);
    }

    public function testIsValideHistoriqueError(): void
    {
        $mavenKey = 'invalid-maven-key';

        $repository = $this->createMock(HistoriqueRepository::class);
        $repository->expects($this->once())->method('selectHistoriqueIsValide')->with(['maven_key' => $mavenKey])
            ->willReturn(['code' => 404, 'erreur' => self::$notFound]);

        $this->entityManager->expects($this->once())->method('getRepository')->with(Historique::class)
            ->willReturn($repository);

        $result = $this->service->isValideHistorique($mavenKey);

        $this->assertEquals(['code' => 404, 'request' => self::$notFound], $result);
    }
}
