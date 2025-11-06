<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Case;

use App\Entity\Groupe;
use PHPUnit\Framework\TestCase;

/**
 * [Description GroupeCaseTest]
 */
class GroupeCaseTest extends TestCase
{
    private $groupe;

    private static $titre = 'MA PETITE ENTREPRISE';
    private static $description = "Équipe de Développement de l'application Ma-Moulinette";
    private static $dateModification = '2024-03-26 14:46:38+02';
    private static $dateEnregistrement = '2024-03-25 12:26:58+02';

    private function getEntity(): Groupe
    {
        return (new groupe())
        ->setTitre(static::$titre)
        ->setDescription(static::$description)
        ->setDateModification(new \DateTime(static::$dateModification))
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->groupe = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->groupe->setId(1);
        $this->assertEquals(1, $this->groupe->getId());
    }

    public function testSettingAndGettingTitre(): void
    {
        $this->groupe->setTitre(static::$titre);
        $this->assertEquals(static::$titre, $this->groupe->getTitre());
    }

    public function testSettingAndGettingDescription(): void
    {
        $this->groupe->setDescription(static::$description);
        $this->assertEquals(static::$description, $this->groupe->getDescription());
    }

    public function testSettingAndGettingDateModification(): void
    {
        $newDate=new \DateTime(static::$dateModification);
        $this->groupe->setDateModification($newDate);
        $this->assertEquals($newDate, $this->groupe->getDateModification());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->groupe->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->groupe->getDateEnregistrement());
    }

}
