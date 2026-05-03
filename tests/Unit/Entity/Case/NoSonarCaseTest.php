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

use App\Entity\NoSonar;
use PHPUnit\Framework\TestCase;

/**
 * [Description NoSonarCaseTest]
 */
class NoSonarCaseTest extends TestCase
{
    private $nosonar;

    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static string $rule = 'java:S1309';
    private static $component = 'fr.ma-petite-entreprise:mo-moulinette:
    ma-moulinette-service/src/main/java/fr/ma-petite-entreprise/ma-moulinette/service/ClamAvService.java';
    private static int $line = 118;
    private static string $modeCollecte = 'TRAITEMENT MANUEL';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static string $dateEnregistrement = '2024-03-26 14:46:38+01';

    private function getEntity(): NoSonar
    {
        return (new nosonar())
        ->setMavenKey(self::$mavenKey)
        ->setRule(self::$rule)
        ->setComponent(self::$component)
        ->setLine(self::$line)
        ->setModeCollecte(self::$modeCollecte)
        ->setUtilisateurCollecte(self::$utilisateurCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->nosonar = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->nosonar->setId(1);
        $this->assertEquals(1, $this->nosonar->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->nosonar->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->nosonar->getMavenKey());
    }

    public function testSettingAndGettingRule(): void
    {
        $this->nosonar->setRule(self::$rule);
        $this->assertEquals(self::$rule, $this->nosonar->getRule());
    }

    public function testSettingAndGettingComponent(): void
    {
        $this->nosonar->setComponent(self::$component);
        $this->assertEquals(self::$component, $this->nosonar->getComponent());
    }

    public function testSettingAndGettingLine(): void
    {
        $this->nosonar->setLine(self::$line);
        $this->assertEquals(self::$line, $this->nosonar->getLine());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->nosonar->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->nosonar->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->nosonar->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->nosonar->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->nosonar->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->nosonar->getDateEnregistrement());
    }

}
