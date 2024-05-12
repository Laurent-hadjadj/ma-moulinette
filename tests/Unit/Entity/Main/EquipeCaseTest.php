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

namespace App\Tests\Unit\Entity\Main;

use App\Entity\Main\Equipe;
use PHPUnit\Framework\TestCase;

/**
 * [Description EquipeCaseTest]
 */
class EquipeCaseTest extends TestCase
{
    private $equipe;

    private static $titre = 'MA PETITE ENTREPRISE';
    private static $description = "Equipe de Développement de l'application Ma-Moulinette";
    private static $dateModification = '2024-03-26 14:46:38';
    private static $dateEnregistrement = '2024-03-25 12:26:58';

    private function getEntity(): Equipe
    {
        return (new equipe())
        ->setTitre(static::$titre)
        ->setDescription(static::$description)
        ->setDateModification(new \DateTime(static::$dateModification))
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->equipe = $this->getEntity();
    }

    public function testSettingAndGettingTitre(): void
    {
        $this->equipe->setTitre(static::$titre);
        $this->assertEquals(static::$titre, $this->equipe->getTitre());
    }

    public function testSettingAndGettingDescription(): void
    {
        $this->equipe->setDescription(static::$description);
        $this->assertEquals(static::$description, $this->equipe->getDescription());
    }

    public function testSettingAndGettingDateModification(): void
    {
        $newDate=new \DateTime(static::$dateModification);
        $this->equipe->setDateModification($newDate);
        $this->assertEquals($newDate, $this->equipe->getDateModification());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTime(static::$dateEnregistrement);
        $this->equipe->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->equipe->getDateEnregistrement());
    }

}
