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

use App\Entity\InformationProjet;
use PHPUnit\Framework\TestCase;

/**
 * [Description InformationProjetCaseTest]
 */
class InformationProjetCaseTest extends TestCase
{
    private InformationProjet $informationProjet;

    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static string $analyseKey = 'AYVyxZcQo0TJpgSeq-ph';
    /* MODIF 2026-05-06 : alignement avec entite
     * (setDate -> setDateAnalyse, setType -> setTypeAnalyse). */
    private static string $dateAnalyse = '2024-04-12 16:23:11';
    private static string $projectVersion = '2.0.0-RELEASE';
    private static string $typeAnalyse = 'RELEASE';
    private static int $versionSonar = 59;
    private static int $versionReleaseSonar = 54;
    private static int $versionSnapshotSonar = 3;
    private static int $versionAutreSonar = 2;
    private static string $modeCollecte = 'TRAITEMENT MANUEL';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
    private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): InformationProjet
    {
        return (new informationProjet())
            ->setMavenKey(self::$mavenKey)
            ->setAnalyseKey(self::$analyseKey)
            ->setDateAnalyse(new \DateTimeImmutable(self::$dateAnalyse))
            ->setProjectVersion(self::$projectVersion)
            ->setTypeAnalyse(self::$typeAnalyse)
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
        $newDate = new \DateTimeImmutable(self::$dateAnalyse);
        $this->informationProjet->setDateAnalyse($newDate);
        $this->assertEquals($newDate, $this->informationProjet->getDateAnalyse());
    }

    public function testSettingAndGettingProjectVersion(): void
    {
        $this->informationProjet->setProjectVersion(self::$projectVersion);
        $this->assertEquals(self::$projectVersion, $this->informationProjet->getProjectVersion());
    }

    public function testSettingAndGettingType(): void
    {
        $this->informationProjet->setTypeAnalyse(self::$typeAnalyse);
        $this->assertEquals(self::$typeAnalyse, $this->informationProjet->getTypeAnalyse());
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
        $newDate = new \DateTimeImmutable(self::$dateEnregistrement);
        $this->informationProjet->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->informationProjet->getDateEnregistrement());
    }
}
