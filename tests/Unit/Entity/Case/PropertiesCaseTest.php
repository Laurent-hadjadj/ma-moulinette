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

use App\Entity\Properties;
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
    private static $dateCreation = '2024-03-26 14:46:38+01';
    private static $dateModificationProjet = '2024-03-27 10:26:31+01';
    private static $dateModificationProfil = '2024-04-12 16:23:11+01';

    private function getEntity(): Properties
    {
        return (new properties())
        ->setType(self::$type)
        ->setProjetBd(self::$projetBd)
        ->setProjetSonar(self::$projetSonar)
        ->setProfilBd(self::$profilBd)
        ->setProfilSonar(self::$profilSonar)
        ->setDateCreation(new \DateTimeImmutable(self::$dateCreation))
        ->setDateModificationProjet(new \DateTime(self::$dateModificationProjet))
        ->setDateModificationProfil(new \DateTime(self::$dateModificationProfil));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->properties = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->properties->setId(1);
        $this->assertEquals(1, $this->properties->getId());
    }

    public function testSettingAndGettingType(): void
    {
        $this->properties->setType(self::$type);
        $this->assertEquals(self::$type, $this->properties->getType());
    }

    public function testSettingAndGettingProjetBd(): void
    {
        $this->properties->setProjetBd(self::$projetBd);
        $this->assertEquals(self::$projetBd, $this->properties->getProjetBd());
    }

    public function testSettingAndGettingProjetSonar(): void
    {
        $this->properties->setProjetSonar(self::$projetSonar);
        $this->assertEquals(self::$projetSonar, $this->properties->getProjetSonar());
    }

    public function testSettingAndGettingProfilBd(): void
    {
        $this->properties->setProfilBd(self::$profilBd);
        $this->assertEquals(self::$profilBd, $this->properties->getProfilBd());
    }

    public function testSettingAndGettingProfilSonar(): void
    {
        $this->properties->setProfilSonar(self::$profilSonar);
        $this->assertEquals(self::$profilSonar, $this->properties->getProfilSonar());
    }
    public function testSettingAndGettingDateCreation(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateCreation);
        $this->properties->setDateCreation($newDate);
        $this->assertEquals($newDate, $this->properties->getDateCreation());
    }

    public function testSettingAndGettingDateModificationProjet(): void
    {
        $newDate=new \DateTime(self::$dateModificationProjet);
        $this->properties->setDatemodificationProjet($newDate);
        $this->assertEquals($newDate, $this->properties->getDatemodificationProjet());
    }

    public function testSettingAndGettingDateModificationProfil(): void
    {
        $newDate=new \DateTime(self::$dateModificationProfil);
        $this->properties->setDateModificationProfil($newDate);
        $this->assertEquals($newDate, $this->properties->getDateModificationProfil());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new \App\Entity\Properties());
        $this->assertEquals(9, count($reflectionClass->getProperties()));
    }
}
