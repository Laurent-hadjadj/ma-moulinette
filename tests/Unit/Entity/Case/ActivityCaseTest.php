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

use App\Entity\Activity;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActivityCaseTest]
 */
class ActivityCaseTest extends TestCase
{
    private $activity;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $projectName = 'ma-moulinette';
    private static $analyseId = 'vtrf14lkiutq9mp';
    private static $status = 'SUCCESS';
    private static $submitterLogin = 'laurent.hadjadj';
    private static $submittedAt = '2024-07-31 12:26:58+02';
    private static $startedAt = '2024-07-31 12:27:05+02';
    private static $executedAt = '2024-07-31 12:27:47+02';
    private static $executionTime = 42;

    private function getEntity(): Activity
    {
        return (new activity())
        ->setMavenKey(static::$mavenKey)
        ->setProjectName(static::$projectName)
        ->setAnalyseId(static::$analyseId)
        ->setStatus(static::$status)
        ->setSubmitterLogin(static::$submitterLogin)
        ->setSubmittedAt(new \DateTimeImmutable(static::$submittedAt))
        ->setStartedAt(new \DateTimeImmutable(static::$startedAt))
        ->setExecutedAt(new \DateTimeImmutable(static::$executedAt))
        ->setExecutionTime(static::$executionTime);
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->activity = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->activity->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->activity->getMavenKey());
    }

    public function testSettingAndGettingProjectName(): void
    {
        $this->activity->setProjectName(static::$projectName);
        $this->assertEquals(static::$projectName, $this->activity->getProjectName());
    }

    public function testSettingAndGettingAnalyseId(): void
    {
        $this->activity->setAnalyseId(static::$analyseId);
        $this->assertEquals(static::$analyseId, $this->activity->getAnalyseId());
    }

    public function testSettingAndGettingStatus(): void
    {
        $this->activity->setStatus(static::$status);
        $this->assertEquals(static::$status, $this->activity->getStatus());
    }

    public function testSettingAndGettingSubmitterLogin(): void
    {
        $this->activity->setSubmitterLogin(static::$submitterLogin);
        $this->assertEquals(static::$submitterLogin, $this->activity->getSubmitterLogin());
    }

    public function testSettingAndGettingSubmittedAt(): void
    {
        $newDate=new \DateTimeImmutable(static::$submittedAt);
        $this->activity->setSubmittedAt($newDate);
        $this->assertEquals($newDate, $this->activity->getSubmittedAt());
    }

    public function testSettingAndGettingStartedAt(): void
    {
        $newDate=new \DateTimeImmutable(static::$startedAt);
        $this->activity->setStartedAt($newDate);
        $this->assertEquals($newDate, $this->activity->getStartedAt());
    }

    public function testSettingAndGettingExecutedAt(): void
    {
        $newDate=new \DateTimeImmutable(static::$executedAt);
        $this->activity->setExecutedAt($newDate);
        $this->assertEquals($newDate, $this->activity->getExecutedAt());
    }

    public function testSettingAndGettingExecutionTime(): void
    {
        $this->activity->setExecutionTime(static::$executionTime);
        $this->assertEquals(static::$executionTime, $this->activity->getExecutionTime());
    }

}
