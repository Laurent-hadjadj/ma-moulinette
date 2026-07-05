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

    /* MODIF 2026-05-05 [fix-ulid-nullable] : test de regression — le constructor exige
     * desormais des Ulid non-nullables (alignement DDL NOT NULL). Passer null doit lever TypeError. */
    public function testConstructorRejectsNullExecutionId(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line on teste explicitement un appel invalide */
        new BatchExecution('job', null, new Ulid(), null, 'COLLECTE');
    }

    public function testConstructorRejectsNullTraitementId(): void
    {
        $this->expectException(\TypeError::class);
        /** @phpstan-ignore-next-line on teste explicitement un appel invalide */
        new BatchExecution('job', new Ulid(), null, null, 'COLLECTE');
    }

    /* L'id est genere par la base (typed property non initialisee avant persist).
     * On verifie juste l'absence du setter setId : l'ID ne doit pas etre exposable. */
    public function testIdHasNoSetter(): void
    {
        $entity = $this->getEntity();
        $this->assertFalse(method_exists($entity, 'setId'),
            'BatchExecution::setId() ne devrait pas exister (ID auto-genere).');
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

    /* MODIF 2026-05-05 : adaptation au remaniement de BatchExecutionJournal (constructor
     * implicite, pas d'arguments nommes). On utilise les setters au lieu d'un constructor. */
    public function testAddJournal(): void
    {
        $entity = $this->getEntity();
        $journal = new BatchExecutionJournal();
        $journal->setNomProjet('projet1');
        $journal->setPortefeuille('Equipe DEV');
        $journal->setCompteRendu('<p>OK</p>');
        $journal->setDateExecution(new \DateTimeImmutable());
        $journal->setCode(200);

        $entity->addJournal($journal);
        $this->assertCount(1, $entity->getCollectes());
        $this->assertSame($entity, $journal->getJob());
    }

    /* MODIF 2026-06-08 : couvre addCollecte (distinct de addJournal : pas de check contains). */
    public function testAddCollecteAddsJournalUnconditionally(): void
    {
        $entity = $this->getEntity();
        $journal = new BatchExecutionJournal();
        $journal->setNomProjet('project1');
        $journal->setPortefeuille('P1');
        $journal->setCompteRendu('<p>OK</p>');
        $journal->setDateExecution(new \DateTimeImmutable());
        $journal->setCode(200);

        $entity->addCollecte($journal);
        $this->assertCount(1, $entity->getCollectes());
        $this->assertSame($entity, $journal->getJob());
    }

    /* MODIF 2026-06-08 : couvre getId (nullable, null avant persist). */
    public function testGetIdReturnsNullByDefault(): void
    {
        $entity = $this->getEntity();
        $this->assertNull($entity->getId());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass($this->getEntity());
        $this->assertEquals(8, count($reflectionClass->getProperties()));
    }
}
