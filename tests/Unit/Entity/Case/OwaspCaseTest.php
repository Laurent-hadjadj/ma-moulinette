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

namespace App\Tests\Unit\Entity\Case;

use App\Entity\Owasp;
use PHPUnit\Framework\TestCase;

/**
 * [Description OwaspCaseTest]
 */
class OwaspCaseTest extends TestCase
{
    private $owasp;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $version = '1.2.0-RELEASE';
    private static $dateVersion = '2024-07-10 15:26:07+02';
    private static $effortTotal = 0;
    private static $a = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aBlocker = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aCritical = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMajor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aInfo = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $aMinor = [0, 0, 0, 0, 0, 0, 0, 0, 0, 0];
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-03-26 14:46:38+02';

    private function getEntity(): Owasp
    {
        $owasp = new Owasp();
            $owasp->setMavenKey(static::$mavenKey);
            $owasp->setVersion(static::$version);
            $owasp->setDateVersion(new \DateTimeImmutable(static::$dateVersion));
            $owasp->setEffortTotal(static::$effortTotal);

        for ($i = 0; $i < 10; $i++) {
            $owasp->{"setA" . ($i + 1)}(static::$a[$i]);
            $owasp->{"setA" . ($i + 1) . "Blocker"}(static::$aBlocker[$i]);
            $owasp->{"setA" . ($i + 1) . "Critical"}(static::$aCritical[$i]);
            $owasp->{"setA" . ($i + 1) . "Major"}(static::$aMajor[$i]);
            $owasp->{"setA" . ($i + 1) . "Info"}(static::$aInfo[$i]);
            $owasp->{"setA" . ($i + 1) . "Minor"}(static::$aMinor[$i]);
        }

            $owasp->setModeCollecte(static::$modeCollecte);
            $owasp->setUtilisateurCollecte(static::$utilisateurCollecte);
            $owasp->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
        return $owasp;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->owasp = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->owasp->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->owasp->getMavenKey());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->owasp->setVersion(static::$version);
        $this->assertEquals(static::$version, $this->owasp->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateVersion);
        $this->owasp->setDateVersion($newDate);
        $this->assertEquals($newDate, $this->owasp->getDateVersion());
    }


    public function testSettingAndGettingEffortTotal(): void
    {
        $this->owasp->setEffortTotal(static::$effortTotal);
        $this->assertEquals(static::$effortTotal, $this->owasp->getEffortTotal());
    }

    public function testSettingAndGettingA(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->owasp->{"setA" . ($i + 1)}(static::$a[$i]);
            $this->assertEquals(static::$a[$i], $this->owasp->{"getA" . ($i + 1)}());
        }
    }

    public function testSettingAndGettingABlocker(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->owasp->{"setA" . ($i + 1) . "Blocker"}(static::$aBlocker[$i]);
            $this->assertEquals(static::$aBlocker[$i], $this->owasp->{"getA" . ($i + 1) . "Blocker"}());
        }
    }

    public function testSettingAndGettingACritical(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->owasp->{"setA" . ($i + 1) . "Critical"}(static::$aCritical[$i]);
            $this->assertEquals(static::$aCritical[$i], $this->owasp->{"getA" . ($i + 1) . "Critical"}());
        }
    }

    public function testSettingAndGettingAMajor(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->owasp->{"setA" . ($i + 1) . "Major"}(static::$aMajor[$i]);
            $this->assertEquals(static::$aMajor[$i], $this->owasp->{"getA" . ($i + 1) . "Major"}());
        }
    }

    public function testSettingAndGettingAInfo(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->owasp->{"setA" . ($i + 1) . "Info"}(static::$aInfo[$i]);
            $this->assertEquals(static::$aInfo[$i], $this->owasp->{"getA" . ($i + 1) . "Info"}());
        }
    }

    public function testSettingAndGettingAMinor(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->owasp->{"setA" . ($i + 1) . "Minor"}(static::$aMinor[$i]);
            $this->assertEquals(static::$aMinor[$i], $this->owasp->{"getA" . ($i + 1) . "Minor"}());
        }
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->owasp->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->owasp->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->owasp->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->owasp->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->owasp->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->owasp->getDateEnregistrement());
    }

}
