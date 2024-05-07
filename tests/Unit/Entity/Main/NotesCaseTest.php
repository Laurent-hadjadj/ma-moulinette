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

use App\Entity\Main\Notes;
use PHPUnit\Framework\TestCase;

/**
 * [Description NotesCaseTest]
 */
class NotesCaseTest extends TestCase
{
    private $notes;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $type = 'reliability';
    private static $value = 3;
    private static $dateEnregistrement = '2024-03-26 14:46:38';

    private function getEntity(): Notes
    {
        return (new notes())
        ->setMavenKey(static::$mavenKey)
        ->setType(static::$type)
        ->setValue(static::$value)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->notes = $this->getEntity();
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->notes->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->notes->getMavenKey());
    }

    public function testSettingAndGettingType(): void
    {
        $this->notes->setType(static::$type);
        $this->assertEquals(static::$type, $this->notes->getType());
    }

    public function testSettingAndGettingValue(): void
    {
        $this->notes->setValue(static::$value);
        $this->assertEquals(static::$value, $this->notes->getValue());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTime(static::$dateEnregistrement);
        $this->notes->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->notes->getDateEnregistrement());
    }
}
