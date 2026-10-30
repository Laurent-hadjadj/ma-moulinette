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

use App\Entity\Accessibility;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * [Description AccessibilityCaseTest]
 *
 * MODIF 27/10/2026 : couverture — aucune des 18 paires getter/setter n'était
 * exercée (même trou que RciCaseTest avant correctif : seuls les setters
 * étaient atteints via les tests d'intégration/batch, jamais les getters).
 */
class AccessibilityCaseTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function attributesProvider(): iterable
    {
        yield 'mavenKey' => ['MavenKey', 'fr.ma-moulinette:ma-moulinette'];

        // Global
        yield 'accessibilityAnomalies' => ['AccessibilityAnomalies', 42];
        yield 'accessibilityDetteMinute' => ['AccessibilityDetteMinute', 120];
        yield 'accessibilityRating' => ['AccessibilityRating', 'A'];

        // CSS
        yield 'accessibilityCssAnomalies' => ['AccessibilityCssAnomalies', 10];
        yield 'accessibilityCssDetteMinute' => ['AccessibilityCssDetteMinute', 30];
        yield 'accessibilityCssRating' => ['AccessibilityCssRating', 'B'];

        // Web (HTML)
        yield 'accessibilityWebAnomalies' => ['AccessibilityWebAnomalies', 5];
        yield 'accessibilityWebDetteMinute' => ['AccessibilityWebDetteMinute', 15];
        yield 'accessibilityWebRating' => ['AccessibilityWebRating', 'A'];

        // JavaScript
        yield 'accessibilityJavascriptAnomalies' => ['AccessibilityJavascriptAnomalies', 0];
        yield 'accessibilityJavascriptDetteMinute' => ['AccessibilityJavascriptDetteMinute', 0];
        yield 'accessibilityJavascriptRating' => ['AccessibilityJavascriptRating', null];

        // TypeScript
        yield 'accessibilityTypescriptAnomalies' => ['AccessibilityTypescriptAnomalies', null];
        yield 'accessibilityTypescriptDetteMinute' => ['AccessibilityTypescriptDetteMinute', null];
        yield 'accessibilityTypescriptRating' => ['AccessibilityTypescriptRating', null];

        // Méta
        yield 'modeCollecte' => ['ModeCollecte', 'COLLECTE'];
        yield 'utilisateurCollecte' => ['UtilisateurCollecte', 'admin@ma-moulinette.fr'];
    }

    #[DataProvider('attributesProvider')]
    public function testGetterSetter(string $suffix, mixed $value): void
    {
        $entity = new Accessibility();
        $entity->{'set' . $suffix}($value);
        $this->assertSame($value, $entity->{'get' . $suffix}());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = new Accessibility();
        $date = new \DateTimeImmutable('2026-10-27 12:00:00');
        $entity->setDateEnregistrement($date);
        $this->assertSame($date, $entity->getDateEnregistrement());
    }

    /**
     * v2.4.0 : l'entité Accessibility comporte 20 attributs (id + mavenKey +
     * 15 colonnes accessibility_* + modeCollecte + utilisateurCollecte +
     * dateEnregistrement).
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Accessibility());
        $this->assertEquals(20, count($reflectionClass->getProperties()));
    }
}
