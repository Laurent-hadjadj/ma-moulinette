<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Case;

use App\Entity\ActivityHistorique;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActivityHistoriqueCaseTest]
 */
class ActivityHistoriqueCaseTest extends TestCase
{
    private $activityHistorique;

    private static $year = 2024;
    private static $day = 326;
    private static $analyse = 1253;
    private static $analyseAverage = 87.3;
    private static $success = 1249;
    private static $failed = 4;
    private static $successRate = 0.99;
    private static $maxTime = 34;
    private static $dateEnregistrement = '2024-07-14 19:36:33+02';

    private function getEntity(): activityHistorique
    {
        return (new activityHistorique())
        ->setYear(static::$year)
        ->setDay(static::$day)
        ->setAnalyse(static::$analyse)
        ->setAnalyseAverage(static::$analyseAverage)
        ->setSuccess(static::$success)
        ->setFailed(static::$failed)
        ->setSuccessRate(static::$successRate)
        ->setMaxTime(static::$maxTime)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityHistorique = $this->getEntity();
    }

    public function testSettingAndGettingYear(): void
    {
        $this->activityHistorique->setYear(static::$year);
        $this->assertEquals(static::$year, $this->activityHistorique->getYear());
    }
    public function testSettingAndGettingDay(): void
    {
        $this->activityHistorique->setDay(static::$day);
        $this->assertEquals(static::$day, $this->activityHistorique->getDay());
    }
    public function testSettingAndGettingAnalyse(): void
    {
        $this->activityHistorique->setAnalyse(static::$analyse);
        $this->assertEquals(static::$analyse, $this->activityHistorique->getAnalyse());
    }
    public function testSettingAndGettingAnalyseAverage(): void
    {
        $this->activityHistorique->setAnalyseAverage(static::$analyseAverage);
        $this->assertEquals(static::$analyseAverage, $this->activityHistorique->getAnalyseAverage());
    }
    public function testSettingAndGettingSuccess(): void
    {
        $this->activityHistorique->setSuccess(static::$success);
        $this->assertEquals(static::$success, $this->activityHistorique->getSuccess());
    }
    public function testSettingAndGettingFailed(): void
    {
        $this->activityHistorique->setFailed(static::$failed);
        $this->assertEquals(static::$failed, $this->activityHistorique->getFailed());
    }
    public function testSettingAndGettingSuccessRate(): void
    {
        $this->activityHistorique->setSuccessRate(static::$successRate);
        $this->assertEquals(static::$successRate, $this->activityHistorique->getSuccessRate());
    }
    public function testSettingAndGettingMaxTime(): void
    {
        $this->activityHistorique->setMaxTime(static::$maxTime);
        $this->assertEquals(static::$maxTime, $this->activityHistorique->getMaxTime());
    }
    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->activityHistorique->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->activityHistorique->getDateEnregistrement());
    }

}
