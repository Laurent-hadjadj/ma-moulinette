<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity;

use App\Entity\Portefeuille;
use PHPUnit\Framework\TestCase;

/**
 * [Description PortefeuilleCaseTest]
 */
class PortefeuilleCaseTest extends TestCase
{
    private $portefeuille;

    private static $titre = 'MES PROJETS';
    private static $equipe = 'MA PETITE ENTREPRISE - équipe de développement.';
    private static $liste =  ['fr.ma-petite-entreprise:ma-moulinette'];
    private static $dateModification = '2024-03-26 14:46:38';
    private static $dateEnregistrement = '2024-03-25 12:26:58';

    private function getEntity(): Portefeuille
    {
        return (new portefeuille())
        ->setTitre(static::$titre)
        ->setEquipe(static::$equipe)
        ->setListe(static::$liste)
        ->setDateModification(new \DateTime(static::$dateModification))
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->portefeuille = $this->getEntity();
    }

    public function testSettingAndGettingTitre(): void
    {
        $this->portefeuille->setTitre(static::$titre);
        $this->assertEquals(static::$titre, $this->portefeuille->getTitre());
    }

    public function testSettingAndGettingEquipe(): void
    {
        $this->portefeuille->setEquipe(static::$equipe);
        $this->assertEquals(static::$equipe, $this->portefeuille->getEquipe());
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
        $newDate=new \DateTime(static::$dateEnregistrement);
        $this->portefeuille->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->portefeuille->getDateEnregistrement());
    }

}
