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

use App\Entity\BatchProfiling;
use PHPUnit\Framework\TestCase;

/**
 * [Description BatchProfilingCaseTest]
 *
 * v2.0.0 : entite quasi-immutable. Seul setId est mutable, les autres champs
 * sont fixes via le constructeur.
 */
class BatchProfilingCaseTest extends TestCase
{
    private function getEntity(): BatchProfiling
    {
        return new BatchProfiling(
            portefeuille: 'Equipe DEV',
            nbProjets: 15,
            tempsTotal: 42.5,
            tempsMoyen: 2.83,
            memoirePeak: 128.5,
            memoireMoyenne: 96.2,
            utilisateur: 'admin@ma-moulinette.fr',
            executionReference: '01HK7XMKQGM3F5XZJ4S6T7VWE2'
        );
    }

    public function testConstructorAssignsAllValues(): void
    {
        $entity = $this->getEntity();
        $this->assertSame('Equipe DEV', $entity->getPortefeuille());
        $this->assertSame(15, $entity->getNbProjets());
        $this->assertSame(42.5, $entity->getTempsTotal());
        $this->assertSame(2.83, $entity->getTempsMoyen());
        $this->assertSame(128.5, $entity->getMemoirePeak());
        $this->assertSame(96.2, $entity->getMemoireMoyenne());
        $this->assertSame('admin@ma-moulinette.fr', $entity->getUtilisateur());
        $this->assertSame('01HK7XMKQGM3F5XZJ4S6T7VWE2', $entity->getExecutionReference());
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getDateExecution());
    }

    public function testExecutionReferenceCanBeNull(): void
    {
        $entity = new BatchProfiling('P', 1, 1.0, 1.0, 1.0, 1.0, 'u@ma-moulinette.fr');
        $this->assertNull($entity->getExecutionReference());
    }

    /* MODIF 2026-05-06 : pas de setId expose (ID auto-genere). */
    public function testIdHasNoSetter(): void
    {
        $entity = $this->getEntity();
        $this->assertFalse(method_exists($entity, 'setId'),
            'BatchProfiling::setId() ne devrait pas exister (ID auto-genere).');
    }

    public function testGetDateExecutionFormatted(): void
    {
        $entity = $this->getEntity();
        $formatted = $entity->getDateExecutionFormatted('Y-m-d');
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $formatted);
    }

    public function testToString(): void
    {
        $entity = $this->getEntity();
        $str = (string) $entity;
        $this->assertStringContainsString('Equipe DEV', $str);
        $this->assertStringContainsString('admin@ma-moulinette.fr', $str);
        $this->assertStringContainsString('15 projets', $str);
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
        $this->assertEquals(10, count($reflectionClass->getProperties()));
    }
}
