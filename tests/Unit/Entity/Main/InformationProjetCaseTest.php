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

use App\Entity\Main\InformationProjet;
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
    private static $dateEnregistrement = '2024-04-12 16:23:11';

    private function getEntity(): InformationProjet
    {
        return (new informationProjet())
        ->setMavenKey(static::$mavenKey)
        ->setAnalyseKey(static::$analyseKey)
        ->setDate(new \DateTime(static::$date))
        ->setProjectVersion(static::$projectVersion)
        ->setType(static::$type)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->informationProjet = $this->getEntity();
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
        $newDate=new \DateTime(static::$date);
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

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTime(static::$dateEnregistrement);
        $this->informationProjet->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->informationProjet->getDateEnregistrement());
    }

}
