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

use App\Entity\GroupeFonctionnel;
use PHPUnit\Framework\TestCase;

/**
 * [Description GroupeFonctionnelCaseTest]
 */
class GroupeFonctionnelCaseTest extends TestCase
{
    private function getEntity(): GroupeFonctionnel
    {
        return (new GroupeFonctionnel())
            ->setGroupeFonctionnel('Equipe DEV')
            ->setDescription('Equipe de developpement')
            ->setDateModification(new \DateTime('2024-04-12 10:00:00'));
    }

    public function testSettingAndGettingId(): void
    {
        $entity = $this->getEntity();
        $entity->setId(42);
        $this->assertSame(42, $entity->getId());
    }

    public function testSettingAndGettingGroupeFonctionnel(): void
    {
        $entity = $this->getEntity();
        $entity->setGroupeFonctionnel('Equipe RECETTE');
        $this->assertSame('Equipe RECETTE', $entity->getGroupeFonctionnel());
    }

    public function testSettingAndGettingDescription(): void
    {
        $entity = $this->getEntity();
        $entity->setDescription('Equipe de recette');
        $this->assertSame('Equipe de recette', $entity->getDescription());
    }

    public function testSettingAndGettingDateModification(): void
    {
        $entity = $this->getEntity();
        $date = new \DateTime('2025-01-15 09:00:00');
        $entity->setDateModification($date);
        $this->assertEquals($date, $entity->getDateModification());
    }

    public function testDateModificationCanBeNull(): void
    {
        $entity = new GroupeFonctionnel();
        $this->assertNull($entity->getDateModification());
    }

    public function testConstructorInitialisesDateEnregistrement(): void
    {
        $entity = new GroupeFonctionnel();
        $this->assertInstanceOf(\DateTimeImmutable::class, $entity->getDateEnregistrement());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = new GroupeFonctionnel();
        $date = new \DateTimeImmutable('2024-06-28 17:55:45+02:00');
        $entity->setDateEnregistrement($date);
        $this->assertEquals($date, $entity->getDateEnregistrement());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new GroupeFonctionnel());
        $this->assertEquals(5, count($reflectionClass->getProperties()));
    }
}
