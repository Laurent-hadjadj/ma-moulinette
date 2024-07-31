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

use App\Entity\Activite;
use PHPUnit\Framework\TestCase;

/**
 * [Description ActiviteCaseTest]
 */
class ActiviteCaseTest extends TestCase
{
    private $activite;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $projectName = 'ma-moulinette';
    private static $analyseId = 'vtrf14lkiutq9mp';
    private static $status = 'SUCCESS';
    private static $submitterLogin = 'laurent.hadjadj';
    private static $submittedAt = '2024-07-31 12:26:58+02';
    private static $startedAt = '2024-07-31 12:27:05+02';
    private static $executedAt = '2024-07-31 12:27:47+02';
    private static $executionTime = "42";

    private function getEntity(): Activite
    {
        return (new activite())
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
        $this->activite = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->activite->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->activite->getMavenKey());
    }

    public function testSettingAndGettingProjectName(): void
    {
        $this->activite->setProjectName(static::$projectName);
        $this->assertEquals(static::$projectName, $this->activite->getProjectName());
    }

    public function testSettingAndGettingAnalyseId(): void
    {
        $this->activite->setAnalyseId(static::$analyseId);
        $this->assertEquals(static::$analyseId, $this->activite->getAnalyseId());
    }

    public function testSettingAndGettingStatus(): void
    {
        $this->activite->setStatus(static::$status);
        $this->assertEquals(static::$status, $this->activite->getStatus());
    }

    public function testSettingAndGettingSubmitterLogin(): void
    {
        $this->activite->setSubmitterLogin(static::$submitterLogin);
        $this->assertEquals(static::$submitterLogin, $this->activite->getSubmitterLogin());
    }

    public function testSettingAndGettingSubmittedAt(): void
    {
        $newDate=new \DateTimeImmutable(static::$submittedAt);
        $this->activite->setSubmittedAt($newDate);
        $this->assertEquals($newDate, $this->activite->getSubmittedAt());
    }

    public function testSettingAndGettingStartedAt(): void
    {
        $newDate=new \DateTimeImmutable(static::$startedAt);
        $this->activite->setStartedAt($newDate);
        $this->assertEquals($newDate, $this->activite->getStartedAt());
    }

    public function testSettingAndGettingExecutedAt(): void
    {
        $newDate=new \DateTimeImmutable(static::$executedAt);
        $this->activite->setExecutedAt($newDate);
        $this->assertEquals($newDate, $this->activite->getExecutedAt());
    }

    public function testSettingAndGettingExecutionTime(): void
    {
        $this->activite->setExecutionTime(static::$executionTime);
        $this->assertEquals(static::$executionTime, $this->activite->getExecutionTime());
    }

}
