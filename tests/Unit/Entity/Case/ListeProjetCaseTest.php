<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Case;

use App\Entity\ListeProjet;
use PHPUnit\Framework\TestCase;

/**
 * [Description ListeProjetCaseTest]
 */
class ListeProjetCaseTest extends TestCase
{
    private ListeProjet $listeProjet;

    private static string $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static string $name = 'Ma-Moulinette';
    private static $tags = ['ma-moulinette', '2048'];
    private static string $visibility = 'private';
    private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): ListeProjet
    {
        return (new ListeProjet(
            self::$mavenKey,
            self::$name,
            self::$visibility,
            self::$tags))
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->listeProjet = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->listeProjet->setId(1);
        $this->assertEquals(1, $this->listeProjet->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->listeProjet->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->listeProjet->getMavenKey());
    }

    public function testSettingAndGettingName(): void
    {
        $this->listeProjet->setName(self::$name);
        $this->assertEquals(self::$name, $this->listeProjet->getName());
    }

    public function testSettingAndGettingTags(): void
    {
        $this->listeProjet->setTags(self::$tags);
        $this->assertEquals(self::$tags, $this->listeProjet->getTags());
    }

    public function testSettingAndGettingVisibility(): void
    {
        $this->listeProjet->setVisibility(self::$visibility);
        $this->assertEquals(self::$visibility, $this->listeProjet->getVisibility());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->listeProjet->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->listeProjet->getDateEnregistrement());
    }

}
