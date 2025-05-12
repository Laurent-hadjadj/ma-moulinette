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

use App\Entity\Mesures;
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
    private static $languageDistribution = ['java' => 4278, 'ts' => 18690];
    private static $files = 18;
    private static $classes = 26;
    private static $functions = 52;
    private static $coverage = 10.3;
    private static $duplicatedLinesDensity = 5.1;
    private static $sqaleDebtRatio = 26.0;
    private static $issues = 200;
    private static $tests = 123;
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-04-12 16:23:11';

    private function getEntity(): Mesures
    {
        return (new mesures())
        ->setMavenKey(static::$mavenKey)
        ->setProjectName(static::$projectName)
        ->setLines(static::$lines)
        ->setNcloc(static::$ncloc)
        ->setLanguageDistribution(static::$languageDistribution)
        ->setFiles(static::$files)
        ->setClasses(static::$classes)
        ->setFunctions(static::$functions)
        ->setCoverage(static::$coverage)
        ->setDuplicatedLinesDensity(static::$duplicatedLinesDensity)
        ->setSqaleDebtRatio(static::$sqaleDebtRatio)
        ->setIssues(static::$issues)
        ->setTests(static::$tests)
        ->setModeCollecte(static::$modeCollecte)
        ->setUtilisateurCollecte(static::$utilisateurCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->mesures = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->mesures->setId(1);
        $this->assertEquals(1, $this->mesures->getId());
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

    public function testSettingAndGettingLanguageDistribution(): void
    {
        $this->mesures->setLanguageDistribution(static::$ncloc);
        $this->assertEquals(static::$languageDistribution, $this->mesures->getLanguageDistribution());
    }

    public function testSettingAndGettingFiles(): void
    {
        $this->mesures->setFiles(static::$files);
        $this->assertEquals(static::$files, $this->mesures->getFiles());
    }

    public function testSettingAndGettingClasses(): void
    {
        $this->mesures->setClasses(static::$classes);
        $this->assertEquals(static::$classes, $this->mesures->getClasses());
    }

    public function testSettingAndGettingFunctions(): void
    {
        $this->mesures->setFiles(static::$functions);
        $this->assertEquals(static::$functions, $this->mesures->getFunctions());
    }

    public function testSettingAndGettingCoverage(): void
    {
        $this->mesures->setCoverage(static::$coverage);
        $this->assertEquals(static::$coverage, $this->mesures->getCoverage());
    }

    public function testSettingAndGettingDuplicatedLinesDensity(): void
    {
        $this->mesures->setDuplicatedLinesDensity(static::$duplicatedLinesDensity);
        $this->assertEquals(static::$duplicatedLinesDensity, $this->mesures->getDuplicatedLinesDensity());
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

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->mesures->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->mesures->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->mesures->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->mesures->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->mesures->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->mesures->getDateEnregistrement());
    }

}
