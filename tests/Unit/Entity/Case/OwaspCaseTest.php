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
 * MODIF 27/10/2026 : les 60 attributs a1..a10/aN_blocker..minor + source sont
 * remplacés par un seul attribut `categories` (JSON) + module_frontend/backend/autre
 * (cf. OwaspRuleClassifierService, BatchCollecteOwaspController).
 */
class OwaspCaseTest extends TestCase
{
    /**
     * Liste des [setter/getter suffix, valeur a injecter] pour les attributs simples.
     * id, referentialOwasp, dateVersion, dateEnregistrement, categories testes a part.
     *
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function attributesProvider(): iterable
    {
        yield 'mavenKey' => ['MavenKey', 'fr.ma-moulinette:ma-moulinette'];
        yield 'version' => ['Version', '1.2.0-RELEASE'];
        yield 'effortTotal' => ['EffortTotal', 1500];
        yield 'moduleFrontend' => ['ModuleFrontend', 12];
        yield 'moduleBackend' => ['ModuleBackend', 34];
        yield 'moduleAutre' => ['ModuleAutre', 5];
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
        // Appel via réflexion : le test vérifie délibérément la coercition PHP
        // faible (string numérique -> int), un appel direct violerait le type
        // statique du paramètre sans refléter un vrai bug.
        (new \ReflectionMethod($entity, 'setReferentialOwasp'))->invoke($entity, '2021');
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

    public function testSettingAndGettingCategoriesDefaultsToEmptyArray(): void
    {
        $entity = new Owasp();
        $this->assertSame([], $entity->getCategories());

        $categories = [
            'a1' => ['total' => 5, 'blocker' => 1, 'critical' => 2, 'major' => 2, 'minor' => 0, 'info' => 0, 'source' => 'mixed'],
        ];
        $entity->setCategories($categories);
        $this->assertSame($categories, $entity->getCategories());
    }

    /**
     * MODIF 27/10/2026 : 70 → 13 (suppression des 60 attributs a1..a10/aN_blocker..minor
     * + source, remplacés par categories + module_frontend/backend/autre : -60 +3 = -57,
     * 70 - 57 = 13).
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Owasp());
        $this->assertEquals(13, count($reflectionClass->getProperties()));
    }
}
