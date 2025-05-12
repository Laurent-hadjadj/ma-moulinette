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

use App\Entity\Hotspots;
use PHPUnit\Framework\TestCase;

/**
 * [Description HotspotsCaseTest]
 */
class HotspotsCaseTest extends TestCase
{
    private $hotspots;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $version = '1.2.0-RELEASE';
    private static $dateVersion = '2024-07-10 15:26:07+02';
    private static $hotspotKey = 'AZCc06XbgfifxdiJPzw6';
    private static $securityCategory = 'dos';
    private static $ruleKey = 'typescript:S5852';
    private static $probability = 'MEDIUM';
    private static $status = 'TO_REVIEW';
    private static $resolution = 'Todo';
    private static $niveau = 2;
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): Hotspots
    {
        return (new hotspots())
            ->setMavenKey(static::$mavenKey)
            ->setVersion(static::$version)
            ->setDateVersion(new \DateTimeImmutable(static::$dateVersion))
            ->setHotspotKey(static::$hotspotKey)
            ->setSecurityCategory(static::$securityCategory)
            ->setRuleKey(static::$ruleKey)
            ->setProbability(static::$probability)
            ->setStatus(static::$status)
            ->setResolution(static::$resolution)
            ->setNiveau(static::$niveau)
            ->setModeCollecte(static::$modeCollecte)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->hotspots = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->hotspots->setId(1);
        $this->assertEquals(1, $this->hotspots->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->hotspots->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->hotspots->getMavenKey());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->hotspots->setVersion(static::$version);
        $this->assertEquals(static::$version, $this->hotspots->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $newDate = new \DateTimeImmutable(static::$dateVersion);
        $this->hotspots->setDateVersion($newDate);
        $this->assertEquals($newDate, $this->hotspots->getDateVersion());
    }

    public function testSettingAndGettingHotspotKey(): void
    {
        $this->hotspots->setHotspotKey(static::$hotspotKey);
        $this->assertEquals(static::$hotspotKey, $this->hotspots->getHotspotKey());
    }

    public function testSettingAndGettingSecurityCategory(): void
    {
        $this->hotspots->setSecurityCategory(static::$securityCategory);
        $this->assertEquals(static::$securityCategory, $this->hotspots->getSecurityCategory());
    }

    public function testSettingAndGettingRuleKey(): void
    {
        $this->hotspots->setRuleKey(static::$ruleKey);
        $this->assertEquals(static::$ruleKey, $this->hotspots->getRuleKey());
    }

    public function testSettingAndGettingProbability(): void
    {
        $this->hotspots->setProbability(static::$probability);
        $this->assertEquals(static::$probability, $this->hotspots->getProbability());
    }

    public function testSettingAndGettingStatus(): void
    {
        $this->hotspots->setStatus(static::$status);
        $this->assertEquals(static::$status, $this->hotspots->getStatus());
    }

    public function testSettingAndGettingResolution(): void
    {
        $this->hotspots->setResolution(static::$resolution);
        $this->assertEquals(static::$resolution, $this->hotspots->getResolution());
    }

    public function testSettingAndGettingNiveau(): void
    {
        $this->hotspots->setNiveau(static::$niveau);
        $this->assertEquals(static::$niveau, $this->hotspots->getNiveau());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->hotspots->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->hotspots->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->hotspots->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->hotspots->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->hotspots->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->hotspots->getDateEnregistrement());
    }

}
