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

use App\Entity\Main\Profiles;
use PHPUnit\Framework\TestCase;

/**
 * [Description ProfilesCaseTest]
 */
class ProfilesCaseTest extends TestCase
{
    private $profiles;

    private static $key = 'AXyXMubJRtAGLwAs7Zcv';
    private static $name = 'Ma-Petite-Entreprise v1.0.0 (2024)';
    private static $languageName = 'CSS';
    private static $activeRuleCount = 31;
    private static $rulesUpdateAt = '2024-04-13 12:10:51';
    private static $default = true;
    private static $dateEnregistrement = '2024-04-12 16:23:11';

    private function getEntity(): Profiles
    {
        return (new profiles())
        ->setKey(static::$key)
        ->setName(static::$name)
        ->setLanguageName(static::$languageName)
        ->setActiveRuleCount(static::$activeRuleCount)
        ->setRulesUpdateAt(new \DateTime(static::$rulesUpdateAt))
        ->setdefault(static::$default)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->profiles = $this->getEntity();
    }

    public function testSettingAndGettingKey(): void
    {
        $this->profiles->setKey(static::$key);
        $this->assertEquals(static::$key, $this->profiles->getKey());
    }

    public function testSettingAndGettingName(): void
    {
        $this->profiles->setName(static::$name);
        $this->assertEquals(static::$name, $this->profiles->getName());
    }

    public function testSettingAndGettingLanguageName(): void
    {
        $this->profiles->setLanguageName(static::$languageName);
        $this->assertEquals(static::$languageName, $this->profiles->getLanguageName());
    }

    public function testSettingAndGettingActiveRuleCount(): void
    {
        $this->profiles->setActiveRuleCount(static::$activeRuleCount);
        $this->assertEquals(static::$activeRuleCount, $this->profiles->getActiveRuleCount());
    }

    public function testSettingAndGettingRulesUpdateAt(): void
    {
        $newDate=new \DateTime(static::$rulesUpdateAt);
        $this->profiles->setRulesUpdateAt($newDate);
        $this->assertEquals($newDate, $this->profiles->getRulesUpdateAt());
    }

    public function testSettingAndGettingDefault(): void
    {
        $this->profiles->setDefault(static::$default);
        $this->assertEquals(static::$default, $this->profiles->isDefault());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTime(static::$dateEnregistrement);
        $this->profiles->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->profiles->getDateEnregistrement());
    }
}
