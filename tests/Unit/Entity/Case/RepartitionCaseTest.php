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

use App\Entity\Repartition;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * [Description RepartitionCaseTest]
 *
 * v2.0.0 : test compact via dataProvider couvrant les 87 attributs de l’entité Repartition.
 */
class RepartitionCaseTest extends TestCase
{
    /**
     * Génère les 15 setters severity x type (Bug/Vulnerability/CodeSmell x Blocker/Critical/Major/Minor/Info)
     * éventuellement prefix par un module (frontend, backend, autre, inconnu).
     *
     * @return string[] liste des suffixes (ex: 'BugBlocker', 'FrontendCodeSmellMajor')
     */
    private static function severityNames(string $prefix = ''): array
    {
        $types = ['Bug', 'Vulnerability', 'CodeSmell'];
        $severities = ['Blocker', 'Critical', 'Major', 'Minor', 'Info'];
        $names = [];
        foreach ($types as $t) {
            foreach ($severities as $s) {
                $names[] = $prefix . $t . $s;
            }
        }
        return $names;
    }

    public static function attributesProvider(): iterable
    {
        // Identification
        yield 'mavenKey' => ['MavenKey', 'fr.ma-petite-entreprise:ma-moulinette'];
        yield 'name' => ['Name', 'ma-moulinette'];

        // 15 setters racine (BugBlocker, BugCritical, ..., CodeSmellInfo)
        $i = 1;
        foreach (self::severityNames() as $suffix) {
            yield "root_{$suffix}" => [$suffix, $i++];
        }

        // 4 modules x (1 total + 15 details) = 64 setters
        foreach (['Frontend', 'Backend', 'Autre', 'Inconnu'] as $module) {
            yield $module => [$module, $i++];
            foreach (self::severityNames($module) as $suffix) {
                yield "{$module}_{$suffix}" => [$suffix, $i++];
            }
        }

        // Setup / control / mode collecte
        yield 'setup' => ['Setup', 1];
        yield 'control' => ['Control', 'verifie'];
        yield 'modeCollecte' => ['ModeCollecte', 'COLLECTE'];
        yield 'utilisateurCollecte' => ['UtilisateurCollecte', 'admin@ma-moulinette.fr'];
    }

    #[DataProvider('attributesProvider')]
    public function testGetterSetter(string $suffix, mixed $value): void
    {
        $entity = new Repartition();
        $entity->{'set' . $suffix}($value);
        $this->assertSame($value, $entity->{'get' . $suffix}());
    }

    public function testSettingAndGettingId(): void
    {
        $entity = new Repartition();
        $entity->setId(42);
        $this->assertSame(42, $entity->getId());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = new Repartition();
        $date = new \DateTimeImmutable('2024-06-28 17:55:45+02:00');
        $entity->setDateEnregistrement($date);
        $this->assertEquals($date, $entity->getDateEnregistrement());
    }

    /**
     * v2.0.0 : l’entité Repartition comporte 87 attributs.
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Repartition());
        $this->assertEquals(87, count($reflectionClass->getProperties()));
    }
}
