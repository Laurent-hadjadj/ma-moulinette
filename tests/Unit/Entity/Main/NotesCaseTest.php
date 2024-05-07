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

class NotesCaseTest extends TestCase
{
    private $notes;

    private static $mavenKey = 'fr.ma-petite-entreprise:ma-moulinette';
    private static $type = 'reliability';
    private static $value = 3;
    private static $dateEnregistrement = '2024-03-26 14:46:38';

    /**
     * [Description for getEntity]
     *
     * @return Notes
     *
     * Created at: 06/05/2024 17:46:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getEntity(): Notes
    {
        return (new notes())
        ->setMavenKey(static::$mavenKey)
        ->setType(static::$type)
        ->setValue(static::$value)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
    }

    /**
     * [Description for setUp]
     *
     * @return void
     *
     * Created at: 06/05/2024 17:46:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->notes = $this->getEntity();
    }

    /**
     * [Description for testMavenKey]
     *
     * @return void
     *
     * Created at: 06/05/2024 17:46:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testMavenKey(): void
    {
        $this->notes->setMavenKey(static::$mavenKey);
        $this->assertEquals(static::$mavenKey, $this->notes->getMavenKey());
    }

    /**
     * [Description for testType]
     *
     * @return void
     *
     * Created at: 06/05/2024 17:46:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testType(): void
    {
        $this->notes->setType(static::$type);
        $this->assertEquals(static::$type, $this->notes->getType());
    }

    /**
     * [Description for testValue]
     *
     * @return void
     *
     * Created at: 06/05/2024 17:46:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testValue(): void
    {
        $this->notes->setValue(static::$value);
        $this->assertEquals(static::$value, $this->notes->getValue());
    }

    /**
     * [Description for testDateEnregistrement]
     *
     * @return void
     *
     * Created at: 06/05/2024 17:46:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function testDateEnregistrement(): void
    {
        $this->notes->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
        $this->assertEquals(new \DateTime(static::$dateEnregistrement), $this->notes->getDateEnregistrement());
    }
}
