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

/* MODIF 2026-06-08 : couverture complète ActuatorInfo (8 méthodes). */
#[AllowMockObjectsWithoutExpectations]
class ActuatorInfoTest extends TestCase
{
    public function testSetGetId(): void
    {
        $a = new ActuatorInfo();
        $result = $a->setId(42);
        $this->assertSame($a, $result);
        $this->assertSame(42, $a->getId());
    }

    public function testSetGetActuatorInfoDescription(): void
    {
        $a = new ActuatorInfo();
        $result = $a->setActuatorInfoDescription('Version Java');
        $this->assertInstanceOf(ActuatorInfo::class, $result);
        $this->assertSame('Version Java', $a->getActuatorInfoDescription());
    }

    public function testSetGetActuatorInfoDescriptionNull(): void
    {
        $a = new ActuatorInfo();
        $a->setActuatorInfoDescription(null);
        $this->assertNull($a->getActuatorInfoDescription());
    }

    public function testSetGetActuatorInfoValue(): void
    {
        $a = new ActuatorInfo();
        $result = $a->setActuatorInfoValue('17.0.12');
        $this->assertInstanceOf(ActuatorInfo::class, $result);
        $this->assertSame('17.0.12', $a->getActuatorInfoValue());
    }

    public function testSetGetActuatorInfoValueNull(): void
    {
        $a = new ActuatorInfo();
        $a->setActuatorInfoValue(null);
        $this->assertNull($a->getActuatorInfoValue());
    }

    public function testSetGetActuator(): void
    {
        $actuator = $this->createMock(Actuator::class);
        $a = new ActuatorInfo();
        $result = $a->setActuator($actuator);
        $this->assertSame($a, $result);
        $this->assertSame($actuator, $a->getActuator());
    }

    public function testSetGetActuatorNull(): void
    {
        $a = new ActuatorInfo();
        $a->setActuator(null);
        $this->assertNull($a->getActuator());
    }
}
