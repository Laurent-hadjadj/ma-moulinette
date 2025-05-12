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

use App\Entity\InformationProjet;
use PHPUnit\Framework\TestCase;

/**
 * [Description InformationProjetCaseTest]
 */
class InformationProjetCaseTest extends TestCase
{
    private $informationProjet;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $analyseKey = 'AYVyxZcQo0TJpgSeq-ph';
    private static $date = '2024-04-12 16:23:11';
    private static $projectVersion = '2.0.0-RELEASE';
    private static $type = 'RELEASE';
    private static $versionSonar = 59;
    private static $versionReleaseSonar = 54;
    private static $versionSnapshotSonar = 3;
    private static $versionAutreSonar = 2;
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): InformationProjet
    {
        return (new informationProjet())
        ->setMavenKey(static::$mavenKey)
        ->setAnalyseKey(static::$analyseKey)
        ->setDate(new \DateTimeImmutable(static::$date))
        ->setProjectVersion(static::$projectVersion)
        ->setType(static::$type)
        ->setVersionSonar(static::$versionSonar)
        ->setVersionReleaseSonar(static::$versionReleaseSonar)
        ->setVersionSnapshotSonar(static::$versionSnapshotSonar)
        ->setVersionAutreSonar(static::$versionAutreSonar)
        ->setModeCollecte(static::$modeCollecte)
        ->setUtilisateurCollecte(static::$utilisateurCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
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
        $this->informationProjet->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->informationProjet->getMavenKey());
    }

    public function testSettingAndGettingAnalyseKey(): void
    {
        $this->informationProjet->setAnalyseKey(static::$analyseKey);
        $this->assertEquals(static::$analyseKey, $this->informationProjet->getAnalyseKey());
    }

    public function testSettingAndGettingDate(): void
    {
        $newDate=new \DateTimeImmutable(static::$date);
        $this->informationProjet->setDate($newDate);
        $this->assertEquals($newDate, $this->informationProjet->getDate());
    }

    public function testSettingAndGettingProjectVersion(): void
    {
        $this->informationProjet->setProjectVersion(static::$projectVersion);
        $this->assertEquals(static::$projectVersion, $this->informationProjet->getProjectVersion());
    }

    public function testSettingAndGettingType(): void
    {
        $this->informationProjet->setType(static::$type);
        $this->assertEquals(static::$type, $this->informationProjet->getType());
    }

    public function testSettingAndGettingVersionSonar(): void
    {
        $this->informationProjet->setVersionSonar(static::$versionSonar);
        $this->assertEquals(static::$versionSonar, $this->informationProjet->getVersionSonar());
    }

    public function testSettingAndGettingVersionReleaseSonar(): void
    {
        $this->informationProjet->setVersionReleaseSonar(static::$versionReleaseSonar);
        $this->assertEquals(static::$versionReleaseSonar, $this->informationProjet->getVersionReleaseSonar());
    }

    public function testSettingAndGettingVersionSnapshotSonar(): void
    {
        $this->informationProjet->setVersionSnapshotSonar(static::$versionSnapshotSonar);
        $this->assertEquals(static::$versionSnapshotSonar, $this->informationProjet->getVersionSnapshotSonar());
    }

    public function testSettingAndGettingVersionAutreSonar(): void
    {
        $this->informationProjet->setVersionAutreSonar(static::$versionAutreSonar);
        $this->assertEquals(static::$versionAutreSonar, $this->informationProjet->getVersionAutreSonar());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->informationProjet->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->informationProjet->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->informationProjet->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->informationProjet->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->informationProjet->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->informationProjet->getDateEnregistrement());
    }

}
