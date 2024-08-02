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

use App\Entity\HotspotOwasp;
use PHPUnit\Framework\TestCase;

/**
 * [Description HotspotOwaspCaseTest]
 */
class HotspotOwaspCaseTest extends TestCase
{
    private $hotspotOwasp;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $referentielOwasp = 2017;
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
            ->setMavenKey(static::$mavenKey)
            ->setReferentielOwasp(static::$referentielOwasp)
            ->setVersion(static::$version)
            ->setDateVersion(new \DateTimeImmutable(static::$dateVersion))
            ->setMenace(static::$menace)
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
        $this->hotspotOwasp = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->hotspotOwasp->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->hotspotOwasp->getMavenKey());
    }
    public function testSettingAndGettingReferentielOwasp(): void
    {
        $this->hotspotOwasp->setReferentielOwasp(static::$referentielOwasp);
        $this->assertEquals(static::$referentielOwasp, $this->hotspotOwasp->getReferentielOwasp());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->hotspotOwasp->setVersion(static::$version);
        $this->assertEquals(static::$version, $this->hotspotOwasp->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $newDate = new \DateTimeImmutable(static::$dateVersion);
        $this->hotspotOwasp->setDateVersion($newDate);
        $this->assertEquals($newDate, $this->hotspotOwasp->getDateVersion());
    }

    public function testSettingAndGettingMenace(): void
    {
        $this->hotspotOwasp->setMenace(static::$menace);
        $this->assertEquals(static::$menace, $this->hotspotOwasp->getMenace());
    }

    public function testSettingAndGettingSecurityCategory(): void
    {
        $this->hotspotOwasp->setSecurityCategory(static::$securityCategory);
        $this->assertEquals(static::$securityCategory, $this->hotspotOwasp->getSecurityCategory());
    }

    public function testSettingAndGettingRuleKey(): void
    {
        $this->hotspotOwasp->setRuleKey(static::$ruleKey);
        $this->assertEquals(static::$ruleKey, $this->hotspotOwasp->getRuleKey());
    }

    public function testSettingAndGettingProbability(): void
    {
        $this->hotspotOwasp->setProbability(static::$probability);
        $this->assertEquals(static::$probability, $this->hotspotOwasp->getProbability());
    }

    public function testSettingAndGettingStatus(): void
    {
        $this->hotspotOwasp->setStatus(static::$status);
        $this->assertEquals(static::$status, $this->hotspotOwasp->getStatus());
    }

    public function testSettingAndGettingResolution(): void
    {
        $this->hotspotOwasp->setResolution(static::$resolution);
        $this->assertEquals(static::$resolution, $this->hotspotOwasp->getResolution());
    }

    public function testSettingAndGettingNiveau(): void
    {
        $this->hotspotOwasp->setNiveau(static::$niveau);
        $this->assertEquals(static::$niveau, $this->hotspotOwasp->getNiveau());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->hotspotOwasp->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->hotspotOwasp->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->hotspotOwasp->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->hotspotOwasp->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->hotspotOwasp->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->hotspotOwasp->getDateEnregistrement());
    }

}
