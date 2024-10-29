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

namespace App\Tests\Unit\Entity\Case;

use App\Entity\Actuator;
use App\Entity\ActuatorInfo;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActuatorInfoCaseTest]
 */
class ActuatorInfoCaseTest extends TestCase
{
    private $actuatorInfo;

    private static $actuatorId = 1;
    private static $actuatorInfoDescription = '[SOCLE][ARCHETYPE]';
    private static $actuatorInfoValue = 'socle.archetype';

    private function getEntity(): ActuatorInfo
    {
        // Créé une instance de Actuator
        $actuator = new Actuator();
        $actuator->setId(static::$actuatorId);

        $actuatorInfo = new ActuatorInfo();
        $actuatorInfo->setActuator($actuator)
                    ->setActuatorInfoDescription(static::$actuatorInfoDescription)
                    ->setActuatorInfoValue(static::$actuatorInfoValue);
    return $actuatorInfo;
}

    protected function setUp(): void
    {
        parent::setUp();
        $this->actuatorInfo = $this->getEntity();
    }

    public function testSettingAndGettingActuatorIdSignature(): void
    {
        $actuatorInfo = $this->getEntity();
        $this->assertSame(static::$actuatorId, $actuatorInfo->getActuator()->getId());
    }

    public function testSettingAndGettingActuatorInfoDescription(): void
    {
        $this->actuatorInfo->setActuatorInfoDescription(static::$actuatorInfoDescription);
        $this->assertEquals(static::$actuatorInfoDescription, $this->actuatorInfo->getActuatorInfoDescription());
    }

    public function testSettingAndGettingActuatorInfoValue(): void
    {
        $this->actuatorInfo->setActuatorInfoValue(static::$actuatorInfoValue);
        $this->assertEquals(static::$actuatorInfoValue, $this->actuatorInfo->getActuatorInfoValue());
    }

}
