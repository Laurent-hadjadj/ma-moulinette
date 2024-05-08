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

namespace App\Tests\Unit\Entity\Main;

use App\Entity\Main\ListeProjet;
use PHPUnit\Framework\TestCase;

/**
 * [Description ListeProjetCaseTest]
 */
class ListeProjetCaseTest extends TestCase
{
    private $listeprojet;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $name = 'Ma-Moulinette';
    private static $tags = ['ma-moulinette', '2048'];
    private static $visibility = 'private';
    private static $dateEnregistrement = '2024-04-12 16:23:11';

    private function getEntity(): ListeProjet
    {
        return (new listeprojet())
        ->setMavenKey(static::$mavenKey)
        ->setName(static::$name)
        ->setTags(static::$tags)
        ->setVisibility(static::$visibility)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
  }

    protected function setUp(): void
    {
        parent::setUp();
        $this->listeprojet = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->listeprojet->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->listeprojet->getMavenKey());
    }

    public function testSettingAndGettingName(): void
    {
        $this->listeprojet->setName(static::$name);
        $this->assertEquals(static::$name, $this->listeprojet->getName());
    }

    public function testSettingAndGettingTags(): void
    {
        $this->listeprojet->setTags(static::$tags);
        $this->assertEquals(static::$tags, $this->listeprojet->getTags());
    }

    public function testSettingAndGettingVisibility(): void
    {
        $this->listeprojet->setVisibility(static::$visibility);
        $this->assertEquals(static::$visibility, $this->listeprojet->getVisibility());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTime(static::$dateEnregistrement);
        $this->listeprojet->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->listeprojet->getDateEnregistrement());
    }

}
