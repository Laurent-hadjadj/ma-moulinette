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

use App\Entity\ActivityHistorique;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActivityHistoriqueCaseTest]
 */
class ActivityHistoriqueCaseTest extends TestCase
{
    private ActivityHistorique $activityHistorique;

    private static int $year = 2024;
    private static int $day = 326;
    private static int $analyse = 1253;
    private static float $analyseAverage = 87.3;
    private static int $success = 1249;
    private static int $failed = 4;
    private static float $successRate = 0.99;
    private static int $maxTime = 34;
    private static string $dateEnregistrement = '2024-07-14 19:36:33+02';

    private function getEntity(): ActivityHistorique
    {
        return (new ActivityHistorique())
        ->setYear(self::$year)
        ->setDay(self::$day)
        ->setAnalyse(self::$analyse)
        ->setAnalyseAverage(self::$analyseAverage)
        ->setSuccess(self::$success)
        ->setFailed(self::$failed)
        ->setSuccessRate(self::$successRate)
        ->setMaxTime(self::$maxTime)
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityHistorique = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->activityHistorique->setId(1);
        $this->assertEquals(1, $this->activityHistorique->getId());
    }

    public function testSettingAndGettingYear(): void
    {
        $this->activityHistorique->setYear(self::$year);
        $this->assertEquals(self::$year, $this->activityHistorique->getYear());
    }
    public function testSettingAndGettingDay(): void
    {
        $this->activityHistorique->setDay(self::$day);
        $this->assertEquals(self::$day, $this->activityHistorique->getDay());
    }
    public function testSettingAndGettingAnalyse(): void
    {
        $this->activityHistorique->setAnalyse(self::$analyse);
        $this->assertEquals(self::$analyse, $this->activityHistorique->getAnalyse());
    }
    public function testSettingAndGettingAnalyseAverage(): void
    {
        $this->activityHistorique->setAnalyseAverage(self::$analyseAverage);
        $this->assertEquals(self::$analyseAverage, $this->activityHistorique->getAnalyseAverage());
    }
    public function testSettingAndGettingSuccess(): void
    {
        $this->activityHistorique->setSuccess(self::$success);
        $this->assertEquals(self::$success, $this->activityHistorique->getSuccess());
    }
    public function testSettingAndGettingFailed(): void
    {
        $this->activityHistorique->setFailed(self::$failed);
        $this->assertEquals(self::$failed, $this->activityHistorique->getFailed());
    }
    public function testSettingAndGettingSuccessRate(): void
    {
        $this->activityHistorique->setSuccessRate(self::$successRate);
        $this->assertEquals(self::$successRate, $this->activityHistorique->getSuccessRate());
    }
    public function testSettingAndGettingMaxTime(): void
    {
        $this->activityHistorique->setMaxTime(self::$maxTime);
        $this->assertEquals(self::$maxTime, $this->activityHistorique->getMaxTime());
    }
    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->activityHistorique->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->activityHistorique->getDateEnregistrement());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new \App\Entity\ActivityHistorique());
        $this->assertEquals(10, count($reflectionClass->getProperties()));
    }
}
