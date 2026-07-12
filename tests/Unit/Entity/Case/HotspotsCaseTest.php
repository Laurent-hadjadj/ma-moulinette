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

use App\Entity\Hotspots;
use PHPUnit\Framework\TestCase;

/**
 * [Description HotspotsCaseTest]
 */
class HotspotsCaseTest extends TestCase
{
    private Hotspots $hotspots;

    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $version = '1.2.0-RELEASE';
    private static string $dateVersion = '2024-07-10 15:26:07+02';
    private static string $hotspotKey = 'AZCc06XbgfifxdiJPzw6';
    private static string $securityCategory = 'dos';
    private static string $ruleKey = 'typescript:S5852';
    private static string $probability = 'MEDIUM';
    private static string $status = 'TO_REVIEW';
    private static string $resolution = 'Todo';
    private static int $niveau = 2;
    private static string $modeCollecte = 'TRAITEMENT MANUEL';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
    private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): Hotspots
    {
        return (new hotspots())
            ->setMavenKey(self::$mavenKey)
            ->setVersion(self::$version)
            ->setDateVersion(new \DateTimeImmutable(self::$dateVersion))
            ->setHotspotKey(self::$hotspotKey)
            ->setSecurityCategory(self::$securityCategory)
            ->setRuleKey(self::$ruleKey)
            ->setProbability(self::$probability)
            ->setStatus(self::$status)
            ->setResolution(self::$resolution)
            ->setNiveau(self::$niveau)
            ->setModeCollecte(self::$modeCollecte)
            ->setUtilisateurCollecte(self::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
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
        $this->hotspots->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->hotspots->getMavenKey());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->hotspots->setVersion(self::$version);
        $this->assertEquals(self::$version, $this->hotspots->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $newDate = new \DateTimeImmutable(self::$dateVersion);
        $this->hotspots->setDateVersion($newDate);
        $this->assertEquals($newDate, $this->hotspots->getDateVersion());
    }

    public function testSettingAndGettingHotspotKey(): void
    {
        $this->hotspots->setHotspotKey(self::$hotspotKey);
        $this->assertEquals(self::$hotspotKey, $this->hotspots->getHotspotKey());
    }

    public function testSettingAndGettingSecurityCategory(): void
    {
        $this->hotspots->setSecurityCategory(self::$securityCategory);
        $this->assertEquals(self::$securityCategory, $this->hotspots->getSecurityCategory());
    }

    public function testSettingAndGettingRuleKey(): void
    {
        $this->hotspots->setRuleKey(self::$ruleKey);
        $this->assertEquals(self::$ruleKey, $this->hotspots->getRuleKey());
    }

    public function testSettingAndGettingProbability(): void
    {
        $this->hotspots->setProbability(self::$probability);
        $this->assertEquals(self::$probability, $this->hotspots->getProbability());
    }

    public function testSettingAndGettingStatus(): void
    {
        $this->hotspots->setStatus(self::$status);
        $this->assertEquals(self::$status, $this->hotspots->getStatus());
    }

    public function testSettingAndGettingResolution(): void
    {
        $this->hotspots->setResolution(self::$resolution);
        $this->assertEquals(self::$resolution, $this->hotspots->getResolution());
    }

    public function testSettingAndGettingNiveau(): void
    {
        $this->hotspots->setNiveau(self::$niveau);
        $this->assertEquals(self::$niveau, $this->hotspots->getNiveau());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->hotspots->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->hotspots->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->hotspots->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->hotspots->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate = new \DateTimeImmutable(self::$dateEnregistrement);
        $this->hotspots->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->hotspots->getDateEnregistrement());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new \App\Entity\Hotspots());
        $this->assertEquals(14, count($reflectionClass->getProperties()));
    }
}
