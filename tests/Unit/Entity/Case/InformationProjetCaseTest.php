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

use App\Entity\InformationProjet;
use PHPUnit\Framework\TestCase;

/**
 * [Description InformationProjetCaseTest]
 */
class InformationProjetCaseTest extends TestCase
{
    private $informationProjet;

    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static string $analyseKey = 'AYVyxZcQo0TJpgSeq-ph';
    private static string $date = '2024-04-12 16:23:11';
    private static string $projectVersion = '2.0.0-RELEASE';
    private static string $type = 'RELEASE';
    private static int $versionSonar = 59;
    private static int $versionReleaseSonar = 54;
    private static int $versionSnapshotSonar = 3;
    private static int $versionAutreSonar = 2;
    private static string $modeCollecte = 'TRAITEMENT MANUEL';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): InformationProjet
    {
        return (new informationProjet())
        ->setMavenKey(self::$mavenKey)
        ->setAnalyseKey(self::$analyseKey)
        ->setDate(new \DateTimeImmutable(self::$date))
        ->setProjectVersion(self::$projectVersion)
        ->setType(self::$type)
        ->setVersionSonar(self::$versionSonar)
        ->setVersionReleaseSonar(self::$versionReleaseSonar)
        ->setVersionSnapshotSonar(self::$versionSnapshotSonar)
        ->setVersionAutreSonar(self::$versionAutreSonar)
        ->setModeCollecte(self::$modeCollecte)
        ->setUtilisateurCollecte(self::$utilisateurCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->informationProjet = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->informationProjet->setId(1);
        $this->assertEquals(1, $this->informationProjet->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->informationProjet->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->informationProjet->getMavenKey());
    }

    public function testSettingAndGettingAnalyseKey(): void
    {
        $this->informationProjet->setAnalyseKey(self::$analyseKey);
        $this->assertEquals(self::$analyseKey, $this->informationProjet->getAnalyseKey());
    }

    public function testSettingAndGettingDate(): void
    {
        $newDate=new \DateTimeImmutable(self::$date);
        $this->informationProjet->setDate($newDate);
        $this->assertEquals($newDate, $this->informationProjet->getDate());
    }

    public function testSettingAndGettingProjectVersion(): void
    {
        $this->informationProjet->setProjectVersion(self::$projectVersion);
        $this->assertEquals(self::$projectVersion, $this->informationProjet->getProjectVersion());
    }

    public function testSettingAndGettingType(): void
    {
        $this->informationProjet->setType(self::$type);
        $this->assertEquals(self::$type, $this->informationProjet->getType());
    }

    public function testSettingAndGettingVersionSonar(): void
    {
        $this->informationProjet->setVersionSonar(self::$versionSonar);
        $this->assertEquals(self::$versionSonar, $this->informationProjet->getVersionSonar());
    }

    public function testSettingAndGettingVersionReleaseSonar(): void
    {
        $this->informationProjet->setVersionReleaseSonar(self::$versionReleaseSonar);
        $this->assertEquals(self::$versionReleaseSonar, $this->informationProjet->getVersionReleaseSonar());
    }

    public function testSettingAndGettingVersionSnapshotSonar(): void
    {
        $this->informationProjet->setVersionSnapshotSonar(self::$versionSnapshotSonar);
        $this->assertEquals(self::$versionSnapshotSonar, $this->informationProjet->getVersionSnapshotSonar());
    }

    public function testSettingAndGettingVersionAutreSonar(): void
    {
        $this->informationProjet->setVersionAutreSonar(self::$versionAutreSonar);
        $this->assertEquals(self::$versionAutreSonar, $this->informationProjet->getVersionAutreSonar());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->informationProjet->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->informationProjet->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->informationProjet->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->informationProjet->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->informationProjet->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->informationProjet->getDateEnregistrement());
    }

}
