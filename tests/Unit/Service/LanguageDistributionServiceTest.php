<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LanguageDistributionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(LanguageDistributionService::class)]
final class LanguageDistributionServiceTest extends TestCase
{
    private LanguageDistributionService $svc;

    protected function setUp(): void
    {
        $this->svc = new LanguageDistributionService();
    }

    // =====================================================================
    // parse()
    // =====================================================================

    public function testParseReturnsEmptyArrayWhenNull(): void
    {
        self::assertSame([], $this->svc->parse(null));
    }

    public function testParseReturnsEmptyArrayWhenEmptyString(): void
    {
        self::assertSame([], $this->svc->parse(''));
    }

    public function testParseReturnsEmptyArrayWhenBlankString(): void
    {
        self::assertSame([], $this->svc->parse('   '));
    }

    public function testParseJsonFormat(): void
    {
        $result = $this->svc->parse('{"java":12345,"ruby":56}');
        self::assertSame(['java' => 12345, 'ruby' => 56], $result);
    }

    public function testParseJsonFormatValuesAreIntegers(): void
    {
        $result = $this->svc->parse('{"java":"99"}');
        self::assertIsInt($result['java']);
        self::assertSame(99, $result['java']);
    }

    public function testParseTextFormatWithSemicolon(): void
    {
        $result = $this->svc->parse('java=12345;ruby=56;xml=4');
        self::assertSame(['java' => 12345, 'ruby' => 56, 'xml' => 4], $result);
    }

    public function testParseTextFormatWithComma(): void
    {
        $result = $this->svc->parse('java=100,php=200');
        self::assertSame(['java' => 100, 'php' => 200], $result);
    }

    public function testParseTextFormatWithColonSeparator(): void
    {
        $result = $this->svc->parse('java:100;php:200');
        self::assertSame(['java' => 100, 'php' => 200], $result);
    }

    public function testParseSkipsInvalidPairs(): void
    {
        $result = $this->svc->parse('java=100;invalid_no_value;php=200');
        self::assertSame(['java' => 100, 'php' => 200], $result);
    }

    public function testParseReturnsIntegerValues(): void
    {
        $result = $this->svc->parse('java=99');
        self::assertIsInt($result['java']);
        self::assertSame(99, $result['java']);
    }

    public function testParseSinglePair(): void
    {
        self::assertSame(['go' => 42], $this->svc->parse('go=42'));
    }

    // =====================================================================
    // getLabel()
    // =====================================================================

    #[DataProvider('knownCodeProvider')]
    public function testGetLabelReturnsKnownLabel(string $code, string $expected): void
    {
        self::assertSame($expected, $this->svc->getLabel($code));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function knownCodeProvider(): array
    {
        return [
            'java'   => ['java',   'Java'],
            'js'     => ['js',     'JavaScript'],
            'ts'     => ['ts',     'TypeScript'],
            'py'     => ['py',     'Python'],
            'php'    => ['php',    'PHP'],
            'web'    => ['web',    'HTML'],
            'css'    => ['css',    'CSS'],
            'xml'    => ['xml',    'XML'],
            'go'     => ['go',     'Go'],
            'kotlin' => ['kotlin', 'Kotlin'],
            'scala'  => ['scala',  'Scala'],
            'rust'   => ['rust',   'Rust'],
        ];
    }

    public function testGetLabelIsCaseInsensitive(): void
    {
        self::assertSame('Java', $this->svc->getLabel('JAVA'));
        self::assertSame('Java', $this->svc->getLabel('Java'));
        self::assertSame('Java', $this->svc->getLabel('java'));
    }

    public function testGetLabelTrimsCode(): void
    {
        self::assertSame('Java', $this->svc->getLabel('  java  '));
    }

    public function testGetLabelReturnsUcfirstForUnknownCode(): void
    {
        self::assertSame('Cobol', $this->svc->getLabel('cobol'));
    }

    public function testGetLabelReturnsUcfirstUppercaseUnknown(): void
    {
        self::assertSame('Abap', $this->svc->getLabel('ABAP'));
    }

    // =====================================================================
    // buildDistributionMatrix()
    // =====================================================================

    public function testBuildDistributionMatrixEmptyInput(): void
    {
        $result = $this->svc->buildDistributionMatrix([]);
        self::assertSame([], $result['languages']);
        self::assertSame([], $result['rows']);
        self::assertSame([], $result['chart']['labels']);
        self::assertSame([], $result['chart']['datasets']);
    }

    public function testBuildDistributionMatrixSingleVersion(): void
    {
        $suivi = [$this->makeRow('1.0.0', 'java=800;xml=200')];
        $result = $this->svc->buildDistributionMatrix($suivi);

        self::assertArrayHasKey('java', $result['languages']);
        self::assertArrayHasKey('xml', $result['languages']);
        self::assertSame('Java', $result['languages']['java']);
        self::assertSame('XML', $result['languages']['xml']);

        self::assertCount(1, $result['rows']);
        self::assertSame('1.0.0', $result['rows'][0]['version']);
        self::assertSame(800, $result['rows'][0]['parsed']['java']);

        self::assertSame(['1.0.0'], $result['chart']['labels']);
        self::assertCount(2, $result['chart']['datasets']);
    }

    public function testBuildDistributionMatrixLanguageUnionAcrossVersions(): void
    {
        $suivi = [
            $this->makeRow('1.0.0', 'java=500'),
            $this->makeRow('2.0.0', 'java=600;php=100'),
        ];
        $result = $this->svc->buildDistributionMatrix($suivi);

        self::assertArrayHasKey('java', $result['languages']);
        self::assertArrayHasKey('php', $result['languages']);
        self::assertCount(2, $result['rows']);
    }

    public function testBuildDistributionMatrixExcludesEmptyParsedFromChart(): void
    {
        $suivi = [
            $this->makeRow('1.0.0-REBUILD', null),
            $this->makeRow('2.0.0', 'java=600'),
        ];
        $result = $this->svc->buildDistributionMatrix($suivi);

        self::assertCount(2, $result['rows'], 'Toutes les versions dans rows');
        self::assertSame(['2.0.0'], $result['chart']['labels'], 'Seule la version avec distribution dans le chart');
    }

    public function testBuildDistributionMatrixSortsByLastVersionDesc(): void
    {
        $suivi = [
            $this->makeRow('1.0.0', 'java=600;php=100;xml=50'),
            $this->makeRow('2.0.0', 'java=1000;xml=200;php=50'),
        ];
        $result = $this->svc->buildDistributionMatrix($suivi);

        $codes = array_keys($result['languages']);
        self::assertSame(['java', 'xml', 'php'], $codes, 'Tri desc sur dernière version : java(1000) > xml(200) > php(50)');
    }

    public function testBuildDistributionMatrixDatasetLabelsMatchLanguages(): void
    {
        $suivi = [$this->makeRow('1.0.0', 'java=100;php=50')];
        $result = $this->svc->buildDistributionMatrix($suivi);

        $dsLabels = array_column($result['chart']['datasets'], 'label');
        self::assertContains('Java', $dsLabels);
        self::assertContains('PHP', $dsLabels);
    }

    public function testBuildDistributionMatrixDatasetDataCountMatchesChartRows(): void
    {
        $suivi = [
            $this->makeRow('1.0.0', 'java=100'),
            $this->makeRow('2.0.0', 'java=200'),
        ];
        $result = $this->svc->buildDistributionMatrix($suivi);

        foreach ($result['chart']['datasets'] as $ds) {
            self::assertCount(count($result['chart']['labels']), $ds['data']);
        }
    }

    public function testBuildDistributionMatrixMissingCodeInVersionGetsZero(): void
    {
        $suivi = [
            $this->makeRow('1.0.0', 'java=500;php=100'),
            $this->makeRow('2.0.0', 'java=600'),
        ];
        $result = $this->svc->buildDistributionMatrix($suivi);

        foreach ($result['chart']['datasets'] as $ds) {
            if ($ds['label'] === 'PHP') {
                self::assertSame(0, $ds['data'][1], 'PHP absent en v2.0.0 → 0');
                return;
            }
        }
        self::fail('Dataset PHP non trouvé');
    }

    /* MODIF 2026-06-08 : tiebreak strcmp quand 2 codes ont le même compte sur la dernière version. */
    public function testBuildDistributionMatrixAlphaTiebreakWhenSameCount(): void
    {
        // java et php ont le même compte (100) sur la dernière version → tri alpha : java < php
        $suivi = [$this->makeRow('1.0.0', 'java=100;php=100')];
        $result = $this->svc->buildDistributionMatrix($suivi);

        $codes = array_keys($result['languages']);
        $this->assertSame(['java', 'php'], $codes, 'Tiebreak alphabétique : java avant php');
    }

    // ---- helper ----

    /**
     * @return array<string, mixed>
     */
    private function makeRow(string $version, ?string $distribution): array
    {
        return [
            'version' => $version,
            'date' => '2026-01-01',
            'ncloc' => 1000,
            'mode_collecte' => 'COLLECTE',
            'initial' => 0,
            'ncloc_language_distribution' => $distribution,
        ];
    }
}
