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

use App\Entity\Portefeuille;
use PHPUnit\Framework\TestCase;

/**
 * [Description PortefeuilleCaseTest]
 */
class PortefeuilleCaseTest extends TestCase
{
    private Portefeuille $portefeuille;

    private static string $portefeuilles = 'MES PROJETS';
    private static string $groupeFonctionnel = 'MA PETITE ENTREPRISE';
    private static $liste =  ['fr.ma-petite-entreprise:ma-moulinette'];
    private static string $dateModification = '2024-03-26 14:46:38+01';
    private static string $dateEnregistrement = '2024-03-25 12:26:58+01';

    private function getEntity(): Portefeuille
    {
        return (new portefeuille())
        ->setPortefeuille(self::$portefeuilles)
        ->setGroupeFonctionnel(self::$groupeFonctionnel)
        ->setListe(self::$liste)
        ->setDateModification(new \DateTime(self::$dateModification))
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->portefeuille = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->portefeuille->setId(1);
        $this->assertEquals(1, $this->portefeuille->getId());
    }

    public function testSettingAndGettingPortefeuille(): void
    {
        $this->portefeuille->setPortefeuille(self::$portefeuilles);
        $this->assertEquals(self::$portefeuilles, $this->portefeuille->getPortefeuille());
    }

    public function testSettingAndGettingGroupeFonctionnel(): void
    {
        $this->portefeuille->setGroupeFonctionnel(self::$groupeFonctionnel);
        $this->assertEquals(self::$groupeFonctionnel, $this->portefeuille->getGroupeFonctionnel());
    }

    public function testSettingAndGettingListe(): void
    {
        $this->portefeuille->setListe(self::$liste);
        $this->assertEquals(self::$liste, $this->portefeuille->getListe());
    }

    public function testSettingAndGettingDateModification(): void
    {
        $newDate=new \DateTime(self::$dateModification);
        $this->portefeuille->setDateModification($newDate);
        $this->assertEquals($newDate, $this->portefeuille->getDateModification());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->portefeuille->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->portefeuille->getDateEnregistrement());
    }

    /* MODIF 2026-05-05 : test de regression.
     * La propriété `liste` doit être typée `array` non-nullable et avoir un default `[]`
     * (DDL portefeuille.sql:22 = `liste json NOT NULL`). */
    public function testListeIsNonNullableArray(): void
    {
        $reflection = new \ReflectionProperty(Portefeuille::class, 'liste');
        $type = $reflection->getType();

        $this->assertInstanceOf(\ReflectionNamedType::class, $type);
        $this->assertSame('array', $type->getName());
        $this->assertFalse($type->allowsNull(),
            'Portefeuille::$liste doit etre typee non-nullable (alignement DDL NOT NULL).');

        $entity = new Portefeuille();
        $this->assertSame([], $entity->getListe(),
            'Portefeuille::$liste doit avoir un default array vide.');
    }

}
