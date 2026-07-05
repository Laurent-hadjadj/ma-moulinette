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

use App\Entity\Anomalie;
use PHPUnit\Framework\TestCase;

/**
 * [Description AnomalieCaseTest]
 */
class AnomalieCaseTest extends TestCase
{
    private Anomalie $anomalie;

    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static string $projectName = 'ma-moulinette';
    private static int $anomalieTotal = 1956;
    private static int $detteMinute = 19586;
    private static int $detteReliabilityMinute = 107;
    private static int $detteVulnerabilityMinute = 0;
    private static int $detteCodeSmellMinute = 7369;
    private static string $detteReliability = '0h:5min';
    private static string $detteVulnerability = '0h:0min';
    private static string $dette = '4d, 19h:32min';
    private static string $detteCodeSmell = '5d, 2h:49min';
    private static int $frontend = 806;
    private static int $backend = 0;
    private static int $autre = 0;
    private static int $inconnu = 1;
    private static int $blocker = 0;
    private static int $critical = 0;
    private static int $major = 4750;
    private static int $info = 0;
    private static int $minor = 222;
    private static int $bug = 0;
    private static int $vulnerability = 0;
    private static int $codeSmell = 801;
    private static string $modeCollecte = 'TRAITEMENT MANUEL';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static string $dateEnregistrement = '2024-06-28 17:55:45+02';

    private function getEntity(): Anomalie
    {
        return (new anomalie())
        ->setMavenKey(self::$mavenKey)
        ->setProjectName(self::$projectName)
        ->setAnomalieTotal(self::$anomalieTotal)
        ->setDetteMinute(self::$detteMinute)
        ->setDetteReliabilityMinute(self::$detteReliabilityMinute)
        ->setDetteVulnerabilityMinute(self::$detteVulnerabilityMinute)
        ->setDetteCodeSmellMinute(self::$detteCodeSmellMinute)
        ->setDetteReliability(self::$detteReliability)
        ->setDetteVulnerability(self::$detteVulnerability)
        ->setDetteCodeSmell(self::$detteCodeSmell)
        ->setDette(self::$dette)
        ->setFrontend(self::$frontend)
        ->setBackend(self::$backend)
        ->setAutre(self::$autre)
        ->setInconnu(self::$inconnu)
        ->setBlocker(self::$blocker)
        ->setCritical(self::$critical)
        ->setMajor(self::$major)
        ->setInfo(self::$info)
        ->setMinor(self::$minor)
        ->setBug(self::$bug)
        ->setVulnerability(self::$vulnerability)
        ->setCodeSmell(self::$codeSmell)
        ->setModeCollecte(self::$modeCollecte)
        ->setUtilisateurCollecte(self::$utilisateurCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->anomalie = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->anomalie->setId(1);
        $this->assertEquals(1, $this->anomalie->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->anomalie->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->anomalie->getMavenKey());
    }

    public function testSettingAndGettingProjectName(): void
    {
        $this->anomalie->setProjectName(self::$projectName);
        $this->assertEquals(self::$projectName, $this->anomalie->getProjectName());
    }

    public function testSettingAndGettingAnomalieTotal(): void
    {
        $this->anomalie->setAnomalieTotal(self::$anomalieTotal);
        $this->assertEquals(self::$anomalieTotal, $this->anomalie->getAnomalieTotal());
    }
    public function testSettingAndGettingDetteMinute(): void
    {
        $this->anomalie->setDetteMinute(self::$detteMinute);
        $this->assertEquals(self::$detteMinute, $this->anomalie->getDetteMinute());
    }
    public function testSettingAndGettingDetteReliabilityMinute(): void
    {
        $this->anomalie->setDetteReliabilityMinute(self::$detteReliabilityMinute);
        $this->assertEquals(self::$detteReliabilityMinute, $this->anomalie->getDetteReliabilityMinute());
    }
    public function testSettingAndGettingDetteVulnerabilityMinute(): void
    {
        $this->anomalie->setDetteVulnerabilityMinute(self::$detteVulnerabilityMinute);
        $this->assertEquals(self::$detteVulnerabilityMinute, $this->anomalie->getDetteVulnerabilityMinute());
    }
    public function testSettingAndGettingDetteCodeSmellMinute(): void
    {
        $this->anomalie->setDetteCodeSmellMinute(self::$detteCodeSmellMinute);
        $this->assertEquals(self::$detteCodeSmellMinute, $this->anomalie->getDetteCodeSmellMinute());
    }
    public function testSettingAndGettingDetteReliability(): void
    {
        $this->anomalie->setDetteReliability(self::$detteReliability);
        $this->assertEquals(self::$detteReliability, $this->anomalie->getDetteReliability());
    }
    public function testSettingAndGettingDetteVulnerability(): void
    {
        $this->anomalie->setDetteVulnerability(self::$detteVulnerability);
        $this->assertEquals(self::$detteVulnerability, $this->anomalie->getDetteVulnerability());
    }
    public function testSettingAndGettingDetteCodeSmell(): void
    {
        $this->anomalie->setDetteCodeSmell(self::$detteCodeSmell);
        $this->assertEquals(self::$detteCodeSmell, $this->anomalie->getDetteCodeSmell());
    }
    public function testSettingAndGettingDette(): void
    {
        $this->anomalie->setDette(self::$dette);
        $this->assertEquals(self::$dette, $this->anomalie->getDette());
    }
    public function testSettingAndGettingFrontend(): void
    {
        $this->anomalie->setFrontend(self::$frontend);
        $this->assertEquals(self::$frontend, $this->anomalie->getFrontend());
    }
    public function testSettingAndGettingBackend(): void
    {
        $this->anomalie->setBackend(self::$backend);
        $this->assertEquals(self::$backend, $this->anomalie->getBackend());
    }
    public function testSettingAndGettingAutre(): void
    {
        $this->anomalie->setAutre(self::$autre);
        $this->assertEquals(self::$autre, $this->anomalie->getAutre());
    }
    public function testSettingAndGettingInconnu(): void
    {
        $this->anomalie->setInconnu(self::$inconnu);
        $this->assertEquals(self::$inconnu, $this->anomalie->getInconnu());
    }
    public function testSettingAndGettingBlocker(): void
    {
        $this->anomalie->setBlocker(self::$blocker);
        $this->assertEquals(self::$blocker, $this->anomalie->getBlocker());
    }
    public function testSettingAndGettingCritical(): void
    {
        $this->anomalie->setCritical(self::$critical);
        $this->assertEquals(self::$critical, $this->anomalie->getCritical());
    }
    public function testSettingAndGettingMajor(): void
    {
        $this->anomalie->setMajor(self::$major);
        $this->assertEquals(self::$major, $this->anomalie->getMajor());
    }
    public function testSettingAndGettingInfo(): void
    {
        $this->anomalie->setInfo(self::$info);
        $this->assertEquals(self::$info, $this->anomalie->getInfo());
    }
    public function testSettingAndGettingMinor(): void
    {
        $this->anomalie->setMinor(self::$minor);
        $this->assertEquals(self::$minor, $this->anomalie->getMinor());
    }
    public function testSettingAndGettingBug(): void
    {
        $this->anomalie->setBug(self::$bug);
        $this->assertEquals(self::$bug, $this->anomalie->getBug());
    }
    public function testSettingAndGettingVulnerability(): void
    {
        $this->anomalie->setVulnerability(self::$vulnerability);
        $this->assertEquals(self::$vulnerability, $this->anomalie->getVulnerability());
    }
    public function testSettingAndGettingCodeSmell(): void
    {
        $this->anomalie->setCodeSmell(self::$codeSmell);
        $this->assertEquals(self::$codeSmell, $this->anomalie->getCodeSmell());
    }
    public function testSettingAndGettingModeCollecte(): void
    {
        $this->anomalie->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->anomalie->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->anomalie->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->anomalie->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->anomalie->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->anomalie->getDateEnregistrement());
    }

}
