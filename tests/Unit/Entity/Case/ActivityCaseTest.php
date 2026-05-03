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

use App\Entity\Activity;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActivityCaseTest]
 */
class ActivityCaseTest extends TestCase
{
    private $activity;

    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static string $projectName = 'ma-moulinette';
    private static string $analyseId = 'vtrf14lkiutq9mp';
    private static string $status = 'SUCCESS';
    private static string $submitterLogin = 'laurent.hadjadj';
    private static string $submittedAt = '2024-07-31 12:26:58+02';
    private static string $startedAt = '2024-07-31 12:27:05+02';
    private static string $executedAt = '2024-07-31 12:27:47+02';
    private static int $executionTime = 42;

    private function getEntity(): Activity
    {
        return (new activity())
        ->setMavenKey(self::$mavenKey)
        ->setProjectName(self::$projectName)
        ->setAnalyseId(self::$analyseId)
        ->setStatus(self::$status)
        ->setSubmitterLogin(self::$submitterLogin)
        ->setSubmittedAt(new \DateTimeImmutable(self::$submittedAt))
        ->setStartedAt(new \DateTimeImmutable(self::$startedAt))
        ->setExecutedAt(new \DateTimeImmutable(self::$executedAt))
        ->setExecutionTime(self::$executionTime);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->activity = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->activity->setId(1);
        $this->assertEquals(1, $this->activity->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->activity->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->activity->getMavenKey());
    }

    public function testSettingAndGettingProjectName(): void
    {
        $this->activity->setProjectName(self::$projectName);
        $this->assertEquals(self::$projectName, $this->activity->getProjectName());
    }

    public function testSettingAndGettingAnalyseId(): void
    {
        $this->activity->setAnalyseId(self::$analyseId);
        $this->assertEquals(self::$analyseId, $this->activity->getAnalyseId());
    }

    public function testSettingAndGettingStatus(): void
    {
        $this->activity->setStatus(self::$status);
        $this->assertEquals(self::$status, $this->activity->getStatus());
    }

    public function testSettingAndGettingSubmitterLogin(): void
    {
        $this->activity->setSubmitterLogin(self::$submitterLogin);
        $this->assertEquals(self::$submitterLogin, $this->activity->getSubmitterLogin());
    }

    public function testSettingAndGettingSubmittedAt(): void
    {
        $newDate=new \DateTimeImmutable(self::$submittedAt);
        $this->activity->setSubmittedAt($newDate);
        $this->assertEquals($newDate, $this->activity->getSubmittedAt());
    }

    public function testSettingAndGettingStartedAt(): void
    {
        $newDate=new \DateTimeImmutable(self::$startedAt);
        $this->activity->setStartedAt($newDate);
        $this->assertEquals($newDate, $this->activity->getStartedAt());
    }

    public function testSettingAndGettingExecutedAt(): void
    {
        $newDate=new \DateTimeImmutable(self::$executedAt);
        $this->activity->setExecutedAt($newDate);
        $this->assertEquals($newDate, $this->activity->getExecutedAt());
    }

    public function testSettingAndGettingExecutionTime(): void
    {
        $this->activity->setExecutionTime(self::$executionTime);
        $this->assertEquals(self::$executionTime, $this->activity->getExecutionTime());
    }

}
