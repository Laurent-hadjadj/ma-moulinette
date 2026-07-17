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

use App\Entity\Owasp;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * [Description OwaspCaseTest]
 *
 * v2.0.0 : test compact via dataProvider couvrant les 69 attributs de l'entite Owasp.
 */
class OwaspCaseTest extends TestCase
{
    /**
     * Liste des [setter/getter suffix, valeur a injecter] pour 65 attributs simples.
     * id, referentialOwasp, dateVersion, dateEnregistrement testes a part.
     */
    public static function attributesProvider(): iterable
    {
        // Identification
        yield 'mavenKey' => ['MavenKey', 'fr.ma-moulinette:ma-moulinette'];
        yield 'version' => ['Version', '1.2.0-RELEASE'];

        // Effort global
        yield 'effortTotal' => ['EffortTotal', 1500];

        // Totaux par categorie A1..A10
        for ($i = 1; $i <= 10; $i++) {
            yield "a{$i}" => ["A{$i}", $i * 10];
        }

        // Detail par severite pour chaque A1..A10
        $severities = ['Blocker', 'Critical', 'Major', 'Info', 'Minor'];
        for ($i = 1; $i <= 10; $i++) {
            foreach ($severities as $idx => $sev) {
                yield "a{$i}{$sev}" => ["A{$i}{$sev}", $i + $idx];
            }
        }

        // Mode de collecte / utilisateur
        yield 'modeCollecte' => ['ModeCollecte', 'COLLECTE'];
        yield 'utilisateurCollecte' => ['UtilisateurCollecte', 'admin@ma-moulinette.fr'];
    }

    #[DataProvider('attributesProvider')]
    public function testGetterSetter(string $suffix, mixed $value): void
    {
        $entity = new Owasp();
        $entity->{'set' . $suffix}($value);
        $this->assertSame($value, $entity->{'get' . $suffix}());
    }

    public function testSettingAndGettingId(): void
    {
        $entity = new Owasp();
        $entity->setId(42);
        $this->assertSame(42, $entity->getId());
    }

    /**
     * setReferentialOwasp accepte une string mais écrit dans une propriété int.
     * PHP coerce automatiquement la chaîne numérique vers int (sans strict_types).
     * Le getter renvoie la valeur convertie en string par PHP.
     */
    public function testSettingAndGettingReferentialOwasp(): void
    {
        $entity = new Owasp();
        $entity->setReferentialOwasp('2021');
        $this->assertEquals('2021', $entity->getReferentialOwasp());
    }

    public function testSettingAndGettingDateVersion(): void
    {
        $entity = new Owasp();
        $date = new \DateTimeImmutable('2024-07-12 16:34:46');
        $entity->setDateVersion($date);
        $this->assertEquals($date, $entity->getDateVersion());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = new Owasp();
        $date = new \DateTimeImmutable('2024-06-28 17:55:45+02:00');
        $entity->setDateEnregistrement($date);
        $this->assertEquals($date, $entity->getDateEnregistrement());
    }

    /**
     * v2.0.0 : l’entité Owasp comporte 70 attributs (dont `source` depuis le 2026-07-18).
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Owasp());
        // MODIF 2026-07-18 : +1 (nouveau champ `source` : 'facet'|'tag').
        $this->assertEquals(70, count($reflectionClass->getProperties()));
    }
}
