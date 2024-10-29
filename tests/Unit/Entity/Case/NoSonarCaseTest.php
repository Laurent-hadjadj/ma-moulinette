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

namespace App\Tests\Unit\Entity\Case;

use App\Entity\NoSonar;
use PHPUnit\Framework\TestCase;

/**
 * [Description NoSonarCaseTest]
 */
class NoSonarCaseTest extends TestCase
{
    private $nosonar;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $rule = 'java:S1309';
    private static $component = 'fr.ma-petite-entreprise:mo-moulinette:
    ma-moulinette-service/src/main/java/fr/ma-petite-entreprise/ma-moulinette/service/ClamAvService.java';
    private static $line = 118;
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-03-26 14:46:38+01';

    private function getEntity(): NoSonar
    {
        return (new nosonar())
        ->setMavenKey(static::$mavenKey)
        ->setRule(static::$rule)
        ->setComponent(static::$component)
        ->setLine(static::$line)
        ->setModeCollecte(static::$modeCollecte)
        ->setUtilisateurCollecte(static::$utilisateurCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->nosonar = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->nosonar->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->nosonar->getMavenKey());
    }

    public function testSettingAndGettingRule(): void
    {
        $this->nosonar->setRule(static::$rule);
        $this->assertEquals(static::$rule, $this->nosonar->getRule());
    }

    public function testSettingAndGettingComponent(): void
    {
        $this->nosonar->setComponent(static::$component);
        $this->assertEquals(static::$component, $this->nosonar->getComponent());
    }

    public function testSettingAndGettingLine(): void
    {
        $this->nosonar->setLine(static::$line);
        $this->assertEquals(static::$line, $this->nosonar->getLine());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->nosonar->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->nosonar->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->nosonar->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->nosonar->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->nosonar->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->nosonar->getDateEnregistrement());
    }

}
