<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Case;

use App\Entity\Actuator;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActuatorCaseTest]
 */
class ActuatorCaseTest extends TestCase
{
    private $actuator;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $nomApplication = 'Application 04';
    private static $url = 'http://ma-moulinette.fr/app04';
    private static $actuatorUser = 'user4';
    private static $actuatorPassword = 'password4';
    private static $personne = 'Elsa Davis';
    private static $dateModification = '2024-06-23 11:59:51.854783+02';

    private function getEntity(): Actuator
    {
        $actuator = new Actuator();
        $actuator->setMavenKey(static::$mavenKey)
                    ->setNomApplication(static::$nomApplication)
                    ->setUrl(static::$url)
                    ->setActuatorUser(static::$actuatorUser)
                    ->setActuatorPassword(static::$actuatorPassword)
                    ->setPersonne(static::$personne)
                    ->setDateModification(new \DateTimeImmutable(static::$dateModification))
                    ->setDateEnregistrement(new \DateTimeImmutable());
        return $actuator;
}

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuator = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->actuator->setId(1);
        $this->assertEquals(1, $this->actuator->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->actuator->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->actuator->getMavenKey());
    }

    public function testSettingAndGettingNomApplication(): void
    {
        $this->actuator->setNomApplication(static::$nomApplication);
        $this->assertEquals(static::$nomApplication, $this->actuator->getNomApplication());
    }

    public function testSettingAndGettingUrl(): void
    {
        $this->actuator->setUrl(static::$url);
        $this->assertEquals(static::$url, $this->actuator->getUrl());
    }

    public function testSettingAndGettingActuatorUser(): void
    {
        $this->actuator->setActuatorUser(static::$actuatorUser);
        $this->assertEquals(static::$actuatorUser, $this->actuator->getActuatorUser());
    }

    public function testSettingAndGettingActuatorPassword(): void
    {
        $this->actuator->setActuatorPassword(static::$actuatorPassword);
        $this->assertEquals(static::$actuatorPassword, $this->actuator->getActuatorPassword());
    }

    public function testSettingAndGettingPersonne(): void
    {
        $this->actuator->setPersonne(static::$personne);
        $this->assertEquals(static::$personne, $this->actuator->getPersonne());
    }

    public function testSettingAndGettingDateModification(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateModification);
        $this->actuator->setDateModification($newDate);
        $this->assertEquals($newDate, $this->actuator->getDateModification());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=$this->actuator->getDateEnregistrement();
        $this->assertEquals($newDate, $this->actuator->getDateEnregistrement(new \DateTimeImmutable()));
    }

}
