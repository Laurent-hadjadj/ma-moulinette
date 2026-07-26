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

    /* MODIF 2026-05-05 : pas de setId expose (ID auto-genere par Doctrine).
     * On verifie juste l'absence du setter et que le getter retourne null avant persist. */
    public function testIdHasNoSetter(): void
    {
        $entity = $this->getEntity();
        $this->assertFalse(method_exists($entity, 'setId'),
            'GroupeFonctionnel::setId() ne devrait pas exister (ID auto-genere).');
        $this->assertNull($entity->getId());
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
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $entity->getDateEnregistrement());
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

    /* MODIF 2026-05-05 : test de regression.
     * La colonne `description` doit avoir length: 128 dans le mapping Doctrine pour rester
     * coherente avec le DDL groupe_fonctionnel.sql:20 (VARCHAR(128) apres alignement). */
    public function testDescriptionMappingIsLength128(): void
    {
        $reflection = new \ReflectionProperty(GroupeFonctionnel::class, 'description');
        $attributes = $reflection->getAttributes(\Doctrine\ORM\Mapping\Column::class);
        $this->assertCount(1, $attributes,
            'GroupeFonctionnel::$description doit avoir un attribut #[ORM\\Column].');

        $args = $attributes[0]->getArguments();
        $this->assertSame(128, $args['length'] ?? null,
            'GroupeFonctionnel::$description doit avoir length: 128 (alignement DDL).');
    }

    /* MODIF 2026-05-05 : test de regression.
     * La colonne `groupe_fonctionnel` doit avoir length: 32 dans le mapping Doctrine
     * pour rester alignee avec le Validator Assert\Length(max: 32) et le DDL VARCHAR(32). */
    public function testGroupeFonctionnelMappingIsLength32(): void
    {
        $reflection = new \ReflectionProperty(GroupeFonctionnel::class, 'groupeFonctionnel');
        $attributes = $reflection->getAttributes(\Doctrine\ORM\Mapping\Column::class);
        $this->assertCount(1, $attributes,
            'GroupeFonctionnel::$groupeFonctionnel doit avoir un attribut #[ORM\\Column].');

        $args = $attributes[0]->getArguments();
        $this->assertSame(32, $args['length'] ?? null,
            'GroupeFonctionnel::$groupeFonctionnel doit avoir length: 32 (alignement DDL+Validator).');
        $this->assertTrue($args['unique'] ?? false,
            'GroupeFonctionnel::$groupeFonctionnel doit avoir unique: true.');
    }

    /* MODIF 2026-05-05 : verifie la coherence interne
     * entre le `length` Doctrine et le `max` du Validator (les deux doivent rester en phase). */
    public function testGroupeFonctionnelDoctrineAndValidatorLengthsMatch(): void
    {
        $reflection = new \ReflectionProperty(GroupeFonctionnel::class, 'groupeFonctionnel');

        $columnAttrs = $reflection->getAttributes(\Doctrine\ORM\Mapping\Column::class);
        $doctrineLength = $columnAttrs[0]->getArguments()['length'] ?? null;

        $validatorAttrs = $reflection->getAttributes(\Symfony\Component\Validator\Constraints\Length::class);
        $this->assertCount(1, $validatorAttrs,
            'GroupeFonctionnel::$groupeFonctionnel doit avoir un Assert\\Length.');
        $validatorMax = $validatorAttrs[0]->getArguments()['max'] ?? null;

        $this->assertSame($doctrineLength, $validatorMax,
            'Doctrine length et Validator max doivent etre identiques (mismatch interne sinon).');
    }
}
