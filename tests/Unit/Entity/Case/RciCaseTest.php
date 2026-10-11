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

use App\Entity\Rci;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * [Description RciCaseTest]
 *
 * MODIF 2026-10-11 : couverture — aucune des 24 paires getter/setter n'était
 * exercée (seuls les setters étaient atteints via les tests d'intégration,
 * jamais les getters).
 */
class RciCaseTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function attributesProvider(): iterable
    {
        yield 'mavenKey' => ['MavenKey', 'fr.ma-moulinette:ma-moulinette'];

        // Global
        yield 'ncloc' => ['Ncloc', 15000];
        yield 'weight' => ['Weight', 300];
        yield 'indicator' => ['Indicator', 2.5];
        yield 'rating' => ['Rating', 'B'];

        // Module frontend
        yield 'frontendNcloc' => ['FrontendNcloc', 5000];
        yield 'frontendWeight' => ['FrontendWeight', 90];
        yield 'frontendIndicator' => ['FrontendIndicator', 1.8];
        yield 'frontendRating' => ['FrontendRating', 'A'];

        // Module backend
        yield 'backendNcloc' => ['BackendNcloc', 7000];
        yield 'backendWeight' => ['BackendWeight', 150];
        yield 'backendIndicator' => ['BackendIndicator', 3.1];
        yield 'backendRating' => ['BackendRating', 'C'];

        // Module autre
        yield 'autreNcloc' => ['AutreNcloc', 2000];
        yield 'autreWeight' => ['AutreWeight', 30];
        yield 'autreIndicator' => ['AutreIndicator', 0.5];
        yield 'autreRating' => ['AutreRating', 'A'];

        // Fichiers non classés
        yield 'inconnuNcloc' => ['InconnuNcloc', 1000];
        yield 'inconnuWeight' => ['InconnuWeight', 30];
        yield 'inconnuIndicator' => ['InconnuIndicator', 0.0];
        yield 'inconnuRating' => ['InconnuRating', 'A'];

        // Méta
        yield 'modeCollecte' => ['ModeCollecte', 'COLLECTE'];
        yield 'utilisateurCollecte' => ['UtilisateurCollecte', 'admin@ma-moulinette.fr'];
    }

    #[DataProvider('attributesProvider')]
    public function testGetterSetter(string $suffix, mixed $value): void
    {
        $entity = new Rci();
        $entity->{'set' . $suffix}($value);
        $this->assertSame($value, $entity->{'get' . $suffix}());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = new Rci();
        $date = new \DateTimeImmutable('2026-10-11 12:00:00');
        $entity->setDateEnregistrement($date);
        $this->assertSame($date, $entity->getDateEnregistrement());
    }

    /**
     * v2.2.0 : l'entité Rci comporte 25 attributs (id + 24 colonnes).
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Rci());
        $this->assertEquals(25, count($reflectionClass->getProperties()));
    }
}
