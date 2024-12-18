<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
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
    private $hotspotDetails;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $version = '1.2.0-RELEASE';
    private static $dateVersion = '2024-07-10 15:26:07+02';
    private static $securityCategory = 'dos';
    private static $ruleKey = 'typescript:S5852';
    private static $ruleName = 'Using slow regular expressions is security-sensitive';
    private static $severity = 'MEDIUM';
    private static $status = 'TO_REVIEW';
    private string $resolution = 'Todo';
    private static $niveau = 2;
    private static $frontend = 1;
    private static $backend = 1;
    private static $autre= 0;
    private static $fileName = 'service-worker-network-first.ts';
    private static $filePath = 'ma-moulinette/angular/src/service-worker-network-first.ts';
    private static $line = 60;
    private static $message = 'Make sure the regex used here, which is vulnerable to super-linear runtime due to backtracking, cannot lead to denial of service.';
    private static $key = 'AZCc06XbgfifxdiJPzw2';
    private static $modeCollecte = 'TRAITEMENT AUTOMATIQUE';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-03-26 14:46:38+02';

    private function getEntity(): HotspotDetails
    {
        return (new hotspotDetails())
            ->setMavenKey(static::$mavenKey)
            ->setVersion(static::$version)
            ->setDateVersion(new \DateTimeImmutable(static::$dateVersion))
            ->setSecurityCategory(static::$securityCategory)
            ->setRuleKey(static::$ruleKey)
            ->setRuleName(static::$ruleName)
            ->setSeverity(static::$severity)
            ->setStatus(static::$status)
            ->setResolution($this->resolution)
            ->setNiveau(static::$niveau)
            ->setFrontend(static::$frontend)
            ->setBackend(static::$backend)
            ->setAutre(static::$autre)
            ->setFileName(static::$fileName)
            ->setFilePath(static::$filePath)
            ->setLine(static::$line)
            ->setMessage(static::$message)
            ->setHotspotKey(static::$key)
            ->setModeCollecte(static::$modeCollecte)
            ->setUtilisateurCollecte(static::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->hotspotDetails = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->hotspotDetails->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->hotspotDetails->getMavenKey());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->hotspotDetails->setVersion(static::$version);
        $this->assertEquals(static::$version, $this->hotspotDetails->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $newDate = new \DateTimeImmutable(static::$dateVersion);
        $this->hotspotDetails->setDateVersion($newDate);
        $this->assertEquals($newDate, $this->hotspotDetails->getDateVersion());
    }

    public function testSettingAndGettingSecurityCategory(): void
    {
        $this->hotspotDetails->setSecurityCategory(static::$securityCategory);
        $this->assertEquals(static::$securityCategory, $this->hotspotDetails->getSecurityCategory());
    }

    public function testSettingAndGettingRuleKey(): void
    {
        $this->hotspotDetails->setRuleKey(static::$ruleKey);
        $this->assertEquals(static::$ruleKey, $this->hotspotDetails->getRuleKey());
    }

    public function testSettingAndGettingRuleName(): void
    {
        $this->hotspotDetails->setRuleName(static::$ruleName);
        $this->assertEquals(static::$ruleName, $this->hotspotDetails->getRuleName());
    }

    public function testSettingAndGettingSeverity(): void
    {
        $this->hotspotDetails->setSeverity(static::$severity);
        $this->assertEquals(static::$severity, $this->hotspotDetails->getSeverity());
    }

    public function testSettingAndGettingStatus(): void
    {
        $this->hotspotDetails->setStatus(static::$status);
        $this->assertEquals(static::$status, $this->hotspotDetails->getStatus());
    }

    public function testSettingAndGettingResolution(): void
    {
        $this->hotspotDetails->setResolution($this->resolution);
        $this->assertEquals($this->resolution, $this->hotspotDetails->getResolution());
    }

    public function testSettingAndGettingNiveau(): void
    {
        $this->hotspotDetails->setNiveau(static::$niveau);
        $this->assertEquals(static::$niveau, $this->hotspotDetails->getNiveau());
    }

    public function testSettingAndGettingFrontend(): void
    {
        $this->hotspotDetails->setFrontend(static::$frontend);
        $this->assertEquals(static::$frontend, $this->hotspotDetails->getFrontend());
    }

    public function testSettingAndGettingBackend(): void
    {
        $this->hotspotDetails->setBackend(static::$backend);
        $this->assertEquals(static::$backend, $this->hotspotDetails->getBackend());
    }

    public function testSettingAndGettingAutre(): void
    {
        $this->hotspotDetails->setAutre(static::$autre);
        $this->assertEquals(static::$autre, $this->hotspotDetails->getAutre());
    }

    public function testSettingAndGettingFileName(): void
    {
        $this->hotspotDetails->setFileName(static::$fileName);
        $this->assertEquals(static::$fileName, $this->hotspotDetails->getFileName());
    }

    public function testSettingAndGettingFilePath(): void
    {
        $this->hotspotDetails->setFilePath(static::$filePath);
        $this->assertEquals(static::$filePath, $this->hotspotDetails->getFilePath());
    }

    public function testSettingAndGettingLine(): void
    {
        $this->hotspotDetails->setLine(static::$line);
        $this->assertEquals(static::$line, $this->hotspotDetails->getLine());
    }

    public function testSettingAndGettingMessage(): void
    {
        $this->hotspotDetails->setMessage(static::$message);
        $this->assertEquals(static::$message, $this->hotspotDetails->getMessage());
    }

    public function testSettingAndGettingKey(): void
    {
        $this->hotspotDetails->setHotspotKey(static::$key);
        $this->assertEquals(static::$key, $this->hotspotDetails->getHotspotKey());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->hotspotDetails->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->hotspotDetails->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->hotspotDetails->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->hotspotDetails->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->hotspotDetails->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->hotspotDetails->getDateEnregistrement());
    }

}
