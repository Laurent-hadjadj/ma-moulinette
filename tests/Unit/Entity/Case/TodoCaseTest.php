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

use App\Entity\Todo;
use PHPUnit\Framework\TestCase;

/**
 * [Description TodoCaseTest]
 */
class TodoCaseTest extends TestCase
{
    private $todo;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $rule = 'java:S1135';
    private static $component = 'fr.ma-petite-entreprise:ma-moulinette:ma-moulinette/src/main/java/fr/ma-petite-entreprise/service/AnalyseTraceService.java';
    private static $line = 81;
    private static $modeCollecte = 'TRAITEMENT AUTOMATIQUE';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-03-26 14:46:38+02';

    private function getEntity(): Todo
    {
        return (new todo())
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
        $this->todo = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->todo->setId(1);
        $this->assertEquals(1, $this->todo->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->todo->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->todo->getMavenKey());
    }

    public function testSettingAndGettingRule(): void
    {
        $this->todo->setRule(self::$rule);
        $this->assertEquals(self::$rule, $this->todo->getRule());
    }

    public function testSettingAndGettingComponent(): void
    {
        $this->todo->setComponent(self::$component);
        $this->assertEquals(self::$component, $this->todo->getComponent());
    }

    public function testSettingAndGettingLine(): void
    {
        $this->todo->setLine(self::$line);
        $this->assertEquals(self::$line, $this->todo->getLine());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->todo->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->todo->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->todo->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->todo->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->todo->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->todo->getDateEnregistrement());
    }

}
