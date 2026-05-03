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

use App\Entity\Profiles;
use PHPUnit\Framework\TestCase;

/**
 * [Description ProfilesCaseTest]
 */
class ProfilesCaseTest extends TestCase
{
    private $profiles;

    private static string $key = 'AXyXMubJRtAGLwAs7Zcv';
    private static string $name = 'Ma-Petite-Entreprise v1.0.0 (2024)';
    private static string $languageName = 'CSS';
    private static int $activeRuleCount = 31;
    private static string $rulesUpdatedAt = '2024-04-13 12:10:51+01';
    private static bool $referentialDefault = true;
    private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): Profiles    {
        return (new profiles())
        ->setKey(self::$key)
        ->setName(self::$name)
        ->setLanguageName(self::$languageName)
        ->setActiveRuleCount(self::$activeRuleCount)
        ->setRulesUpdatedAt(new \DateTimeImmutable(self::$rulesUpdatedAt))
        ->setReferentialDefault(self::$referentialDefault)
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
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
        $this->profiles->setKey(self::$key);
        $this->assertEquals(self::$key, $this->profiles->getKey());
    }

    public function testSettingAndGettingName(): void
    {
        $this->profiles->setName(self::$name);
        $this->assertEquals(self::$name, $this->profiles->getName());
    }
    public function testSettingAndGettingLanguageName(): void
    {
        $this->profiles->setLanguageName(self::$languageName);
        $this->assertEquals(self::$languageName, $this->profiles->getLanguageName());
    }
    public function testSettingAndGettingActiveRuleCount(): void
    {
        $this->profiles->setActiveRuleCount(self::$activeRuleCount);
        $this->assertEquals(self::$activeRuleCount, $this->profiles->getActiveRuleCount());
    }

    public function testSettingAndGettingRulesUpdateAt(): void
    {
        $newDate=new \DateTimeImmutable(self::$rulesUpdatedAt);
        $this->profiles->setRulesUpdatedAt($newDate);
        $this->assertEquals($newDate, $this->profiles->getRulesUpdatedAt());
    }

    public function testSettingAndGettingReferentialDefault(): void
    {
        $this->profiles->setReferentialDefault(self::$referentialDefault);
        $this->assertEquals(self::$referentialDefault, $this->profiles->isReferentialDefault());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->profiles->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->profiles->getDateEnregistrement());
    }

}
