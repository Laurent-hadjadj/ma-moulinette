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

use App\Entity\AnomalieDetails;
use PHPUnit\Framework\TestCase;

/**
 * [Description AnomalieDetailsCaseTest]
 */
class AnomalieDetailsCaseTest extends TestCase
{
    private $anomalieDetails;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $name = 'ma-moulinette';
    private static $bugBlocker = 7;
    private static $bugCritical = 0;
    private static $bugMajor = 44;
    private static $bugInfo = 37;
    private static $bugMinor = 0;
    private static $vulnerabilityBlocker = 0;
    private static $vulnerabilityCritical = 9;
    private static $vulnerabilityMajor = 0;
    private static $vulnerabilityInfo = 0;
    private static $vulnerabilityMinor = 0;
    private static $codeSmellBlocker = 0;
    private static $codeSmellCritical = 4;
    private static $codeSmellMajor = 109;
    private static $codeSmellInfo = 72;
    private static $codeSmellMinor = 13;
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $dateEnregistrement = '2024-07-14 19:36:33+02';

    private function getEntity(): AnomalieDetails
    {
        return (new anomalieDetails())
        ->setMavenKey(self::$mavenKey)
        ->setName(self::$name)
        ->setBugBlocker(self::$bugBlocker)
        ->setBugCritical(self::$bugCritical)
        ->setBugMajor(self::$bugMajor)
        ->setBugInfo(self::$bugInfo)
        ->setBugMinor(self::$bugMinor)
        ->setVulnerabilityBlocker(self::$vulnerabilityBlocker)
        ->setVulnerabilityCritical(self::$vulnerabilityCritical)
        ->setVulnerabilityMajor(self::$vulnerabilityMajor)
        ->setVulnerabilityInfo(self::$vulnerabilityInfo)
        ->setVulnerabilityMinor(self::$vulnerabilityMinor)
        ->setCodeSmellBlocker(self::$codeSmellBlocker)
        ->setCodeSmellCritical(self::$codeSmellCritical)
        ->setCodeSmellMajor(self::$codeSmellMajor)
        ->setCodeSmellInfo(self::$codeSmellInfo)
        ->setCodeSmellMinor(self::$codeSmellMinor)
        ->setUtilisateurCollecte(self::$utilisateurCollecte)
        ->setModeCollecte(self::$modeCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->anomalieDetails = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->anomalieDetails->setId(1);
        $this->assertEquals(1, $this->anomalieDetails->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->anomalieDetails->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->anomalieDetails->getMavenKey());
    }

    public function testSettingAndGettingName(): void
    {
        $this->anomalieDetails->setName(self::$name);
        $this->assertEquals(self::$name, $this->anomalieDetails->getName());
    }

    public function testSettingAndGettingBugBlocker(): void
    {
        $this->anomalieDetails->setBugBlocker(self::$bugBlocker);
        $this->assertEquals(self::$bugBlocker, $this->anomalieDetails->getBugBlocker());
    }
    public function testSettingAndGettingBugCritical(): void
    {
        $this->anomalieDetails->setBugCritical(self::$bugCritical);
        $this->assertEquals(self::$bugCritical, $this->anomalieDetails->getBugCritical());
    }
    public function testSettingAndGettingBugMajor(): void
    {
        $this->anomalieDetails->setBugMajor(self::$bugMajor);
        $this->assertEquals(self::$bugMajor, $this->anomalieDetails->getBugMajor());
    }
    public function testSettingAndGettingBugInfo(): void
    {
        $this->anomalieDetails->setBugInfo(self::$bugInfo);
        $this->assertEquals(self::$bugInfo, $this->anomalieDetails->getBugInfo());
    }
    public function testSettingAndGettingBugMinor(): void
    {
        $this->anomalieDetails->setBugMinor(self::$bugMinor);
        $this->assertEquals(self::$bugMinor, $this->anomalieDetails->getBugMinor());
    }

    public function testSettingAndGettingVulnerabilityBlocker(): void
    {
        $this->anomalieDetails->setVulnerabilityBlocker(self::$vulnerabilityBlocker);
        $this->assertEquals(self::$vulnerabilityBlocker, $this->anomalieDetails->getVulnerabilityBlocker());
    }
    public function testSettingAndGettingVulnerabilityCritical(): void
    {
        $this->anomalieDetails->setVulnerabilityCritical(self::$vulnerabilityCritical);
        $this->assertEquals(self::$vulnerabilityCritical, $this->anomalieDetails->getVulnerabilityCritical());
    }
    public function testSettingAndGettingVulnerabilityMajor(): void
    {
        $this->anomalieDetails->setVulnerabilityMajor(self::$vulnerabilityMajor);
        $this->assertEquals(self::$vulnerabilityMajor, $this->anomalieDetails->getVulnerabilityMajor());
    }
    public function testSettingAndGettingVulnerabilityInfo(): void
    {
        $this->anomalieDetails->setVulnerabilityInfo(self::$vulnerabilityInfo);
        $this->assertEquals(self::$vulnerabilityInfo, $this->anomalieDetails->getVulnerabilityInfo());
    }
    public function testSettingAndGettingVulnerabilityMinor(): void
    {
        $this->anomalieDetails->setVulnerabilityMinor(self::$vulnerabilityMinor);
        $this->assertEquals(self::$vulnerabilityMinor, $this->anomalieDetails->getVulnerabilityMinor());
    }

    public function testSettingAndGettingCodeSmellBlocker(): void
    {
        $this->anomalieDetails->setCodeSmellBlocker(self::$codeSmellBlocker);
        $this->assertEquals(self::$codeSmellBlocker, $this->anomalieDetails->getCodeSmellBlocker());
    }
    public function testSettingAndGettingCodeSmellCritical(): void
    {
        $this->anomalieDetails->setCodeSmellCritical(self::$codeSmellCritical);
        $this->assertEquals(self::$codeSmellCritical, $this->anomalieDetails->getCodeSmellCritical());
    }
    public function testSettingAndGettingCodeSmellMajor(): void
    {
        $this->anomalieDetails->setCodeSmellMajor(self::$codeSmellMajor);
        $this->assertEquals(self::$codeSmellMajor, $this->anomalieDetails->getCodeSmellMajor());
    }
    public function testSettingAndGettingCodeSmellInfo(): void
    {
        $this->anomalieDetails->setCodeSmellInfo(self::$codeSmellInfo);
        $this->assertEquals(self::$codeSmellInfo, $this->anomalieDetails->getCodeSmellInfo());
    }
    public function testSettingAndGettingCodeSmellMinor(): void
    {
        $this->anomalieDetails->setCodeSmellMinor(self::$codeSmellMinor);
        $this->assertEquals(self::$codeSmellMinor, $this->anomalieDetails->getCodeSmellMinor());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->anomalieDetails->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->anomalieDetails->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->anomalieDetails->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->anomalieDetails->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->anomalieDetails->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->anomalieDetails->getDateEnregistrement());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new \App\Entity\AnomalieDetails());
        $this->assertEquals(21, count($reflectionClass->getProperties()));
    }
}
