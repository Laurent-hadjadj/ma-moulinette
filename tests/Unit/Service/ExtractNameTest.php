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

namespace App\Tests\Unit\Service;

use App\Service\ExtractName;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(ExtractName::class)]
final class ExtractNameTest extends TestCase
{
    private ExtractName $service;

    protected function setUp(): void
    {
        $this->service = new ExtractName();
    }

    public function testReturnsEmptyStringWhenMavenKeyIsNull(): void
    {
        self::assertSame('', $this->service->extractNameFromMavenKey(null));
    }

    public function testReturnsRawValueWhenMavenKeyHasNoColon(): void
    {
        self::assertSame('ma-moulinette', $this->service->extractNameFromMavenKey('ma-moulinette'));
    }

    public function testExtractsArtifactIdFromStandardMavenKey(): void
    {
        self::assertSame(
            'ma-moulinette',
            $this->service->extractNameFromMavenKey('fr.ma-moulinette:ma-moulinette')
        );
    }

    #[DataProvider('mavenKeyProvider')]
    public function testExtractsExpectedName(string $mavenKey, string $expected): void
    {
        self::assertSame($expected, $this->service->extractNameFromMavenKey($mavenKey));
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function mavenKeyProvider(): array
    {
        return [
            'standard groupId:artifactId' => ['fr.ma-moulinette:projet-b', 'projet-b'],
            'multiple colons returns second segment' => ['a:b:c', 'b'],
            'empty artifact returns empty' => ['groupId:', ''],
            'empty group returns artifact' => [':my-app', 'my-app'],
            'empty string returns empty' => ['', ''],
        ];
    }
}
