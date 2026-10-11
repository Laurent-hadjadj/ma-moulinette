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

use App\Entity\CleanCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * [Description CleanCodeCaseTest]
 *
 * MODIF 2026-10-11 : couverture — aucune des paires getter/setter n'était
 * exercée (seuls les setters étaient atteints via les tests d'intégration,
 * jamais les getters).
 */
class CleanCodeCaseTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function attributesProvider(): iterable
    {
        yield 'mavenKey' => ['MavenKey', 'fr.ma-moulinette:ma-moulinette'];
        yield 'projectName' => ['ProjectName', 'ma-moulinette'];
        yield 'issueTotal' => ['IssueTotal', 42];

        // Clean Code Attribute Categories
        yield 'ccConsistent' => ['CcConsistent', 12];
        yield 'ccIntentional' => ['CcIntentional', 8];
        yield 'ccAdaptable' => ['CcAdaptable', 5];
        yield 'ccResponsible' => ['CcResponsible', 3];

        // Impact Software Qualities
        yield 'qualityMaintainability' => ['QualityMaintainability', 20];
        yield 'qualityReliability' => ['QualityReliability', 10];
        yield 'qualitySecurity' => ['QualitySecurity', 5];

        // Impact Severities
        yield 'impactBlocker' => ['ImpactBlocker', 1];
        yield 'impactHigh' => ['ImpactHigh', 2];
        yield 'impactMedium' => ['ImpactMedium', 3];
        yield 'impactLow' => ['ImpactLow', 4];
        yield 'impactInfo' => ['ImpactInfo', 5];

        // Standards de conformité
        yield 'owaspTop10' => ['OwaspTop10', 7];
        yield 'sansTop25' => ['SansTop25', 4];
        yield 'cwe' => ['Cwe', 9];

        // Méta
        yield 'modeCollecte' => ['ModeCollecte', 'COLLECTE'];
        yield 'utilisateurCollecte' => ['UtilisateurCollecte', 'admin@ma-moulinette.fr'];
    }

    #[DataProvider('attributesProvider')]
    public function testGetterSetter(string $suffix, mixed $value): void
    {
        $entity = new CleanCode();
        $entity->{'set' . $suffix}($value);
        $this->assertSame($value, $entity->{'get' . $suffix}());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = new CleanCode();
        $date = new \DateTimeImmutable('2026-10-11 12:00:00');
        $entity->setDateEnregistrement($date);
        $this->assertSame($date, $entity->getDateEnregistrement());
    }

    /**
     * v2.2.0 : l'entité CleanCode comporte 22 attributs (id + 21 colonnes).
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new CleanCode());
        $this->assertEquals(22, count($reflectionClass->getProperties()));
    }
}
