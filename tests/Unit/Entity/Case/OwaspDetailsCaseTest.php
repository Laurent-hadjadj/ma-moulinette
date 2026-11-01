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

declare(strict_types=1);

namespace App\Tests\Unit\Entity\Case;

use App\Entity\OwaspDetails;
use PHPUnit\Framework\TestCase;

/**
 * [Description OwaspDetailsCaseTest]
 *
 * Created at: 28/10/2026 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
class OwaspDetailsCaseTest extends TestCase
{
    private OwaspDetails $owaspDetails;

    private static string $mavenKey = 'fr.ma-moulinette:ma-moulinette';
    private static int $referentialOwasp = 2021;
    private static string $version = '1.2.0-RELEASE';
    private static string $dateVersion = '2024-07-10 15:26:07+02';
    private static string $ruleKey = 'java:S2077';
    private static string $categories = 'A1, A3';
    private static string $severity = 'CRITICAL';
    private static string $source = 'core';
    private static string $module = 'frontend';
    private static string $filePath = 'ma-moulinette/src/main/java/Foo.java';
    private static int $line = 60;
    private static string $message = 'Make sure using a dynamically formatted SQL query is safe here.';
    private static string $modeCollecte = 'TRAITEMENT AUTOMATIQUE';
    private static string $utilisateurCollecte = 'laurent.hadjadj@ma-moulinette.fr';
    private static string $dateEnregistrement = '2024-03-26 14:46:38+02';

    private function getEntity(): OwaspDetails
    {
        return (new OwaspDetails())
            ->setMavenKey(self::$mavenKey)
            ->setReferentialOwasp(self::$referentialOwasp)
            ->setVersion(self::$version)
            ->setDateVersion(new \DateTimeImmutable(self::$dateVersion))
            ->setRuleKey(self::$ruleKey)
            ->setCategories(self::$categories)
            ->setSeverity(self::$severity)
            ->setSource(self::$source)
            ->setModule(self::$module)
            ->setFilePath(self::$filePath)
            ->setLine(self::$line)
            ->setMessage(self::$message)
            ->setModeCollecte(self::$modeCollecte)
            ->setUtilisateurCollecte(self::$utilisateurCollecte)
            ->setDateEnregistrement(new \DateTimeImmutable(self::$dateEnregistrement));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->owaspDetails = $this->getEntity();
    }

    public function testSettingAndGettingId(): void
    {
        $this->owaspDetails->setId(1);
        $this->assertEquals(1, $this->owaspDetails->getId());
    }

    public function testSettingAndGettingMavenKey(): void
    {
        $this->owaspDetails->setMavenKey(self::$mavenKey);
        $this->assertEquals(self::$mavenKey, $this->owaspDetails->getMavenKey());
    }

    public function testSettingAndGettingReferentialOwasp(): void
    {
        $this->owaspDetails->setReferentialOwasp(2017);
        $this->assertEquals(2017, $this->owaspDetails->getReferentialOwasp());
    }

    public function testSettingAndGettingVersion(): void
    {
        $this->owaspDetails->setVersion(self::$version);
        $this->assertEquals(self::$version, $this->owaspDetails->getVersion());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $newDate = new \DateTimeImmutable(self::$dateVersion);
        $this->owaspDetails->setDateVersion($newDate);
        $this->assertEquals($newDate, $this->owaspDetails->getDateVersion());
    }

    public function testSettingAndGettingRuleKey(): void
    {
        $this->owaspDetails->setRuleKey(self::$ruleKey);
        $this->assertEquals(self::$ruleKey, $this->owaspDetails->getRuleKey());
    }

    public function testSettingAndGettingCategories(): void
    {
        $this->owaspDetails->setCategories(self::$categories);
        $this->assertEquals(self::$categories, $this->owaspDetails->getCategories());
    }

    public function testSettingAndGettingSeverity(): void
    {
        $this->owaspDetails->setSeverity(self::$severity);
        $this->assertEquals(self::$severity, $this->owaspDetails->getSeverity());
    }

    public function testSettingAndGettingSource(): void
    {
        $this->owaspDetails->setSource(self::$source);
        $this->assertEquals(self::$source, $this->owaspDetails->getSource());
    }

    public function testSettingAndGettingModule(): void
    {
        $this->owaspDetails->setModule(self::$module);
        $this->assertEquals(self::$module, $this->owaspDetails->getModule());
    }

    public function testSettingAndGettingFilePath(): void
    {
        $this->owaspDetails->setFilePath(self::$filePath);
        $this->assertEquals(self::$filePath, $this->owaspDetails->getFilePath());
    }

    public function testSettingAndGettingLine(): void
    {
        $this->owaspDetails->setLine(self::$line);
        $this->assertEquals(self::$line, $this->owaspDetails->getLine());
    }

    public function testSettingAndGettingNullLine(): void
    {
        // Une règle au niveau fichier/architecture n'a pas forcément de ligne précise.
        $this->owaspDetails->setLine(null);
        $this->assertNull($this->owaspDetails->getLine());
    }

    public function testSettingAndGettingMessage(): void
    {
        $this->owaspDetails->setMessage(self::$message);
        $this->assertEquals(self::$message, $this->owaspDetails->getMessage());
    }

    public function testSettingAndGettingModeCollecte(): void
    {
        $this->owaspDetails->setModeCollecte(self::$modeCollecte);
        $this->assertEquals(self::$modeCollecte, $this->owaspDetails->getModeCollecte());
    }

    public function testSettingAndGettingUtilisateurCollecte(): void
    {
        $this->owaspDetails->setUtilisateurCollecte(self::$utilisateurCollecte);
        $this->assertEquals(self::$utilisateurCollecte, $this->owaspDetails->getUtilisateurCollecte());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $newDate = new \DateTimeImmutable(self::$dateEnregistrement);
        $this->owaspDetails->setDateEnregistrement($newDate);
        $this->assertEquals($newDate, $this->owaspDetails->getDateEnregistrement());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new OwaspDetails());
        $this->assertEquals(16, count($reflectionClass->getProperties()));
    }
}
