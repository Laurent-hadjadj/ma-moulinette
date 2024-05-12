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

namespace App\Tests\Unit\Entity\Main;

use App\Entity\Main\Mesures;
use PHPUnit\Framework\TestCase;

/**
 * [Description MesuresCaseTest]
 */
class MesuresCaseTest extends TestCase
{
    private $mesures;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $projectName = 'Ma-Moulinette';
    private static $lines = 22015;
    private static $ncloc = 10043;
    private static $coverage = 10.3;
    private static $duplicationDensity = 5.1;
    private static $sqaleDebtRatio = 26.0;
    private static $issues = 200;
    private static $tests = 123;
    private static $dateEnregistrement = '2024-04-12 16:23:11';

    private function getEntity(): Mesures
    {
        return (new mesures())
        ->setMavenKey(static::$mavenKey)
        ->setProjectName(static::$projectName)
        ->setLines(static::$lines)
        ->setNcloc(static::$ncloc)
        ->setCoverage(static::$coverage)
        ->setDuplicationDensity(static::$duplicationDensity)
        ->setSqaleDebtRatio(static::$sqaleDebtRatio)
        ->setIssues(static::$issues)
        ->setTests(static::$tests)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->mesures = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->mesures->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->mesures->getMavenKey());
    }

    public function testSettingAndGettingProjectName(): void
    {
        $this->mesures->setProjectName(static::$projectName);
        $this->assertEquals(static::$projectName, $this->mesures->getProjectName());
    }

    public function testSettingAndGettingLines(): void
    {
        $this->mesures->setLines(static::$lines);
        $this->assertEquals(static::$lines, $this->mesures->getLines());
    }

    public function testSettingAndGettingNcloc(): void
    {
        $this->mesures->setNcloc(static::$ncloc);
        $this->assertEquals(static::$ncloc, $this->mesures->getNcloc());
    }

    public function testSettingAndGettingCoverage(): void
    {
        $this->mesures->setCoverage(static::$coverage);
        $this->assertEquals(static::$coverage, $this->mesures->getCoverage());
    }

    public function testSettingAndGettingDuplicationDensity(): void
    {
        $this->mesures->setDuplicationDensity(static::$duplicationDensity);
        $this->assertEquals(static::$duplicationDensity, $this->mesures->getDuplicationDensity());
    }

    public function testSettingAndGettingSqaleDebtRatio(): void
    {
        $this->mesures->setSqaleDebtRatio(static::$sqaleDebtRatio);
        $this->assertEquals(static::$sqaleDebtRatio, $this->mesures->getSqaleDebtRatio());
    }

    public function testSettingAndGettingIssues(): void
    {
        $this->mesures->setIssues(static::$issues);
        $this->assertEquals(static::$issues, $this->mesures->getIssues());
    }

    public function testSettingAndGettingTests(): void
    {
        $this->mesures->setTests(static::$tests);
        $this->assertEquals(static::$tests, $this->mesures->getTests());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTime(static::$dateEnregistrement);
        $this->mesures->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->mesures->getDateEnregistrement());
    }

}
