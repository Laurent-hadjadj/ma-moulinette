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

use App\Entity\RepartitionTemp;
use PHPUnit\Framework\TestCase;

/**
 * [Description RepartitionTempCaseTest]
 */
class RepartitionTempCaseTest extends TestCase
{
    private $repartitionTemp;

    private static $setup = 1000000000000;
    private static $component = '/src/Controller/ApiController.php';
    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $type = 'CODE_SMELL';
    private static $severity = 'CRITICAL';

    private function getEntity(): RepartitionTemp
    {
        return (new RepartitionTemp())
        ->setComponent(static::$component)
        ->setType(static::$type)
        ->setSeverity(static::$severity)
        ->setSetup(static::$setup)
        ->setMavenKey(static::$mavenKey);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->repartitionTemp = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->repartitionTemp->setId(1);
        $this->assertEquals(1, $this->repartitionTemp->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->repartitionTemp->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->repartitionTemp->getMavenKey());
    }

    public function testSettingAndGettingComponent(): void
    {
        $this->repartitionTemp->setComponent(static::$component);
        $this->assertEquals(static::$component, $this->repartitionTemp->getComponent());
    }

    public function testSettingAndGettingType(): void
    {
        $this->repartitionTemp->setType(static::$type);
        $this->assertEquals(static::$type, $this->repartitionTemp->getType());
    }

    public function testSettingAndGettingSeverity(): void
    {
        $this->repartitionTemp->setSeverity(static::$severity);
        $this->assertEquals(static::$severity, $this->repartitionTemp->getSeverity());
    }

    public function testSettingAndGettingSetup(): void
    {
        $this->repartitionTemp->setSetup(static::$setup);
        $this->assertEquals(static::$setup, $this->repartitionTemp->getSetup());
    }
}
