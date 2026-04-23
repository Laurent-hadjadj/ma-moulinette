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
 * [Description BatchExecutionJournalCaseTest]
 */
class BatchExecutionJournalCaseTest extends TestCase
{
    private function getBatchExecution(): BatchExecution
    {
        return new BatchExecution(
            nomTraitement: 'job',
            executionId: new Ulid(),
            traitementId: new Ulid(),
            utilisateurCollecte: 'admin@ma-moulinette.fr',
            modeCollecte: 'COLLECTE'
        );
    }

    private function getEntity(): BatchExecutionJournal
    {
        return new BatchExecutionJournal(
            nomProjet: 'ma-moulinette',
            portefeuille: 'Equipe DEV',
            compteRendu: '<p>OK</p>',
            batchExecution: $this->getBatchExecution(),
            dateExecution: new \DateTimeImmutable('2024-04-12 16:23:11'),
            code: 200
        );
    }

    public function testConstructorAssignsValues(): void
    {
        $entity = $this->getEntity();
        $this->assertSame('ma-moulinette', $entity->getNomProjet());
        $this->assertSame('Equipe DEV', $entity->getPortefeuille());
        $this->assertSame(200, $entity->getCode());
        $this->assertEquals(new \DateTimeImmutable('2024-04-12 16:23:11'), $entity->getDateExecution());
    }

    public function testSettingAndGettingId(): void
    {
        $entity = $this->getEntity();
        $entity->setId(42);
        $this->assertSame(42, $entity->getId());
    }

    public function testSettingAndGettingNomProjet(): void
    {
        $entity = $this->getEntity();
        $entity->setNomProjet('autre-projet');
        $this->assertSame('autre-projet', $entity->getNomProjet());
    }

    public function testSettingAndGettingPortefeuille(): void
    {
        $entity = $this->getEntity();
        $entity->setPortefeuille('Equipe RECETTE');
        $this->assertSame('Equipe RECETTE', $entity->getPortefeuille());
    }

    public function testSettingAndGettingCode(): void
    {
        $entity = $this->getEntity();
        $entity->setCode(500);
        $this->assertSame(500, $entity->getCode());
    }

    public function testSettingAndGettingDateExecution(): void
    {
        $entity = $this->getEntity();
        $date = new \DateTimeImmutable('2025-01-15 10:00:00+01:00');
        $entity->setDateExecution($date);
        $this->assertEquals($date, $entity->getDateExecution());
    }

    public function testSettingAndGettingBatchExecution(): void
    {
        $entity = $this->getEntity();
        $other = $this->getBatchExecution();
        $entity->setBatchExecution($other);
        $this->assertSame($other, $entity->getBatchExecution());
    }

    /**
     * setCompteRendu compresse le HTML via gzencode ;
     * getCompteRendu doit le decompresser pour retourner la valeur originale.
     */
    public function testCompteRenduRoundTrip(): void
    {
        $entity = $this->getEntity();
        $html = '<html><body>Rapport batch</body></html>';
        $entity->setCompteRendu($html);
        $this->assertSame($html, $entity->getCompteRendu());
        $this->assertNotSame($html, $entity->getCompteRenduBrut(), 'getCompteRenduBrut doit retourner le contenu compresse');
    }

    public function testIsSuccessAndIsError(): void
    {
        $entity = $this->getEntity();
        $entity->setCode(200);
        $this->assertTrue($entity->isSuccess());
        $this->assertFalse($entity->isError());

        $entity->setCode(500);
        $this->assertFalse($entity->isSuccess());
        $this->assertTrue($entity->isError());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass($this->getEntity());
        $this->assertEquals(7, count($reflectionClass->getProperties()));
    }
}
