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

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\{Actuator, ActuatorInfo};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-06-08 : couverture complète Actuator (23 méthodes). */
#[AllowMockObjectsWithoutExpectations]
class ActuatorTest extends TestCase
{
    public function testConstructorInitializesCollection(): void
    {
        $a = new Actuator();
        $this->assertInstanceOf(\DateTimeImmutable::class, $a->getDateEnregistrement());
        $this->assertCount(0, $a->getActuatorInfo());
    }

    public function testSetGetId(): void
    {
        $a = new Actuator();
        $result = $a->setId(7);
        $this->assertSame($a, $result);
        $this->assertSame(7, $a->getId());
    }

    public function testSetGetMavenKey(): void
    {
        $a = new Actuator();
        $result = $a->setMavenKey('fr.ma-moulinette:ma-moulinette');
        $this->assertInstanceOf(Actuator::class, $result);
        $this->assertSame('fr.ma-moulinette:ma-moulinette', $a->getMavenKey());
    }

    public function testSetGetNomApplication(): void
    {
        $a = new Actuator();
        $result = $a->setNomApplication('Mon Application');
        $this->assertInstanceOf(Actuator::class, $result);
        $this->assertSame('Mon Application', $a->getNomApplication());
    }

    public function testSetGetUrl(): void
    {
        $a = new Actuator();
        $result = $a->setUrl('http://localhost:8080');
        $this->assertInstanceOf(Actuator::class, $result);
        $this->assertSame('http://localhost:8080', $a->getUrl());
    }

    public function testSetGetActuatorUser(): void
    {
        $a = new Actuator();
        $result = $a->setActuatorUser('admin');
        $this->assertInstanceOf(Actuator::class, $result);
        $this->assertSame('admin', $a->getActuatorUser());
    }

    public function testSetGetActuatorUserNull(): void
    {
        $a = new Actuator();
        $a->setActuatorUser(null);
        $this->assertNull($a->getActuatorUser());
    }

    public function testSetGetActuatorPassword(): void
    {
        $a = new Actuator();
        $result = $a->setActuatorPassword('secret');
        $this->assertInstanceOf(Actuator::class, $result);
        $this->assertSame('secret', $a->getActuatorPassword());
    }

    public function testSetGetActuatorPasswordNull(): void
    {
        $a = new Actuator();
        $a->setActuatorPassword(null);
        $this->assertNull($a->getActuatorPassword());
    }

    public function testSetGetPersonne(): void
    {
        $a = new Actuator();
        $result = $a->setPersonne('Jean Dupont');
        $this->assertInstanceOf(Actuator::class, $result);
        $this->assertSame('Jean Dupont', $a->getPersonne());
    }

    public function testSetGetDateModification(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01');
        $a = new Actuator();
        $result = $a->setDateModification($dt);
        $this->assertInstanceOf(Actuator::class, $result);
        $this->assertSame($dt, $a->getDateModification());
    }

    public function testSetGetDateModificationNull(): void
    {
        $a = new Actuator();
        $a->setDateModification(null);
        $this->assertNull($a->getDateModification());
    }

    public function testPreUpdateSetsDateModification(): void
    {
        $a = new Actuator();
        $a->preUpdate();
        $this->assertInstanceOf(\DateTimeImmutable::class, $a->getDateModification());
    }

    public function testSetGetDateEnregistrement(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $a = new Actuator();
        $a->setDateEnregistrement($dt);
        $this->assertSame($dt, $a->getDateEnregistrement());
    }

    public function testAddRemoveActuatorInfo(): void
    {
        $a = new Actuator();
        $info = $this->createMock(ActuatorInfo::class);
        $info->method('getActuator')->willReturn($a);

        $result = $a->addActuatorInfo($info);
        $this->assertSame($a, $result);
        $this->assertCount(1, $a->getActuatorInfo());

        $a->addActuatorInfo($info);
        $this->assertCount(1, $a->getActuatorInfo());
    }

    public function testRemoveActuatorInfoSetsActuatorNullIfOwner(): void
    {
        $a = new Actuator();
        $info = new ActuatorInfo();
        $info->setActuator($a);

        $a->addActuatorInfo($info);
        $this->assertCount(1, $a->getActuatorInfo());

        $a->removeActuatorInfo($info);
        $this->assertCount(0, $a->getActuatorInfo());
        $this->assertNull($info->getActuator());
    }
}
