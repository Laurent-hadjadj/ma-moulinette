<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Case;

use App\Entity\BatchExecution;
use App\Entity\BatchExecutionJournal;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * [Description BatchExecutionCaseTest]
 *
 * v2.0.0 : couvre les 8 attributs de l'entite BatchExecution.
 */
class BatchExecutionCaseTest extends TestCase
{
    private function getEntity(): BatchExecution
    {
        return new BatchExecution(
            nomTraitement: 'collecte_quotidienne',
            executionId: new Ulid(),
            traitementId: new Ulid(),
            utilisateurCollecte: 'admin@ma-moulinette.fr',
            modeCollecte: 'COLLECTE'
        );
    }

    public function testConstructorAssignsValues(): void
    {
        $execId = new Ulid();
        $traitId = new Ulid();
        $entity = new BatchExecution('job', $execId, $traitId, 'admin@ma-moulinette.fr', 'COLLECTE');

        $this->assertSame('job', $entity->getNomTraitement());
        $this->assertSame($execId, $entity->getExecutionId());
        $this->assertSame($traitId, $entity->getTraitementId());
        $this->assertSame('admin@ma-moulinette.fr', $entity->getUtilisateurCollecte());
        $this->assertSame('COLLECTE', $entity->getModeCollecte());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getDateEnregistrement());
    }

    public function testConstructorGeneratesUlidsWhenNull(): void
    {
        $entity = new BatchExecution('job', null, null, null, 'COLLECTE');
        $this->assertInstanceOf(Ulid::class, $entity->getExecutionId());
        $this->assertInstanceOf(Ulid::class, $entity->getTraitementId());
    }

    public function testSettingAndGettingId(): void
    {
        $entity = $this->getEntity();
        $entity->setId(42);
        $this->assertSame(42, $entity->getId());
    }

    public function testSettingAndGettingExecutionId(): void
    {
        $entity = $this->getEntity();
        $ulid = new Ulid();
        $entity->setExecution($ulid);
        $this->assertSame($ulid, $entity->getExecutionId());
    }

    public function testSettingAndGettingTraitementId(): void
    {
        $entity = $this->getEntity();
        $ulid = new Ulid();
        $entity->setTraitementId($ulid);
        $this->assertSame($ulid, $entity->getTraitementId());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $entity = $this->getEntity();
        $entity->setModeCollecte('TRAITEMENT MANUEL');
        $this->assertSame('TRAITEMENT MANUEL', $entity->getModeCollecte());
    }

    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $entity = $this->getEntity();
        $entity->setUtilisateurCollecte('user@example.com');
        $this->assertSame('user@example.com', $entity->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = $this->getEntity();
        $date = new \DateTimeImmutable('2024-06-28 17:55:45+02:00');
        $entity->setDateEnregistrement($date);
        $this->assertEquals($date, $entity->getDateEnregistrement());
    }

    public function testAddAndRemoveJournal(): void
    {
        $entity = $this->getEntity();
        $journal = new BatchExecutionJournal(
            nomProjet: 'projet1',
            portefeuille: 'Equipe DEV',
            compteRendu: '<p>OK</p>',
            batchExecution: $entity,
            dateExecution: new \DateTimeImmutable(),
            code: 200
        );
        $entity->addJournal($journal);
        $this->assertCount(1, $entity->getCollectes());
        $this->assertSame($entity, $journal->getBatchExecution());

        $entity->removeJournal($journal);
        $this->assertCount(0, $entity->getCollectes());
        $this->assertNull($journal->getBatchExecution());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass($this->getEntity());
        $this->assertEquals(8, count($reflectionClass->getProperties()));
    }
}
