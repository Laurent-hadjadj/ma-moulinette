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

use App\Entity\ProfilesHistorique;
use PHPUnit\Framework\TestCase;

/**
 * [Description ProfilesHistoriqueCaseTest]
 */
class ProfilesHistoriqueCaseTest extends TestCase
{
    private $profilesHistorique;

    private static $dateCourte = '2022-04-14';
    private static $language = 'java';
    private static $date  = '2022-08-30T18:42:41+0200';
    private static $action = 'ACTIVATED';
    private static $auteur = 'HADJADJ Laurent';
    private static $rule = 'java:S5679';
    private static $description = 'OpenSAML2 should be configured to prevent authentication bypass';
    private static $detail = '{"severity":"MAJOR"}';
    private static $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): ProfilesHistorique
    {
        return (new profilesHistorique())
        ->setDateCourte(new \DateTimeImmutable(static::$dateCourte))
        ->setLanguage(static::$language)
        ->setDate(new \DateTimeImmutable(static::$date))
        ->setAction(static::$action)
        ->setAuteur(static::$auteur)
        ->setRule(static::$rule)
        ->setDescription(static::$description)
        ->setDetail(static::$detail)
        ->setDateEnregistrement(new \DateTimeImmutable(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->profilesHistorique = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->profilesHistorique->setId(1);
        $this->assertEquals(1, $this->profilesHistorique->getId());
    }

    public function testSettingAndGettingDateCourte(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateCourte);
        $this->profilesHistorique->setDateCourte($newDate);
        $this->assertEquals($newDate, $this->profilesHistorique->getDateCourte());
    }

    public function testSettingAndGettingLanguage(): void
    {
        $this->profilesHistorique->setLanguage(static::$language);
        $this->assertEquals(static::$language, $this->profilesHistorique->getLanguage());
    }

    public function testSettingAndGettingDate(): void
    {
        $newDate=new \DateTimeImmutable(static::$date);
        $this->profilesHistorique->setDate($newDate);
        $this->assertEquals($newDate, $this->profilesHistorique->getDate());
    }

    public function testSettingAndGettingAction(): void
    {
        $this->profilesHistorique->setAction(static::$action);
        $this->assertEquals(static::$action, $this->profilesHistorique->getAction());
    }

    public function testSettingAndGettingAuteur(): void
    {
        $this->profilesHistorique->setAuteur(static::$auteur);
        $this->assertEquals(static::$auteur, $this->profilesHistorique->getAuteur());
    }

    public function testSettingAndGettingRule(): void
    {
        $this->profilesHistorique->setRule(static::$rule);
        $this->assertEquals(static::$rule, $this->profilesHistorique->getRule());
    }

    public function testSettingAndGettingDescription(): void
    {
        $this->profilesHistorique->setDescription(static::$description);
        $this->assertEquals(static::$description, $this->profilesHistorique->getDescription());
    }

    public function testSettingAndGettingDetail(): void
    {
        $this->profilesHistorique->setDetail(static::$detail);
        $this->assertEquals(static::$detail, $this->profilesHistorique->getDetail());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(static::$dateEnregistrement);
        $this->profilesHistorique->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->profilesHistorique->getDateEnregistrement());
    }

}
