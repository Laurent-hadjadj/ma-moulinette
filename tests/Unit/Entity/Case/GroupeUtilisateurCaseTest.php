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

use App\Entity\GroupeUtilisateur;
use PHPUnit\Framework\TestCase;

/**
 * [Description GroupeUtilisateurCaseTest]
 */
class GroupeUtilisateurCaseTest extends TestCase
{
    private function getEntity(): GroupeUtilisateur
    {
        return (new GroupeUtilisateur())
            ->setGroupeUtilisateur('admin')
            ->setDescription('Groupe administrateur')
            ->setDateModification(new \DateTime('2024-04-12 10:00:00'))
            ->setDateEnregistrement(new \DateTimeImmutable('2024-01-01 09:00:00+01:00'));
    }

    public function testConstructorGeneratesGroupeId(): void
    {
        $entity = new GroupeUtilisateur();
        $this->assertNotEmpty($entity->getGroupeId(), 'groupeId doit etre auto-genere via Ulid');
        $this->assertSame(26, strlen($entity->getGroupeId()));
    }

    public function testSettingAndGettingId(): void
    {
        $entity = $this->getEntity();
        $entity->setId(42);
        $this->assertSame(42, $entity->getId());
    }

    public function testSettingAndGettingGroupeUtilisateur(): void
    {
        $entity = $this->getEntity();
        $entity->setGroupeUtilisateur('utilisateur');
        $this->assertSame('utilisateur', $entity->getGroupeUtilisateur());
    }

    public function testSettingAndGettingGroupeId(): void
    {
        $entity = $this->getEntity();
        $entity->setGroupeId('01HK7XMKQGM3F5XZJ4S6T7VWE2');
        $this->assertSame('01HK7XMKQGM3F5XZJ4S6T7VWE2', $entity->getGroupeId());
    }

    public function testSettingAndGettingDescription(): void
    {
        $entity = $this->getEntity();
        $entity->setDescription('Une autre description');
        $this->assertSame('Une autre description', $entity->getDescription());
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
        $entity = $this->getEntity()->setDateModification(null);
        $this->assertNull($entity->getDateModification());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = $this->getEntity();
        $date = new \DateTimeImmutable('2024-06-28 17:55:45+02:00');
        $entity->setDateEnregistrement($date);
        $this->assertEquals($date, $entity->getDateEnregistrement());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new GroupeUtilisateur());
        $this->assertEquals(6, count($reflectionClass->getProperties()));
    }

    /* MODIF 2026-05-05 : tests de regression.
     * Les colonnes groupe_utilisateur et groupe_id portent `unique: true` cote Doctrine
     * et doivent rester alignees avec la contrainte UNIQUE ajoutee dans constraints.sql. */
    public function testGroupeUtilisateurMappingIsUnique(): void
    {
        $reflection = new \ReflectionProperty(GroupeUtilisateur::class, 'groupeUtilisateur');
        $attributes = $reflection->getAttributes(\Doctrine\ORM\Mapping\Column::class);
        $this->assertCount(1, $attributes);

        $args = $attributes[0]->getArguments();
        $this->assertTrue($args['unique'] ?? false,
            'GroupeUtilisateur::$groupeUtilisateur doit avoir unique: true (alignement DDL).');
    }

    public function testGroupeIdMappingIsUnique(): void
    {
        $reflection = new \ReflectionProperty(GroupeUtilisateur::class, 'groupeId');
        $attributes = $reflection->getAttributes(\Doctrine\ORM\Mapping\Column::class);
        $this->assertCount(1, $attributes);

        $args = $attributes[0]->getArguments();
        $this->assertTrue($args['unique'] ?? false,
            'GroupeUtilisateur::$groupeId doit avoir unique: true (alignement DDL).');
    }
}
