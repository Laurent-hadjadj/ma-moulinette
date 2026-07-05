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

namespace App\Tests\Unit\Repository;

use App\Repository\ListeProjetRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Couvre uniquement la logique statique de ListeProjetRepository (pas de DB).
 * Les méthodes d'instance (avec accès Doctrine) seront couvertes par des tests
 * d'intégration dans tests/Integration/.
 */
#[CoversClass(ListeProjetRepository::class)]
final class ListeProjetRepositoryStaticTest extends TestCase
{
    public function testReturnsEmptyArrayWhenNoGroupes(): void
    {
        self::assertSame([], ListeProjetRepository::normalizeGroupesToTagPrefixes([]));
    }

    public function testFiltersNullAndStringNull(): void
    {
        $result = ListeProjetRepository::normalizeGroupesToTagPrefixes([null, 'null', 'team-a']);

        self::assertSame(['team-a'], $result);
    }

    public function testTrimsAndLowercases(): void
    {
        $result = ListeProjetRepository::normalizeGroupesToTagPrefixes(['  Team-Alpha  ', 'TEAM-BETA']);

        self::assertSame(['team-alpha', 'team-beta'], $result);
    }

    public function testReplacesWhitespaceRunsWithSingleHyphen(): void
    {
        $result = ListeProjetRepository::normalizeGroupesToTagPrefixes(['team   alpha', "team\tbeta"]);

        self::assertSame(['team-alpha', 'team-beta'], $result);
    }

    public function testIgnoresBlankStringsAfterTrim(): void
    {
        $result = ListeProjetRepository::normalizeGroupesToTagPrefixes(['', '   ', "\t", 'team-a']);

        self::assertSame(['team-a'], $result);
    }

    #[DataProvider('mixedInputProvider')]
    public function testNormalizesMixedInputs(array $input, array $expected): void
    {
        self::assertSame($expected, ListeProjetRepository::normalizeGroupesToTagPrefixes($input));
    }

    /**
     * @return array<string, array{0: array<int|string, mixed>, 1: array<int, string>}>
     */
    public static function mixedInputProvider(): array
    {
        return [
            'all valid' => [['Alpha', 'beta', 'GAMMA'], ['alpha', 'beta', 'gamma']],
            'mixed null and valid' => [[null, 'Alpha', 'null', 'Beta'], ['alpha', 'beta']],
            'preserves order' => [['c', 'a', 'b'], ['c', 'a', 'b']],
            'numeric coerced' => [[42, 'team'], ['42', 'team']],
        ];
    }
}
