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

use App\Entity\HotspotDetails;
use PHPUnit\Framework\TestCase;

/**
 * [Description HotspotDetailsCaseTest]
 */
class HotspotDetailsCaseTest extends TestCase
{
    private HotspotDetails $hotspotDetails;

    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $version = '1.2.0-RELEASE';
    private static string $dateVersion = '2024-07-10 15:26:07+02';
    private static string $securityCategory = 'dos';
    private static string $ruleKey = 'typescript:S5852';
    private static string $ruleName = 'Using slow regular expressions is security-sensitive';
    private static string $severity = 'MEDIUM';
    private static string $status = 'TO_REVIEW';
    private string $resolution = 'Todo';
    private static int $niveau = 2;
    private static int $frontend = 1;
    private static int $backend = 1;
    private static int $autre = 0;
    private static string $fileName = 'service-worker-network-first.ts';
    private static string $filePath = 'ma-moulinette/angular/src/service-worker-network-first.ts';
    private static int $line = 60;
    private static string $message = 'Make sure the regex used here, which is vulnerable to super-linear runtime due to backtracking, cannot lead to denial of service.';
    private static string $key = 'AZCc06XbgfifxdiJPzw2';
    private static string $modeCollecte = 'TRAITEMENT AUTOMATIQUE';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
    private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

    private function getEntity(): HotspotDetails
    {
        return (new hotspotDetails())
            ->setMavenKey(self::$mavenKey)
            ->setVersion(self::$version)
            ->setDateVersion(new \DateTimeImmutable(self::$dateVersion))
            ->setSecurityCategory(self::$securityCategory)
            ->setRuleKey(self::$ruleKey)
            ->setRuleName(self::$ruleName)
            ->setSeverity(self::$severity)
            ->setStatus(self::$status)
            ->setResolution($this->resolution)
            ->setNiveau(self::$niveau)
            ->setFrontend(self::$frontend)
            ->setBackend(self::$backend)
            ->setAutre(self::$autre)
            ->setFileName(self::$fileName)
            ->setFilePath(self::$filePath)
            ->setLine(self::$line)
            ->setMessage(self::$message)
            ->setHotspotKey(self::$key)
            ->setModeCollecte(self::$modeCollecte)
            ->setUtilisateurCollecte(self::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->hotspotDetails = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->hotspotDetails->setId(1);
        $this->assertEquals(1, $this->hotspotDetails->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->hotspotDetails->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->hotspotDetails->getMavenKey());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->hotspotDetails->setVersion(self::$version);
        $this->assertEquals(self::$version, $this->hotspotDetails->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $newDate = new \DateTimeImmutable(self::$dateVersion);
        $this->hotspotDetails->setDateVersion($newDate);
        $this->assertEquals($newDate, $this->hotspotDetails->getDateVersion());
    }

    public function testSettingAndGettingSecurityCategory(): void
    {
        $this->hotspotDetails->setSecurityCategory(self::$securityCategory);
        $this->assertEquals(self::$securityCategory, $this->hotspotDetails->getSecurityCategory());
    }

    public function testSettingAndGettingRuleKey(): void
    {
        $this->hotspotDetails->setRuleKey(self::$ruleKey);
        $this->assertEquals(self::$ruleKey, $this->hotspotDetails->getRuleKey());
    }

    public function testSettingAndGettingRuleName(): void
    {
        $this->hotspotDetails->setRuleName(self::$ruleName);
        $this->assertEquals(self::$ruleName, $this->hotspotDetails->getRuleName());
    }

    public function testSettingAndGettingSeverity(): void
    {
        $this->hotspotDetails->setSeverity(self::$severity);
        $this->assertEquals(self::$severity, $this->hotspotDetails->getSeverity());
    }

    public function testSettingAndGettingStatus(): void
    {
        $this->hotspotDetails->setStatus(self::$status);
        $this->assertEquals(self::$status, $this->hotspotDetails->getStatus());
    }

    public function testSettingAndGettingResolution(): void
    {
        $this->hotspotDetails->setResolution($this->resolution);
        $this->assertEquals($this->resolution, $this->hotspotDetails->getResolution());
    }

    public function testSettingAndGettingNiveau(): void
    {
        $this->hotspotDetails->setNiveau(self::$niveau);
        $this->assertEquals(self::$niveau, $this->hotspotDetails->getNiveau());
    }

    public function testSettingAndGettingFrontend(): void
    {
        $this->hotspotDetails->setFrontend(self::$frontend);
        $this->assertEquals(self::$frontend, $this->hotspotDetails->getFrontend());
    }

    public function testSettingAndGettingBackend(): void
    {
        $this->hotspotDetails->setBackend(self::$backend);
        $this->assertEquals(self::$backend, $this->hotspotDetails->getBackend());
    }

    public function testSettingAndGettingAutre(): void
    {
        $this->hotspotDetails->setAutre(self::$autre);
        $this->assertEquals(self::$autre, $this->hotspotDetails->getAutre());
    }

    public function testSettingAndGettingFileName(): void
    {
        $this->hotspotDetails->setFileName(self::$fileName);
        $this->assertEquals(self::$fileName, $this->hotspotDetails->getFileName());
    }

    public function testSettingAndGettingFilePath(): void
    {
        $this->hotspotDetails->setFilePath(self::$filePath);
        $this->assertEquals(self::$filePath, $this->hotspotDetails->getFilePath());
    }

    public function testSettingAndGettingLine(): void
    {
        $this->hotspotDetails->setLine(self::$line);
        $this->assertEquals(self::$line, $this->hotspotDetails->getLine());
    }

    public function testSettingAndGettingMessage(): void
    {
        $this->hotspotDetails->setMessage(self::$message);
        $this->assertEquals(self::$message, $this->hotspotDetails->getMessage());
    }

    public function testSettingAndGettingKey(): void
    {
        $this->hotspotDetails->setHotspotKey(self::$key);
        $this->assertEquals(self::$key, $this->hotspotDetails->getHotspotKey());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->hotspotDetails->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->hotspotDetails->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->hotspotDetails->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->hotspotDetails->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate = new \DateTimeImmutable(self::$dateEnregistrement);
        $this->hotspotDetails->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->hotspotDetails->getDateEnregistrement());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new \App\Entity\HotspotDetails());
        $this->assertEquals(22, count($reflectionClass->getProperties()));
    }
}
