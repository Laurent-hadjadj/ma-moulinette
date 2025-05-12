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

use App\Entity\ActivityBatchReport;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActivityBatchReportCaseTest]
 */
class ActivityBatchReportCaseTest extends TestCase
{
    private $activityBatchReport;

    private static $dateStart = '2025-01-01 12:26:58+01';
    private static $dateEnd = '2025-01-01 12:27:12+01';
    private static $taskCount = 12;
    private static $taskDone = 11;
    private static $page = 1;
    private static $lastError = ['Erreur inconnue.'];
    private static $dateEnregistrement = '2024-07-31 12:27:05+02';

    private function getEntity(): ActivityBatchReport
    {
        return (new activityBatchReport())
        ->setDateStart(new \DateTimeImmutable(static::$dateStart))
        ->setDateEnd(new \DateTimeImmutable(static::$dateEnd))
        ->setTaskCount(static::$taskCount)
        ->setTaskDone(static::$taskDone)
        ->setPage(static::$page)
        ->setLastError(static::$lastError)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->activityBatchReport = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->activityBatchReport->setId(1);
        $this->assertEquals(1, $this->activityBatchReport->getId());
    }

    public function testSettingAndGettingDateStart(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateStart);
        $this->activityBatchReport->setDateStart($newDate);
        $this->assertEquals($newDate, $this->activityBatchReport->getDateStart());
    }

    public function testSettingAndGettingDateEnd(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnd);
        $this->activityBatchReport->setDateEnd($newDate);
        $this->assertEquals($newDate, $this->activityBatchReport->getDateEnd());
    }

    public function testSettingAndGettingTaskCount(): void
    {
        $this->activityBatchReport->setTaskCount(static::$taskCount);
        $this->assertEquals(static::$taskCount, $this->activityBatchReport->getTaskCount());
    }

    public function testSettingAndGettingTaskDone(): void
    {
        $this->activityBatchReport->setTaskDone(static::$taskDone);
        $this->assertEquals(static::$taskDone, $this->activityBatchReport->getTaskDone());
    }

    public function testSettingAndGettingPage(): void
    {
        $this->activityBatchReport->setPage(static::$page);
        $this->assertEquals(static::$page, $this->activityBatchReport->getPage());
    }

    public function testSettingAndGettingLastError(): void
    {
        $this->activityBatchReport->setLastError(static::$lastError);
        $this->assertEquals(static::$lastError, $this->activityBatchReport->getLastError());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->activityBatchReport->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->activityBatchReport->getDateEnregistrement());
    }

}
