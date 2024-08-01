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

namespace App\Tests\Unit\Entity;

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
        ->setMavenKey(static::$mavenKey)
        ->setName(static::$name)
        ->setBugBlocker(static::$bugBlocker)
        ->setBugCritical(static::$bugCritical)
        ->setBugMajor(static::$bugMajor)
        ->setBugInfo(static::$bugInfo)
        ->setBugMinor(static::$bugMinor)
        ->setVulnerabilityBlocker(static::$vulnerabilityBlocker)
        ->setVulnerabilityCritical(static::$vulnerabilityCritical)
        ->setVulnerabilityMajor(static::$vulnerabilityMajor)
        ->setVulnerabilityInfo(static::$vulnerabilityInfo)
        ->setVulnerabilityMinor(static::$vulnerabilityMinor)
        ->setCodeSmellBlocker(static::$codeSmellBlocker)
        ->setCodeSmellCritical(static::$codeSmellCritical)
        ->setCodeSmellMajor(static::$codeSmellMajor)
        ->setCodeSmellInfo(static::$codeSmellInfo)
        ->setCodeSmellMinor(static::$codeSmellMinor)
        ->setUtilisateurCollecte(static::$utilisateurCollecte)
        ->setModeCollecte(static::$modeCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->anomalieDetails = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->anomalieDetails->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->anomalieDetails->getMavenKey());
    }

    public function testSettingAndGettingName(): void
    {
        $this->anomalieDetails->setName(static::$name);
        $this->assertEquals(static::$name, $this->anomalieDetails->getName());
    }

    public function testSettingAndGettingBugBlocker(): void
    {
        $this->anomalieDetails->setBugBlocker(static::$bugBlocker);
        $this->assertEquals(static::$bugBlocker, $this->anomalieDetails->getBugBlocker());
    }
    public function testSettingAndGettingBugCritical(): void
    {
        $this->anomalieDetails->setBugCritical(static::$bugCritical);
        $this->assertEquals(static::$bugCritical, $this->anomalieDetails->getBugCritical());
    }
    public function testSettingAndGettingBugMajor(): void
    {
        $this->anomalieDetails->setBugMajor(static::$bugMajor);
        $this->assertEquals(static::$bugMajor, $this->anomalieDetails->getBugMajor());
    }
    public function testSettingAndGettingBugInfo(): void
    {
        $this->anomalieDetails->setBugInfo(static::$bugInfo);
        $this->assertEquals(static::$bugInfo, $this->anomalieDetails->getBugInfo());
    }
    public function testSettingAndGettingBugMinor(): void
    {
        $this->anomalieDetails->setBugMinor(static::$bugMinor);
        $this->assertEquals(static::$bugMinor, $this->anomalieDetails->getBugMinor());
    }

    public function testSettingAndGettingVulnerabilityBlocker(): void
    {
        $this->anomalieDetails->setVulnerabilityBlocker(static::$vulnerabilityBlocker);
        $this->assertEquals(static::$vulnerabilityBlocker, $this->anomalieDetails->getVulnerabilityBlocker());
    }
    public function testSettingAndGettingVulnerabilityCritical(): void
    {
        $this->anomalieDetails->setVulnerabilityCritical(static::$vulnerabilityCritical);
        $this->assertEquals(static::$vulnerabilityCritical, $this->anomalieDetails->getVulnerabilityCritical());
    }
    public function testSettingAndGettingVulnerabilityMajor(): void
    {
        $this->anomalieDetails->setVulnerabilityMajor(static::$vulnerabilityMajor);
        $this->assertEquals(static::$vulnerabilityMajor, $this->anomalieDetails->getVulnerabilityMajor());
    }
    public function testSettingAndGettingVulnerabilityInfo(): void
    {
        $this->anomalieDetails->setVulnerabilityInfo(static::$vulnerabilityInfo);
        $this->assertEquals(static::$vulnerabilityInfo, $this->anomalieDetails->getVulnerabilityInfo());
    }
    public function testSettingAndGettingVulnerabilityMinor(): void
    {
        $this->anomalieDetails->setVulnerabilityMinor(static::$vulnerabilityMinor);
        $this->assertEquals(static::$vulnerabilityMinor, $this->anomalieDetails->getVulnerabilityMinor());
    }

    public function testSettingAndGettingCodeSmellBlocker(): void
    {
        $this->anomalieDetails->setCodeSmellBlocker(static::$codeSmellBlocker);
        $this->assertEquals(static::$codeSmellBlocker, $this->anomalieDetails->getCodeSmellBlocker());
    }
    public function testSettingAndGettingCodeSmellCritical(): void
    {
        $this->anomalieDetails->setCodeSmellCritical(static::$codeSmellCritical);
        $this->assertEquals(static::$codeSmellCritical, $this->anomalieDetails->getCodeSmellCritical());
    }
    public function testSettingAndGettingCodeSmellMajor(): void
    {
        $this->anomalieDetails->setCodeSmellMajor(static::$codeSmellMajor);
        $this->assertEquals(static::$codeSmellMajor, $this->anomalieDetails->getCodeSmellMajor());
    }
    public function testSettingAndGettingCodeSmellInfo(): void
    {
        $this->anomalieDetails->setCodeSmellInfo(static::$codeSmellInfo);
        $this->assertEquals(static::$codeSmellInfo, $this->anomalieDetails->getCodeSmellInfo());
    }
    public function testSettingAndGettingCodeSmellMinor(): void
    {
        $this->anomalieDetails->setCodeSmellMinor(static::$codeSmellMinor);
        $this->assertEquals(static::$codeSmellMinor, $this->anomalieDetails->getCodeSmellMinor());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->anomalieDetails->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->anomalieDetails->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->anomalieDetails->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->anomalieDetails->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->anomalieDetails->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->anomalieDetails->getDateEnregistrement());
    }

}
