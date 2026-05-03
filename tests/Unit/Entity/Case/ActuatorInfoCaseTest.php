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
use App\Entity\ActuatorInfo;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActuatorInfoCaseTest]
 */
class ActuatorInfoCaseTest extends TestCase
{
    private $actuatorInfo;

    private static int $actuatorId = 1;
    private static string $actuatorInfoDescription = '[SOCLE][ARCHETYPE]';
    private static string $actuatorInfoValue = 'socle.archetype';

    private function getEntity(): ActuatorInfo
    {
        // Créé une instance de Actuator
        $actuator = new Actuator();
        $actuator->setId(self::$actuatorId);

        $actuatorInfo = new ActuatorInfo();
        $actuatorInfo->setActuator($actuator)
                    ->setActuatorInfoDescription(self::$actuatorInfoDescription)
                    ->setActuatorInfoValue(self::$actuatorInfoValue);
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
        $this->assertSame(self::$actuatorId, $actuatorInfo->getActuator()->getId());
    }

    public function testSettingAndGettingActuatorInfoDescription(): void
    {
        $this->actuatorInfo->setActuatorInfoDescription(self::$actuatorInfoDescription);
        $this->assertEquals(self::$actuatorInfoDescription, $this->actuatorInfo->getActuatorInfoDescription());
    }

    public function testSettingAndGettingActuatorInfoValue(): void
    {
        $this->actuatorInfo->setActuatorInfoValue(self::$actuatorInfoValue);
        $this->assertEquals(self::$actuatorInfoValue, $this->actuatorInfo->getActuatorInfoValue());
    }

}
