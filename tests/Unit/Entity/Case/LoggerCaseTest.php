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

use App\Entity\Logger;
use PHPUnit\Framework\TestCase;

/**
 * [Description LoggerCaseTest]
 */
class LoggerCaseTest extends TestCase
{
    private $logger;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $loggerInfo = 14;
    private static $loggerWarn = 0;
    private static $loggerError = 15;
    private static $loggerDebug = 8;
    private static $modeCollecte = 'TRAITEMENT AUTOMATIQUE';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-03-26 14:46:38+02';

    private function getEntity(): Logger
    {
        return (new logger())
        ->setMavenKey(static::$mavenKey)
        ->setLoggerInfo(static::$loggerInfo)
        ->setLoggerWarn(static::$loggerWarn)
        ->setLoggerError(static::$loggerError)
        ->setLoggerDebug(static::$loggerDebug)
        ->setModeCollecte(static::$modeCollecte)
        ->setUtilisateurCollecte(static::$utilisateurCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->logger->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->logger->getMavenKey());
    }

    public function testSettingAndGettingLoggerInfo(): void
    {
        $this->logger->setLoggerInfo(static::$loggerInfo);
        $this->assertEquals(static::$loggerInfo, $this->logger->getLoggerInfo());
    }

    public function testSettingAndGettingLoggerWarn(): void
    {
        $this->logger->setLoggerWarn(static::$loggerWarn);
        $this->assertEquals(static::$loggerWarn, $this->logger->getLoggerWarn());
    }

    public function testSettingAndGettingLoggerError(): void
    {
        $this->logger->setLoggerError(static::$loggerError);
        $this->assertEquals(static::$loggerError, $this->logger->getLoggerError());
    }

    public function testSettingAndGettingLoggerDebug(): void
    {
        $this->logger->setLoggerDebug(static::$loggerDebug);
        $this->assertEquals(static::$loggerDebug, $this->logger->getLoggerDebug());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->logger->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->logger->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->logger->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->logger->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->logger->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->logger->getDateEnregistrement());
    }

}
