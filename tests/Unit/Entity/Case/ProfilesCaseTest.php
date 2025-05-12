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

use App\Entity\Profiles;
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
    private static $rulesUpdatedAt = '2024-04-13 12:10:51+01';
    private static $referentialDefault = true;
    private static $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): Profiles    {
        return (new profiles())
        ->setKey(static::$key)
        ->setName(static::$name)
        ->setLanguageName(static::$languageName)
        ->setActiveRuleCount(static::$activeRuleCount)
        ->setRulesUpdatedAt(new \DateTimeImmutable(static::$rulesUpdatedAt))
        ->setReferentialDefault(static::$referentialDefault)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->profiles = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->profiles->setId(1);
        $this->assertEquals(1, $this->profiles->getId());
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
        $newDate=new \DateTimeImmutable(static::$rulesUpdatedAt);
        $this->profiles->setRulesUpdatedAt($newDate);
        $this->assertEquals($newDate, $this->profiles->getRulesUpdatedAt());
    }

    public function testSettingAndGettingReferentialDefault(): void
    {
        $this->profiles->setReferentialDefault(static::$referentialDefault);
        $this->assertEquals(static::$referentialDefault, $this->profiles->isReferentialDefault());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->profiles->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->profiles->getDateEnregistrement());
    }

}
