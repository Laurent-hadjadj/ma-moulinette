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
        return (new logger(
            self::$mavenKey,
            self::$loggerInfo,
            self::$loggerWarn,
            self::$loggerError,
            self::$loggerDebug,
            self::$modeCollecte,
            self::$utilisateurCollecte))
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->logger = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->logger->setId(1);
        $this->assertEquals(1, $this->logger->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->logger->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->logger->getMavenKey());
    }

    public function testSettingAndGettingLoggerInfo(): void
    {
        $this->logger->setLoggerInfo(self::$loggerInfo);
        $this->assertEquals(self::$loggerInfo, $this->logger->getLoggerInfo());
    }

    public function testSettingAndGettingLoggerWarn(): void
    {
        $this->logger->setLoggerWarn(self::$loggerWarn);
        $this->assertEquals(self::$loggerWarn, $this->logger->getLoggerWarn());
    }

    public function testSettingAndGettingLoggerError(): void
    {
        $this->logger->setLoggerError(self::$loggerError);
        $this->assertEquals(self::$loggerError, $this->logger->getLoggerError());
    }

    public function testSettingAndGettingLoggerDebug(): void
    {
        $this->logger->setLoggerDebug(self::$loggerDebug);
        $this->assertEquals(self::$loggerDebug, $this->logger->getLoggerDebug());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->logger->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->logger->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->logger->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->logger->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->logger->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->logger->getDateEnregistrement());
    }

}
