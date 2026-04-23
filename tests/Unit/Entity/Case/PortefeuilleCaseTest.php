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

use App\Entity\Portefeuille;
use PHPUnit\Framework\TestCase;

/**
 * [Description PortefeuilleCaseTest]
 */
class PortefeuilleCaseTest extends TestCase
{
    private $portefeuille;

    private static $portefeuilles = 'MES PROJETS';
    private static $groupeFonctionnel = 'MA PETITE ENTREPRISE';
    private static $liste =  ['fr.ma-petite-entreprise:ma-moulinette'];
    private static $dateModification = '2024-03-26 14:46:38+01';
    private static $dateEnregistrement = '2024-03-25 12:26:58+01';

    private function getEntity(): Portefeuille
    {
        return (new portefeuille())
        ->setPortefeuille(static::$portefeuilles)
        ->setGroupeFonctionnel(static::$groupeFonctionnel)
        ->setListe(static::$liste)
        ->setDateModification(new \DateTime(static::$dateModification))
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
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
        $this->portefeuille->setPortefeuille(static::$portefeuilles);
        $this->assertEquals(static::$portefeuilles, $this->portefeuille->getPortefeuille());
    }

    public function testSettingAndGettingGroupeFonctionnel(): void
    {
        $this->portefeuille->setGroupeFonctionnel(static::$groupeFonctionnel);
        $this->assertEquals(static::$groupeFonctionnel, $this->portefeuille->getGroupeFonctionnel());
    }

    public function testSettingAndGettingListe(): void
    {
        $this->portefeuille->setListe(static::$liste);
        $this->assertEquals(static::$liste, $this->portefeuille->getListe());
    }

    public function testSettingAndGettingDateModification(): void
    {
        $newDate=new \DateTime(static::$dateModification);
        $this->portefeuille->setDateModification($newDate);
        $this->assertEquals($newDate, $this->portefeuille->getDateModification());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->portefeuille->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->portefeuille->getDateEnregistrement());
    }

}
