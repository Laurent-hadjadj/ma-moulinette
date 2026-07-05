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

use App\Entity\OwaspTop10;
use PHPUnit\Framework\TestCase;

/**
 * [Description OwaspTop10CaseTest]
 */
class OwaspTop10CaseTest extends TestCase
{
    private OwaspTop10 $owaspTop10;

    private static int $year = 2017;
    private static string $category = "A1 - Attaques d'injection";
    private static string $description = "Les failles d'injection, telles que l'injection SQL, NoSQL, OS et LDAP, se produisent lorsque des données non fiables sont envoyées à un interpréteur dans le cadre d'une commande ou d'une requête. Les données hostiles de l'attaquant peuvent inciter l'interpréteur à exécuter des commandes non souhaitées ou à accéder à des données sans autorisation appropriée.";
    private static string $lien = '__a01-2017-injection.html.twig';
    private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

    private function getEntity(): OwaspTop10
    {
        return (new owaspTop10())
        ->setYear(self::$year)
        ->setCategory(self::$category)
        ->setDescription(self::$description)
        ->setLien(self::$lien)
        ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->owaspTop10 = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->owaspTop10->setId(1);
        $this->assertEquals(1, $this->owaspTop10->getId());
    }

    public function testSettingAndGettingYear(): void
    {
        $newYear = 2021;
        $this->owaspTop10->setYear($newYear);
        $this->assertEquals($newYear, $this->owaspTop10->getYear());
    }

    public function testSettingAndGettingCategory(): void
    {
        $newCategory = "A2 - Perte de données sensibles";
        $this->owaspTop10->setCategory($newCategory);
        $this->assertEquals($newCategory, $this->owaspTop10->getCategory());
    }

    public function testSettingAndGettingDescription(): void
    {
        $newDescription = "Une description modifiée pour tester les failles.";
        $this->owaspTop10->setDescription($newDescription);
        $this->assertEquals($newDescription, $this->owaspTop10->getDescription());
    }

    public function testSettingAndGettingLien(): void
    {
        $newLien = '__a02-2021-sensitive-data-exposure.html.twig';
        $this->owaspTop10->setLien($newLien);
        $this->assertEquals($newLien, $this->owaspTop10->getLien());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDateEnregistrement = new \DateTimeImmutable('2025-01-02 12:00:00+02');
        $this->owaspTop10->setDateEnregistrement($newDateEnregistrement);
        $this->assertEquals($newDateEnregistrement, $this->owaspTop10->getDateEnregistrement());
    }

    public function testInitialValues(): void
    {
        $this->assertEquals(self::$year, $this->owaspTop10->getYear());
        $this->assertEquals(self::$category, $this->owaspTop10->getCategory());
        $this->assertEquals(self::$description, $this->owaspTop10->getDescription());
        $this->assertEquals(self::$lien, $this->owaspTop10->getLien());
        $this->assertEquals(new \DateTimeImmutable(self::$dateEnregistrement), $this->owaspTop10->getDateEnregistrement());
    }
}
