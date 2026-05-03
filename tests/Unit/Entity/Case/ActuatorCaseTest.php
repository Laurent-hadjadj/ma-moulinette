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

use App\Entity\Actuator;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActuatorCaseTest]
 */
class ActuatorCaseTest extends TestCase
{
    private $actuator;

    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static string $nomApplication = 'Application 04';
    private static string $url = 'http://ma-moulinette.fr/app04';
    private static string $actuatorUser = 'user4';
    private static string $actuatorPassword = 'password4';
    private static string $personne = 'Elsa Davis';
    private static string $dateModification = '2024-06-23 11:59:51.854783+02';

    private function getEntity(): Actuator
    {
        $actuator = new Actuator();
        $actuator->setMavenKey(self::$mavenKey)
                    ->setNomApplication(self::$nomApplication)
                    ->setUrl(self::$url)
                    ->setActuatorUser(self::$actuatorUser)
                    ->setActuatorPassword(self::$actuatorPassword)
                    ->setPersonne(self::$personne)
                    ->setDateModification(new \DateTimeImmutable(self::$dateModification))
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
        $this->actuator->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->actuator->getMavenKey());
    }

    public function testSettingAndGettingNomApplication(): void
    {
        $this->actuator->setNomApplication(self::$nomApplication);
        $this->assertEquals(self::$nomApplication, $this->actuator->getNomApplication());
    }

    public function testSettingAndGettingUrl(): void
    {
        $this->actuator->setUrl(self::$url);
        $this->assertEquals(self::$url, $this->actuator->getUrl());
    }

    public function testSettingAndGettingActuatorUser(): void
    {
        $this->actuator->setActuatorUser(self::$actuatorUser);
        $this->assertEquals(self::$actuatorUser, $this->actuator->getActuatorUser());
    }

    public function testSettingAndGettingActuatorPassword(): void
    {
        $this->actuator->setActuatorPassword(self::$actuatorPassword);
        $this->assertEquals(self::$actuatorPassword, $this->actuator->getActuatorPassword());
    }

    public function testSettingAndGettingPersonne(): void
    {
        $this->actuator->setPersonne(self::$personne);
        $this->assertEquals(self::$personne, $this->actuator->getPersonne());
    }

    public function testSettingAndGettingDateModification(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateModification);
        $this->actuator->setDateModification($newDate);
        $this->assertEquals($newDate, $this->actuator->getDateModification());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=$this->actuator->getDateEnregistrement();
        $this->assertEquals($newDate, $this->actuator->getDateEnregistrement(new \DateTimeImmutable()));
    }

    public function testGetActuatorInfoReturnsCollection(): void
    {
        $this->assertInstanceOf(\Doctrine\Common\Collections\Collection::class, $this->actuator->getActuatorInfo());
        $this->assertCount(0, $this->actuator->getActuatorInfo());
    }

    public function testAddActuatorInfoAttachesToParent(): void
    {
        $info = new \App\Entity\ActuatorInfo();

        $this->actuator->addActuatorInfo($info);

        $this->assertCount(1, $this->actuator->getActuatorInfo());
        $this->assertSame($this->actuator, $info->getActuator());
    }

    public function testAddActuatorInfoIgnoresDuplicates(): void
    {
        $info = new \App\Entity\ActuatorInfo();
        $this->actuator->addActuatorInfo($info);
        $this->actuator->addActuatorInfo($info);

        $this->assertCount(1, $this->actuator->getActuatorInfo());
    }

    public function testRemoveActuatorInfo(): void
    {
        $info = new \App\Entity\ActuatorInfo();
        $this->actuator->addActuatorInfo($info);
        $this->actuator->removeActuatorInfo($info);

        $this->assertCount(0, $this->actuator->getActuatorInfo());
    }

    public function testOnUpdateSetsDateModification(): void
    {
        $actuator = new Actuator();
        $this->assertNull($actuator->getDateModification());

        $actuator->onUpdate();

        $this->assertInstanceOf(\DateTime::class, $actuator->getDateModification());
    }

    public function testGetActuatorPasswordDecryptedReturnsNullWhenPasswordNull(): void
    {
        $actuator = new Actuator();
        $actuator->setActuatorPassword(null);

        $this->assertNull($actuator->getActuatorPasswordDecrypted());
    }

    public function testGetActuatorPasswordDecryptedReturnsPlainWhenNotEncrypted(): void
    {
        $actuator = new Actuator();
        $actuator->setActuatorPassword('plain-password');

        $this->assertSame('plain-password', $actuator->getActuatorPasswordDecrypted());
    }

    public function testEncryptPasswordIsNoopWhenPasswordNull(): void
    {
        $actuator = new Actuator();
        $actuator->setActuatorPassword(null);

        $actuator->encryptPassword();

        $this->assertNull($actuator->getActuatorPassword());
    }

    public function testEncryptPasswordEncryptsAndDecryptsRoundtrip(): void
    {
        $key = 'base64:' . base64_encode(random_bytes(32));
        $_ENV['ACTUATOR_SECRET_KEY'] = $key;

        $actuator = new Actuator();
        $actuator->setActuatorPassword('super-secret');

        $actuator->encryptPassword();

        $encrypted = $actuator->getActuatorPassword();
        $this->assertNotSame('super-secret', $encrypted);

        // Round-trip via getActuatorPasswordDecrypted (which uses passwordEncrypted flag)
        $decrypted = $actuator->getActuatorPasswordDecrypted();
        $this->assertSame('super-secret', $decrypted);
    }
}
