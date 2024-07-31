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

use App\Entity\ActiviteHistorique;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActiviteHistoriqueCaseTest]
 */
class ActiviteHistoriqueCaseTest extends TestCase
{
    private $activiteHistorique;

    private static $annee = 2024;
    private static $nbJour = 326;
    private static $nbAnalyse = 1253;
    private static $moyenneAnalyse = 87.3;
    private static $nbReussi = 1249;
    private static $nbEchec = 4;
    private static $tauxReussite = 0.99;
    private static $maxTemps = 34;
    private static $dateEnregistrement = '2024-07-14 19:36:33+02';

    private function getEntity(): ActiviteHistorique
    {
        return (new activiteHistorique())
        ->setAnnee(static::$annee)
        ->setNbJour(static::$nbJour)
        ->setNbAnalyse(static::$nbAnalyse)
        ->setMoyenneAnalyse(static::$moyenneAnalyse)
        ->setNbReussi(static::$nbReussi)
        ->setNbEchec(static::$nbEchec)
        ->setTauxReussite(static::$tauxReussite)
        ->setmaxTemps(static::$maxTemps)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->activiteHistorique = $this->getEntity();
    }

    public function testSettingAndGettingAnnee(): void
    {
        $this->activiteHistorique->setAnnee(static::$annee);
        $this->assertEquals(static::$annee, $this->activiteHistorique->getAnnee());
    }
    public function testSettingAndGettingNbJour(): void
    {
        $this->activiteHistorique->setNbJour(static::$nbJour);
        $this->assertEquals(static::$nbJour, $this->activiteHistorique->getnbJour());
    }
    public function testSettingAndGettingNbAnalyse(): void
    {
        $this->activiteHistorique->setNbAnalyse(static::$nbAnalyse);
        $this->assertEquals(static::$nbAnalyse, $this->activiteHistorique->getNbAnalyse());
    }
    public function testSettingAndGettingMoyenneAnalyse(): void
    {
        $this->activiteHistorique->setMoyenneAnalyse(static::$moyenneAnalyse);
        $this->assertEquals(static::$moyenneAnalyse, $this->activiteHistorique->getMoyenneAnalyse());
    }
    public function testSettingAndGettingNbReussi(): void
    {
        $this->activiteHistorique->setNbReussi(static::$nbReussi);
        $this->assertEquals(static::$nbReussi, $this->activiteHistorique->getNbReussi());
    }
    public function testSettingAndGettingNbEchec(): void
    {
        $this->activiteHistorique->setNbEchec(static::$nbEchec);
        $this->assertEquals(static::$nbEchec, $this->activiteHistorique->getNbEchec());
    }
    public function testSettingAndGettingTauxReussite(): void
    {
        $this->activiteHistorique->setTauxReussite(static::$tauxReussite);
        $this->assertEquals(static::$tauxReussite, $this->activiteHistorique->getTauxReussite());
    }
    public function testSettingAndGettingMaxTemps(): void
    {
        $this->activiteHistorique->setMaxTemps(static::$maxTemps);
        $this->assertEquals(static::$maxTemps, $this->activiteHistorique->getMaxTemps());
    }
    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->activiteHistorique->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->activiteHistorique->getDateEnregistrement());
    }

}
