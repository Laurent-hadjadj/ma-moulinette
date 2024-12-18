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
        $this->todo = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->todo->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->todo->getMavenKey());
    }

    public function testSettingAndGettingRule(): void
    {
        $this->todo->setRule(static::$rule);
        $this->assertEquals(static::$rule, $this->todo->getRule());
    }

    public function testSettingAndGettingComponent(): void
    {
        $this->todo->setComponent(static::$component);
        $this->assertEquals(static::$component, $this->todo->getComponent());
    }

    public function testSettingAndGettingLine(): void
    {
        $this->todo->setLine(static::$line);
        $this->assertEquals(static::$line, $this->todo->getLine());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->todo->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->todo->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->todo->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->todo->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->todo->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->todo->getDateEnregistrement());
    }

}
