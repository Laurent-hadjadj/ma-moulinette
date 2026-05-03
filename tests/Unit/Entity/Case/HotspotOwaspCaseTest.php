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

use App\Entity\HotspotOwasp;
use PHPUnit\Framework\TestCase;

/**
 * [Description HotspotOwaspCaseTest]
 */
class HotspotOwaspCaseTest extends TestCase
{
    private $hotspotOwasp;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $referentialOwasp = 2017;
    private static $version = '1.2.0-RELEASE';
    private static $dateVersion = '2024-07-10 15:26:07+02';
    private static $menace = 'a1';
    private static $securityCategory = 'dos';
    private static $ruleKey = 'typescript:S5852';
    private static $probability = 'MEDIUM';
    private static $status = 'TO_REVIEW' ;
    private static $resolution = 'Todo';
    private static $niveau = 2 ;
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): HotspotOwasp
    {
        return (new hotspotOwasp())
            ->setMavenKey(self::$mavenKey)
            ->setReferentialOwasp(self::$referentialOwasp)
            ->setVersion(self::$version)
            ->setDateVersion(new \DateTimeImmutable(self::$dateVersion))
            ->setMenace(self::$menace)
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
        $this->hotspotOwasp = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->hotspotOwasp->setId(1);
        $this->assertEquals(1, $this->hotspotOwasp->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->hotspotOwasp->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->hotspotOwasp->getMavenKey());
    }
    public function testSettingAndGettingReferentialOwasp(): void
    {
        $this->hotspotOwasp->setReferentialOwasp(self::$referentialOwasp);
        $this->assertEquals(self::$referentialOwasp, $this->hotspotOwasp->getReferentialOwasp());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->hotspotOwasp->setVersion(self::$version);
        $this->assertEquals(self::$version, $this->hotspotOwasp->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $newDate = new \DateTimeImmutable(self::$dateVersion);
        $this->hotspotOwasp->setDateVersion($newDate);
        $this->assertEquals($newDate, $this->hotspotOwasp->getDateVersion());
    }

    public function testSettingAndGettingMenace(): void
    {
        $this->hotspotOwasp->setMenace(self::$menace);
        $this->assertEquals(self::$menace, $this->hotspotOwasp->getMenace());
    }

    public function testSettingAndGettingSecurityCategory(): void
    {
        $this->hotspotOwasp->setSecurityCategory(self::$securityCategory);
        $this->assertEquals(self::$securityCategory, $this->hotspotOwasp->getSecurityCategory());
    }

    public function testSettingAndGettingRuleKey(): void
    {
        $this->hotspotOwasp->setRuleKey(self::$ruleKey);
        $this->assertEquals(self::$ruleKey, $this->hotspotOwasp->getRuleKey());
    }

    public function testSettingAndGettingProbability(): void
    {
        $this->hotspotOwasp->setProbability(self::$probability);
        $this->assertEquals(self::$probability, $this->hotspotOwasp->getProbability());
    }

    public function testSettingAndGettingStatus(): void
    {
        $this->hotspotOwasp->setStatus(self::$status);
        $this->assertEquals(self::$status, $this->hotspotOwasp->getStatus());
    }

    public function testSettingAndGettingResolution(): void
    {
        $this->hotspotOwasp->setResolution(self::$resolution);
        $this->assertEquals(self::$resolution, $this->hotspotOwasp->getResolution());
    }

    public function testSettingAndGettingNiveau(): void
    {
        $this->hotspotOwasp->setNiveau(self::$niveau);
        $this->assertEquals(self::$niveau, $this->hotspotOwasp->getNiveau());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->hotspotOwasp->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->hotspotOwasp->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->hotspotOwasp->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->hotspotOwasp->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->hotspotOwasp->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->hotspotOwasp->getDateEnregistrement());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new \App\Entity\HotspotOwasp());
        $this->assertEquals(15, count($reflectionClass->getProperties()));
    }
}
