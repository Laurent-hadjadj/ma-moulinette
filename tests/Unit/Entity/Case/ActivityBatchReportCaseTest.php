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

use App\Entity\ActivityBatchReport;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActivityBatchReportCaseTest]
 */
class ActivityBatchReportCaseTest extends TestCase
{
    private $activityBatchReport;

    private static string $dateStart = '2025-01-01 12:26:58+01';
    private static string $dateEnd = '2025-01-01 12:27:12+01';
    private static int $taskCount = 12;
    private static int $taskDone = 11;
    private static int $page = 1;
    private static $lastError = ['Erreur inconnue.'];
    private static string $dateEnregistrement = '2024-07-31 12:27:05+02';

    private function getEntity(): ActivityBatchReport
    {
        return (new activityBatchReport())
        ->setDateStart(new \DateTimeImmutable(self::$dateStart))
        ->setDateEnd(new \DateTimeImmutable(self::$dateEnd))
        ->setTaskCount(self::$taskCount)
        ->setTaskDone(self::$taskDone)
        ->setPage(self::$page)
        ->setLastError(self::$lastError)
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
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
        $newDate=new \DateTimeImmutable(self::$dateStart);
        $this->activityBatchReport->setDateStart($newDate);
        $this->assertEquals($newDate, $this->activityBatchReport->getDateStart());
    }

    public function testSettingAndGettingDateEnd(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnd);
        $this->activityBatchReport->setDateEnd($newDate);
        $this->assertEquals($newDate, $this->activityBatchReport->getDateEnd());
    }

    public function testSettingAndGettingTaskCount(): void
    {
        $this->activityBatchReport->setTaskCount(self::$taskCount);
        $this->assertEquals(self::$taskCount, $this->activityBatchReport->getTaskCount());
    }

    public function testSettingAndGettingTaskDone(): void
    {
        $this->activityBatchReport->setTaskDone(self::$taskDone);
        $this->assertEquals(self::$taskDone, $this->activityBatchReport->getTaskDone());
    }

    public function testSettingAndGettingPage(): void
    {
        $this->activityBatchReport->setPage(self::$page);
        $this->assertEquals(self::$page, $this->activityBatchReport->getPage());
    }

    public function testSettingAndGettingLastError(): void
    {
        $this->activityBatchReport->setLastError(self::$lastError);
        $this->assertEquals(self::$lastError, $this->activityBatchReport->getLastError());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->activityBatchReport->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->activityBatchReport->getDateEnregistrement());
    }

}
