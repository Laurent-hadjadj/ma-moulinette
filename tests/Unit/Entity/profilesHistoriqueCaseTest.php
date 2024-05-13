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

namespace App\Tests\Unit\Entity;

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
    private static $regle = 'java:S5679';
    private static $description = 'OpenSAML2 should be configured to prevent authentication bypass';
    private static $detail = '{"severity":"MAJOR"}';
    private static $dateEnregistrement = '2024-04-12 16:23:11';

    private function getEntity(): ProfilesHistorique
    {
        return (new profilesHistorique())
        ->setDateCourte(new \DateTime(static::$dateCourte))
        ->setLanguage(static::$language)
        ->setDate(new \DateTime(static::$date))
        ->setAction(static::$action)
        ->setAuteur(static::$auteur)
        ->setRegle(static::$regle)
        ->setDescription(static::$description)
        ->setDetail(static::$detail)
        ->setDateEnregistrement(new \DateTime(static::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->profilesHistorique = $this->getEntity();
    }

    public function testSettingAndGettingDateCourte(): void
    {
        $newDate=new \DateTime(static::$dateCourte);
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
        $newDate=new \DateTime(static::$date);
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

    public function testSettingAndGettingRegle(): void
    {
        $this->profilesHistorique->setRegle(static::$regle);
        $this->assertEquals(static::$regle, $this->profilesHistorique->getRegle());
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
        $newDate=new \DateTime(static::$dateEnregistrement);
        $this->profilesHistorique->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->profilesHistorique->getDateEnregistrement());
    }

}
