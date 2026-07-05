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

    /* MODIF 2026-05-05 : adaptation au remaniement de BatchExecutionJournal (constructor
     * implicite, pas d'arguments nommes). Utilisation des setters. */
    private function getEntity(): BatchExecutionJournal
    {
        $entity = new BatchExecutionJournal();
        $entity->setNomProjet('ma-moulinette');
        $entity->setPortefeuille('Equipe DEV');
        $entity->setCompteRendu('<p>OK</p>');
        $entity->setJob($this->getBatchExecution());
        $entity->setDateExecution(new \DateTimeImmutable('2024-04-12 16:23:11'));
        $entity->setCode(200);
        return $entity;
    }

    public function testEntityValuesAreSet(): void
    {
        $entity = $this->getEntity();
        $this->assertSame('ma-moulinette', $entity->getNomProjet());
        $this->assertSame('Equipe DEV', $entity->getPortefeuille());
        $this->assertSame(200, $entity->getCode());
        $this->assertEquals(new \DateTimeImmutable('2024-04-12 16:23:11'), $entity->getDateExecution());
    }

    /* MODIF 2026-05-05 : pas de setId expose (ID auto-genere). */
    public function testIdHasNoSetter(): void
    {
        $entity = $this->getEntity();
        $this->assertFalse(method_exists($entity, 'setId'),
            'BatchExecutionJournal::setId() ne devrait pas exister (ID auto-genere).');
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

    /* MODIF 2026-05-05 : la relation s'appelle desormais `job` (setJob/getJob). */
    public function testSettingAndGettingJob(): void
    {
        $entity = $this->getEntity();
        $other = $this->getBatchExecution();
        $entity->setJob($other);
        $this->assertSame($other, $entity->getJob());
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

    /* MODIF 2026-05-05 : test de regression.
     * La colonne `nomProjet` doit avoir length: 128 dans le mapping Doctrine pour rester
     * coherente avec le DDL batch_execution_journal.sql:18 (VARCHAR(128) NOT NULL).
     * Sans length explicite, Doctrine genererait du VARCHAR(255) (divergence). */
    /* MODIF 2026-06-08 : getId sur entité non-persistée → property int non initialisée → Error. */
    public function testGetIdThrowsBeforePersist(): void
    {
        $entity = new BatchExecutionJournal();
        $this->expectException(\Error::class);
        $entity->getId();
    }

    /* MODIF 2026-06-08 [coverage-batch-entities] : getCompteRendu retourne '' quand compteRendu vaut null (via Reflection).
     * setAccessible() supprimé : no-op depuis PHP 8.1, déprécié PHP 8.5. */
    public function testGetCompteRenduReturnsEmptyStringWhenNull(): void
    {
        $entity = new BatchExecutionJournal();
        $ref = new \ReflectionProperty($entity, 'compteRendu');
        $ref->setValue($entity, null);
        $this->assertSame('', $entity->getCompteRendu());
    }

    public function testNomProjetMappingIsLength128(): void
    {
        $reflection = new \ReflectionProperty(BatchExecutionJournal::class, 'nomProjet');
        $attributes = $reflection->getAttributes(\Doctrine\ORM\Mapping\Column::class);
        $this->assertCount(1, $attributes);

        $args = $attributes[0]->getArguments();
        $this->assertSame(128, $args['length'] ?? null,
            'BatchExecutionJournal::$nomProjet doit avoir length: 128 (alignement DDL).');
    }
}
