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

use App\Entity\ProfilesHistorique;
use PHPUnit\Framework\TestCase;

/**
 * [Description ProfilesHistoriqueCaseTest]
 */
class ProfilesHistoriqueCaseTest extends TestCase
{
    private $profilesHistorique;

    private static string $dateCourte = '2022-04-14';
    private static string $language = 'java';
    private static string $date = '2022-08-30T18:42:41+0200';
    private static string $action = 'ACTIVATED';
    private static string $auteur = 'HADJADJ Laurent';
    private static string $rule = 'java:S5679';
    private static string $description = 'OpenSAML2 should be configured to prevent authentication bypass';
    private static string $detail = '{"severity":"MAJOR"}';
    private static string $dateEnregistrement = '2024-04-12 16:23:11+01';

    private function getEntity(): ProfilesHistorique
    {
        return (new profilesHistorique())
        ->setDateCourte(new \DateTimeImmutable(self::$dateCourte))
        ->setLanguage(self::$language)
        ->setDate(new \DateTimeImmutable(self::$date))
        ->setAction(self::$action)
        ->setAuteur(self::$auteur)
        ->setRule(self::$rule)
        ->setDescription(self::$description)
        ->setDetail(self::$detail)
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
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
        $newDate=new \DateTimeImmutable(self::$dateCourte);
        $this->profilesHistorique->setDateCourte($newDate);
        $this->assertEquals($newDate, $this->profilesHistorique->getDateCourte());
    }

    public function testSettingAndGettingLanguage(): void
    {
        $this->profilesHistorique->setLanguage(self::$language);
        $this->assertEquals(self::$language, $this->profilesHistorique->getLanguage());
    }

    public function testSettingAndGettingDate(): void
    {
        $newDate=new \DateTimeImmutable(self::$date);
        $this->profilesHistorique->setDate($newDate);
        $this->assertEquals($newDate, $this->profilesHistorique->getDate());
    }

    public function testSettingAndGettingAction(): void
    {
        $this->profilesHistorique->setAction(self::$action);
        $this->assertEquals(self::$action, $this->profilesHistorique->getAction());
    }

    public function testSettingAndGettingAuteur(): void
    {
        $this->profilesHistorique->setAuteur(self::$auteur);
        $this->assertEquals(self::$auteur, $this->profilesHistorique->getAuteur());
    }

    public function testSettingAndGettingRule(): void
    {
        $this->profilesHistorique->setRule(self::$rule);
        $this->assertEquals(self::$rule, $this->profilesHistorique->getRule());
    }

    public function testSettingAndGettingDescription(): void
    {
        $this->profilesHistorique->setDescription(self::$description);
        $this->assertEquals(self::$description, $this->profilesHistorique->getDescription());
    }

    public function testSettingAndGettingDetail(): void
    {
        $this->profilesHistorique->setDetail(self::$detail);
        $this->assertEquals(self::$detail, $this->profilesHistorique->getDetail());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate=new \DateTimeImmutable(self::$dateEnregistrement);
        $this->profilesHistorique->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->profilesHistorique->getDateEnregistrement());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new \App\Entity\ProfilesHistorique());
        $this->assertEquals(10, count($reflectionClass->getProperties()));
    }
}
