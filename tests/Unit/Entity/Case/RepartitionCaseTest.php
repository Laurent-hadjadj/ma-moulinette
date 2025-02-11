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

use App\Entity\Repartition;
use PHPUnit\Framework\TestCase;

/**
 * [Description RepartitionCaseTest]
 */
class RepartitionCaseTest extends TestCase
{
    private $repartition;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $name = 'ma-moulinette';
    private static $component = '/controller/auth/reset-password.php';
    private static $type = 'bug';
    private static $severity = 'medium';
    private static $setup = '1707664293645';
    private static $modeCollecte = 'TRAITEMENT MANUEL';
    private static $utilisateurCollecte = 'laurent.hadjadj@ma-petite-entreprise.fr';
    private static $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): Repartition
    {
        return (new repartition())
        ->setMavenKey(static::$mavenKey)
        ->setName(static::$name)
        ->setComponent(static::$component)
        ->setType(static::$type)
        ->setSeverity(static::$severity)
        ->setSetup(static::$setup)
        ->setModeCollecte(static::$modeCollecte)
        ->setUtilisateurCollecte(static::$utilisateurCollecte)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->repartition = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $newMavenKey = 'com.example:example-project';
        $this->repartition->setMavenKey($newMavenKey);
        $this->assertEquals($newMavenKey, $this->repartition->getMavenKey());
    }

    public function testSettingAndGettingName(): void
    {
        $newName = 'Updated Name';
        $this->repartition->setName($newName);
        $this->assertEquals($newName, $this->repartition->getName());
    }

    public function testSettingAndGettingComponent(): void
    {
        $newComponent = '/service/api';
        $this->repartition->setComponent($newComponent);
        $this->assertEquals($newComponent, $this->repartition->getComponent());
    }

    public function testSettingAndGettingType(): void
    {
        $newType = 'feature';
        $this->repartition->setType($newType);
        $this->assertEquals($newType, $this->repartition->getType());
    }

    public function testSettingAndGettingSeverity(): void
    {
        $newSeverity = 'critical';
        $this->repartition->setSeverity($newSeverity);
        $this->assertEquals($newSeverity, $this->repartition->getSeverity());
    }

    public function testSettingAndGettingSetup(): void
    {
        $newSetup = '1707664293645';
        $this->repartition->setSetup($newSetup);
        $this->assertEquals($newSetup, $this->repartition->getSetup());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->repartition->setModeCollecte(static::$modeCollecte);
        $this->assertEquals(static::$modeCollecte, $this->repartition->getModeCollecte());
    }
    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->repartition->setUtilisateurCollecte(static::$utilisateurCollecte);
        $this->assertEquals(static::$utilisateurCollecte, $this->repartition->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate = new \DateTimeImmutable('2025-01-01 12:00:00+01');
        $this->repartition->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->repartition->getDateEnregistrement());
    }

    public function testInitialValues(): void
    {
        $this->assertEquals(static::$mavenKey, $this->repartition->getMavenKey());
        $this->assertEquals(static::$name, $this->repartition->getName());
        $this->assertEquals(static::$component, $this->repartition->getComponent());
        $this->assertEquals(static::$type, $this->repartition->getType());
        $this->assertEquals(static::$severity, $this->repartition->getSeverity());
        $this->assertEquals(static::$setup, $this->repartition->getSetup());
        $this->assertEquals(static::$modeCollecte, $this->repartition->getModeCollecte());
        $this->assertEquals(static::$utilisateurCollecte, $this->repartition->getUtilisateurCollecte());
        $this->assertEquals(new \DateTimeImmutable(static::$dateEnregistrement), $this->repartition->getDateEnregistrement());
    }

}
