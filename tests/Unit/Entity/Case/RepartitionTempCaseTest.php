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
namespace App\Tests\Unit\Entity\Case;

use App\Entity\RepartitionTemp;
use PHPUnit\Framework\TestCase;

/**
 * [Description RepartitionTempCaseTest]
 */
class RepartitionTempCaseTest extends TestCase
{
    private RepartitionTemp $repartitionTemp;

    private static int $setup = 1000000000000;
    private static string $component = '/src/Controller/ApiController.php';
    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static string $type = 'CODE_SMELL';
    private static string $severity = 'CRITICAL';

    private function getEntity(): RepartitionTemp
    {
        return (new RepartitionTemp())
        ->setComponent(self::$component)
        ->setType(self::$type)
        ->setSeverity(self::$severity)
        ->setSetup(self::$setup)
        ->setMavenKey(self::$mavenKey);
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
        $this->repartitionTemp->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->repartitionTemp->getMavenKey());
    }

    public function testSettingAndGettingComponent(): void
    {
        $this->repartitionTemp->setComponent(self::$component);
        $this->assertEquals(self::$component, $this->repartitionTemp->getComponent());
    }

    public function testSettingAndGettingType(): void
    {
        $this->repartitionTemp->setType(self::$type);
        $this->assertEquals(self::$type, $this->repartitionTemp->getType());
    }

    public function testSettingAndGettingSeverity(): void
    {
        $this->repartitionTemp->setSeverity(self::$severity);
        $this->assertEquals(self::$severity, $this->repartitionTemp->getSeverity());
    }

    public function testSettingAndGettingSetup(): void
    {
        $this->repartitionTemp->setSetup(self::$setup);
        $this->assertEquals(self::$setup, $this->repartitionTemp->getSetup());
    }
}
