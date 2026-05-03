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

use App\Entity\MaMoulinette;
use PHPUnit\Framework\TestCase;

/**
 * [Description MaMoulinetteCaseTest]
 */
class MaMoulinetteCaseTest extends TestCase
{
    private $maMoulinette;

    private static $version = '2.0.0';
    private static $dateVersion = '2024-04-12 16:23:11+01';
    private static $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): MaMoulinette
    {
        return (new MaMoulinette(self::$version))
        ->setDateVersion(new \DateTimeImmutable(self::$dateVersion))
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->maMoulinette = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->maMoulinette->setId(1);
        $this->assertEquals(1, $this->maMoulinette->getId());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->maMoulinette->setVersion(self::$version);
        $this->assertEquals(self::$version, $this->maMoulinette->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $newDateVersion=new \DateTimeImmutable(self::$dateVersion);
        $this->maMoulinette->setDateVersion($newDateVersion);
        $this->assertEquals($newDateVersion, $this->maMoulinette->getDateVersion());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->maMoulinette->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->maMoulinette->getDateEnregistrement());
    }

}
