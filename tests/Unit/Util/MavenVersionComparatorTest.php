<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Util;

use App\Util\MavenVersionComparator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-12 : tests Unit du comparateur
 * Maven (helper utilise par DcScanRepository::findPreviousScanAnyVersion).
 */
class MavenVersionComparatorTest extends TestCase
{
    /* ============ compare ============ */

    public function testCompareNumericAscending(): void
    {
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0', '1.0.1'));
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0', '1.1.0'));
        $this->assertSame(-1, MavenVersionComparator::compare('1.99.99', '2.0.0'));
    }

    public function testCompareNumericDescending(): void
    {
        $this->assertSame(1, MavenVersionComparator::compare('2.0.0', '1.99.99'));
        $this->assertSame(1, MavenVersionComparator::compare('1.0.1', '1.0.0'));
    }

    public function testCompareNumericEqual(): void
    {
        $this->assertSame(0, MavenVersionComparator::compare('1.0.0', '1.0.0'));
    }

    public function testCompareTreatsMissingComponentsAsZero(): void
    {
        // 1.0 == 1.0.0 (padding implicite avec 0)
        $this->assertSame(0, MavenVersionComparator::compare('1.0', '1.0.0'));
        $this->assertSame(0, MavenVersionComparator::compare('2', '2.0.0'));
    }

    public function testCompareStripsLeadingV(): void
    {
        // 'v3.5.0' doit etre traite comme '3.5.0'
        $this->assertSame(0, MavenVersionComparator::compare('v3.5.0', '3.5.0'));
        $this->assertSame(0, MavenVersionComparator::compare('V3.5.0', '3.5.0'));
        $this->assertSame(-1, MavenVersionComparator::compare('v3.5.0', '4.0.0'));
    }

    public function testCompareEmptyAndReleaseQualifierAreEquivalent(): void
    {
        // '1.0.0' == '1.0.0-RELEASE' == '1.0.0-FINAL'
        $this->assertSame(0, MavenVersionComparator::compare('1.0.0', '1.0.0-RELEASE'));
        $this->assertSame(0, MavenVersionComparator::compare('1.0.0-RELEASE', '1.0.0-FINAL'));
        $this->assertSame(0, MavenVersionComparator::compare('1.0.0', '1.0.0-FINAL'));
    }

    public function testCompareSnapshotIsLowestQualifier(): void
    {
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0-SNAPSHOT', '1.0.0-RELEASE'));
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0-SNAPSHOT', '1.0.0-RC1'));
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0-SNAPSHOT', '1.0.0-BETA1'));
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0-SNAPSHOT', '1.0.0-ALPHA1'));
    }

    public function testCompareQualifierOrdering(): void
    {
        // ALPHA < BETA < RC < (vide) = RELEASE = FINAL
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0-ALPHA1', '1.0.0-BETA1'));
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0-BETA1', '1.0.0-RC1'));
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0-RC1', '1.0.0-RELEASE'));
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0-RC1', '1.0.0'));
    }

    public function testCompareNumericTakesPriorityOverQualifier(): void
    {
        // Une version numerique plus haute l'emporte meme si qualifier "inferieur"
        $this->assertSame(1, MavenVersionComparator::compare('2.0.0-SNAPSHOT', '1.0.0-RELEASE'));
        $this->assertSame(-1, MavenVersionComparator::compare('1.0.0-RELEASE', '2.0.0-SNAPSHOT'));
    }

    public function testCompareHandlesUnusualPatchDigit(): void
    {
        // 4.2.03 == 4.2.3 (intval('03') == 3)
        $this->assertSame(0, MavenVersionComparator::compare('4.2.03', '4.2.3'));
        $this->assertSame(-1, MavenVersionComparator::compare('4.2.0', '4.2.03'));
    }

    public function testCompareFallsBackToStrcmpOnUnparsable(): void
    {
        // Versions non parsables (ex : build hash, dates exotiques) -> fallback strcmp
        $this->assertSame(-1, MavenVersionComparator::compare('abc123', 'def456'));
        // Mais une version parsable contre une non-parsable utilise strcmp aussi
        $this->assertNotSame(0, MavenVersionComparator::compare('1.0.0', 'not-a-version'));
    }

    /* ============ parse ============ */

    public function testParseStandard(): void
    {
        $parsed = MavenVersionComparator::parse('1.2.3');
        $this->assertSame(['parts' => [1, 2, 3], 'qualifier' => ''], $parsed);
    }

    public function testParseWithQualifier(): void
    {
        $parsed = MavenVersionComparator::parse('1.2.3-RELEASE');
        $this->assertSame(['parts' => [1, 2, 3], 'qualifier' => 'RELEASE'], $parsed);
    }

    public function testParseStripsLeadingV(): void
    {
        $parsed = MavenVersionComparator::parse('v3.5.0-RELEASE');
        $this->assertSame(['parts' => [3, 5, 0], 'qualifier' => 'RELEASE'], $parsed);
    }

    public function testParseReturnsNullOnEmptyOrUnparsable(): void
    {
        $this->assertNull(MavenVersionComparator::parse(''));
        $this->assertNull(MavenVersionComparator::parse('abc'));
        $this->assertNull(MavenVersionComparator::parse('1.x.0'));
    }

    public function testParseQualifierIsUppercased(): void
    {
        $parsed = MavenVersionComparator::parse('1.0.0-release');
        $this->assertSame('RELEASE', $parsed['qualifier']);
    }

    /* ============ scenario reel ============ */

    /* MODIF 2026-06-08 : qualifier inconnu → rank 4 (stable), identique à RELEASE. */
    public function testCompareUnknownQualifierTreatedAsStable(): void
    {
        // MYQUAL ne correspond à aucun cas : SNAPSHOT/ALPHA/BETA/RC/RELEASE/FINAL → fallback return 4
        $this->assertSame(0, MavenVersionComparator::compare('1.0.0-MYQUAL', '1.0.0-RELEASE'));
        $this->assertSame(0, MavenVersionComparator::compare('1.0.0-MYQUAL', '1.0.0'));
        $this->assertSame(0, MavenVersionComparator::compare('1.0.0-CUSTOM', '1.0.0-FINAL'));
    }

    public function testRealMavenVersionsSorting(): void
    {
        $versions = [
            '4.2.2-RELEASE',
            '4.0.0-RELEASE',
            '4.2.0-RELEASE',
            '4.1.0-RELEASE',
            '4.2.03-RELEASE',
        ];
        usort($versions, [MavenVersionComparator::class, 'compare']);
        // Ordre attendu (croissant) : 4.0.0 < 4.1.0 < 4.2.0 < 4.2.03 (=4.2.3) < 4.2.2 ?
        // Non, 4.2.03 == 4.2.3 > 4.2.2. Donc : 4.0.0 < 4.1.0 < 4.2.0 < 4.2.2 < 4.2.3
        $this->assertSame([
            '4.0.0-RELEASE',
            '4.1.0-RELEASE',
            '4.2.0-RELEASE',
            '4.2.2-RELEASE',
            '4.2.03-RELEASE',
        ], $versions);
    }
}
