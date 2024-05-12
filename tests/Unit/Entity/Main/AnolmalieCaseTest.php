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

use App\Entity\Main\Anomalie;
use PHPUnit\Framework\TestCase;

/**
 * [Description AnomalieCaseTest]
 */
class AnomalieCaseTest extends TestCase
{
    private $anomalie;

    private static $mavenKey = 'fr.map-petite-entreprise:ma-moulinette';
    private static $projectName = 'ma-moulinette';
    private static $anomalieTotal = 1956;
    private static $detteMinute = 19586;
    private static $detteReliabilityMinute = 107;
    private static $detteVulnerabilityMinute = 0;
    private static $detteCodeSmellMinute = 7369;
    private static $detteReliability = '0h:5min';
    private static $detteVulnerability = '0h:0min';
    private static $dette = '4d, 19h:32min';
    private static $detteCodeSmell = '5d, 2h:49min';
    private static $frontend = 806;
    private static $backend = 0;
    private static $autre = 0;
    private static $blocker = 0;
    private static $critical = 0;
    private static $major = 4750;
    private static $info = 0;
    private static $minor = 222;
    private static $bug = 0;
    private static $vulnerability = 0;
    private static $codeSmell = 801;
    private static $dateEnregistrement = '2024-03-25 12:26:58';

    private function getEntity(): Anomalie
    {
        return (new anomalie())
        ->setMavenKey(static::$mavenKey)
        ->setProjectName(static::$projectName)
        ->setAnomalieTotal(static::$anomalieTotal)
        ->setDetteMinute(static::$detteMinute)
        ->setDetteReliabilityMinute(static::$detteReliabilityMinute)
        ->setDetteVulnerabilityMinute(static::$detteVulnerabilityMinute)
        ->setDetteCodeSmellMinute(static::$detteCodeSmellMinute)
        ->setDetteReliability(static::$detteReliability)
        ->setDetteVulnerability(static::$detteVulnerability)
        ->setDetteCodeSmell(static::$detteCodeSmell)
        ->setDette(static::$dette)
        ->setFrontend(static::$frontend)
        ->setBackend(static::$backend)
        ->setAutre(static::$autre)
        ->setBlocker(static::$blocker)
        ->setCritical(static::$critical)
        ->setMajor(static::$major)
        ->setInfo(static::$info)
        ->setMinor(static::$minor)
        ->setBug(static::$bug)
        ->setVulnerability(static::$vulnerability)
        ->setCodeSmell(static::$codeSmell)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->anomalie = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->anomalie->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->anomalie->getMavenKey());
    }

    public function testSettingAndGettingProjectName(): void
    {
        $this->anomalie->setProjectName(static::$projectName);
        $this->assertEquals(static::$projectName, $this->anomalie->getProjectName());
    }

    public function testSettingAndGettingAnomalieTotal(): void
    {
        $this->anomalie->setAnomalieTotal(static::$anomalieTotal);
        $this->assertEquals(static::$anomalieTotal, $this->anomalie->getAnomalieTotal());
    }
    public function testSettingAndGettingDetteMinute(): void
    {
        $this->anomalie->setDetteMinute(static::$detteMinute);
        $this->assertEquals(static::$detteMinute, $this->anomalie->getDetteMinute());
    }
    public function testSettingAndGettingDetteReliabilityMinute(): void
    {
        $this->anomalie->setDetteReliabilityMinute(static::$detteReliabilityMinute);
        $this->assertEquals(static::$detteReliabilityMinute, $this->anomalie->getDetteReliabilityMinute());
    }
    public function testSettingAndGettingDetteVulnerabilityMinute(): void
    {
        $this->anomalie->setDetteVulnerabilityMinute(static::$detteVulnerabilityMinute);
        $this->assertEquals(static::$detteVulnerabilityMinute, $this->anomalie->getDetteVulnerabilityMinute());
    }
    public function testSettingAndGettingDetteCodeSmellMinute(): void
    {
        $this->anomalie->setDetteCodeSmellMinute(static::$detteCodeSmellMinute);
        $this->assertEquals(static::$detteCodeSmellMinute, $this->anomalie->getDetteCodeSmellMinute());
    }
    public function testSettingAndGettingDetteReliability(): void
    {
        $this->anomalie->setDetteReliability(static::$detteReliability);
        $this->assertEquals(static::$detteReliability, $this->anomalie->getDetteReliability());
    }
    public function testSettingAndGettingDetteVulnerability(): void
    {
        $this->anomalie->setDetteVulnerability(static::$detteVulnerability);
        $this->assertEquals(static::$detteVulnerability, $this->anomalie->getDetteVulnerability());
    }
    public function testSettingAndGettingDetteCodeSmell(): void
    {
        $this->anomalie->setDetteCodeSmell(static::$detteCodeSmell);
        $this->assertEquals(static::$detteCodeSmell, $this->anomalie->getDetteCodeSmell());
    }
    public function testSettingAndGettingDette(): void
    {
        $this->anomalie->setDette(static::$dette);
        $this->assertEquals(static::$dette, $this->anomalie->getDette());
    }
    public function testSettingAndGettingFrontend(): void
    {
        $this->anomalie->setFrontend(static::$frontend);
        $this->assertEquals(static::$frontend, $this->anomalie->getFrontend());
    }
    public function testSettingAndGettingBackend(): void
    {
        $this->anomalie->setBackend(static::$backend);
        $this->assertEquals(static::$backend, $this->anomalie->getBackend());
    }
    public function testSettingAndGettingAutre(): void
    {
        $this->anomalie->setAutre(static::$autre);
        $this->assertEquals(static::$autre, $this->anomalie->getAutre());
    }
    public function testSettingAndGettingBlocker(): void
    {
        $this->anomalie->setBlocker(static::$blocker);
        $this->assertEquals(static::$blocker, $this->anomalie->getBlocker());
    }
    public function testSettingAndGettingCritical(): void
    {
        $this->anomalie->setCritical(static::$critical);
        $this->assertEquals(static::$critical, $this->anomalie->getCritical());
    }
    public function testSettingAndGettingMajor(): void
    {
        $this->anomalie->setMajor(static::$major);
        $this->assertEquals(static::$major, $this->anomalie->getMajor());
    }
    public function testSettingAndGettingInfo(): void
    {
        $this->anomalie->setInfo(static::$info);
        $this->assertEquals(static::$info, $this->anomalie->getInfo());
    }
    public function testSettingAndGettingMinor(): void
    {
        $this->anomalie->setMinor(static::$minor);
        $this->assertEquals(static::$minor, $this->anomalie->getMinor());
    }
    public function testSettingAndGettingBug(): void
    {
        $this->anomalie->setBug(static::$bug);
        $this->assertEquals(static::$bug, $this->anomalie->getBug());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTime(static::$dateEnregistrement);
        $this->anomalie->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->anomalie->getDateEnregistrement());
    }

}
