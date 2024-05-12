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

use App\Entity\Main\Properties;
use PHPUnit\Framework\TestCase;

/**
 * [Description PropertiesCaseTest]
 */
class PropertiesCaseTest extends TestCase
{
    private $properties;

    private static $type = 'properties';
    private static $projetBd = 100;
    private static $projetSonar = 12;
    private static $profilBd = 12;
    private static $profilSonar = 18;
    private static $dateCreation = '2024-03-26 14:46:38';
    private static $dateModificationProjet = '2024-03-27 10:26:31';
    private static $dateModificationProfil = '2024-04-12 16:23:11';

    private function getEntity(): Properties
    {
        return (new properties())
        ->setType(static::$type)
        ->setProjetBd(static::$projetBd)
        ->setProjetSonar(static::$projetSonar)
        ->setProfilBd(static::$profilBd)
        ->setProfilSonar(static::$profilSonar)
        ->setDateCreation(new \DateTime(static::$dateCreation))
        ->setDateModificationProjet(new \DateTime(static::$dateModificationProjet))
        ->setDateModificationProfil(new \DateTime(static::$dateModificationProfil));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->properties = $this->getEntity();
    }

    public function testSettingAndGettingType(): void
    {
        $this->properties->setType(static::$type);
        $this->assertEquals(static::$type, $this->properties->getType());
    }

    public function testSettingAndGettingProjetBd(): void
    {
        $this->properties->setProjetBd(static::$projetBd);
        $this->assertEquals(static::$projetBd, $this->properties->getProjetBd());
    }

    public function testSettingAndGettingProjetSonar(): void
    {
        $this->properties->setProjetSonar(static::$projetSonar);
        $this->assertEquals(static::$projetSonar, $this->properties->getProjetSonar());
    }

    public function testSettingAndGettingProfilBd(): void
    {
        $this->properties->setProfilBd(static::$profilBd);
        $this->assertEquals(static::$profilBd, $this->properties->getProfilBd());
    }

    public function testSettingAndGettingProfilSonar(): void
    {
        $this->properties->setProfilSonar(static::$profilSonar);
        $this->assertEquals(static::$profilSonar, $this->properties->getProfilSonar());
    }
    public function testSettingAndGettingDateCreation(): void
    {
        $newDate=new \DateTime(static::$dateCreation);
        $this->properties->setDateCreation($newDate);
        $this->assertEquals($newDate, $this->properties->getDateCreation());
    }

    public function testSettingAndGettingDateModificationProjet(): void
    {
        $newDate=new \DateTime(static::$dateModificationProjet);
        $this->properties->setDatemodificationProjet($newDate);
        $this->assertEquals($newDate, $this->properties->getDatemodificationProjet());
    }

    public function testSettingAndGettingDateModificationProfil(): void
    {
        $newDate=new \DateTime(static::$dateModificationProfil);
        $this->properties->setDateModificationProfil($newDate);
        $this->assertEquals($newDate, $this->properties->getDateModificationProfil());
    }
}
