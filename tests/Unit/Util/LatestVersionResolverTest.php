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

use App\Util\LatestVersionResolver;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-14 : tests Unit du sélecteur
 * "dernière version release vs dernière version ingérée".
 */
class LatestVersionResolverTest extends TestCase
{
    /* ============ isRelease - cas release ============ */

    public function testIsReleaseAcceptsPlainSemver(): void
    {
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3'));
        $this->assertTrue(LatestVersionResolver::isRelease('0.0.1'));
        $this->assertTrue(LatestVersionResolver::isRelease('10.20.30'));
        $this->assertTrue(LatestVersionResolver::isRelease('4.2.0'));
    }

    public function testIsReleaseAcceptsTwoOrFourSegment(): void
    {
        // Le comparateur accepte 'x.y' et 'x.y.z.w'
        $this->assertTrue(LatestVersionResolver::isRelease('1.2'));
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3.4'));
    }

    public function testIsReleaseStripsLeadingV(): void
    {
        $this->assertTrue(LatestVersionResolver::isRelease('v1.2.3'));
        $this->assertTrue(LatestVersionResolver::isRelease('V4.2.0'));
    }

    public function testIsReleaseAcceptsWhitelistedQualifiers(): void
    {
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3-RELEASE'));
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3-FINAL'));
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3-GA'));
        // Case-insensitive (parser uppercase le qualifier)
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3-release'));
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3-final'));
    }

    public function testIsReleaseAcceptsServicePack(): void
    {
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3-SP1'));
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3-SP10'));
        $this->assertTrue(LatestVersionResolver::isRelease('5.4.0-sp123'));
    }

    public function testIsReleaseAcceptsNumericBuildSuffix(): void
    {
        // '1.2.3-1' (ex: build CI numéroté) reste une release
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3-1'));
        $this->assertTrue(LatestVersionResolver::isRelease('1.2.3-42'));
    }

    /* ============ isRelease - cas dev / rejet ============ */

    public function testIsReleaseRejectsSnapshot(): void
    {
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-SNAPSHOT'));
        $this->assertFalse(LatestVersionResolver::isRelease('4.2.0-snapshot'));
    }

    public function testIsReleaseRejectsPreReleaseQualifiers(): void
    {
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-RC1'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-RC'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-ALPHA'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-ALPHA1'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-BETA'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-BETA2'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-M1'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-M10'));
    }

    public function testIsReleaseRejectsUnparsableVersions(): void
    {
        $this->assertFalse(LatestVersionResolver::isRelease(''));
        $this->assertFalse(LatestVersionResolver::isRelease('not-a-version'));
        $this->assertFalse(LatestVersionResolver::isRelease('abc'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.x.0'));
        $this->assertFalse(LatestVersionResolver::isRelease('2024.06.01-build-abc123'));
    }

    public function testIsReleaseRejectsCustomQualifier(): void
    {
        // Qualifier hors whitelist : on préfère la prudence (rejet)
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-DEV'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-NIGHTLY'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-PREVIEW'));
        $this->assertFalse(LatestVersionResolver::isRelease('1.2.3-EXPERIMENTAL'));
    }

    /* ============ pickLatest - cas nominaux ============ */

    public function testPickLatestReturnsNullOnEmpty(): void
    {
        $this->assertNull(LatestVersionResolver::pickLatest([], false));
        $this->assertNull(LatestVersionResolver::pickLatest([], true));
    }

    public function testPickLatestSingleVersion(): void
    {
        $this->assertSame('1.2.3', LatestVersionResolver::pickLatest(['1.2.3'], false));
        $this->assertSame('1.2.3', LatestVersionResolver::pickLatest(['1.2.3'], true));
        // Single SNAPSHOT : overall=ok, release=null
        $this->assertSame('1.2.3-SNAPSHOT', LatestVersionResolver::pickLatest(['1.2.3-SNAPSHOT'], false));
        $this->assertNull(LatestVersionResolver::pickLatest(['1.2.3-SNAPSHOT'], true));
    }

    public function testPickLatestOverallReturnsMaxSemver(): void
    {
        $versions = ['4.0.0', '4.1.0', '4.2.0', '4.0.1', '3.9.9'];
        $this->assertSame('4.2.0', LatestVersionResolver::pickLatest($versions, false));

        // Avec un SNAPSHOT plus haut numériquement, il l'emporte en mode overall
        $versions = ['4.2.0-RELEASE', '4.3.0-SNAPSHOT'];
        $this->assertSame('4.3.0-SNAPSHOT', LatestVersionResolver::pickLatest($versions, false));
    }

    public function testPickLatestReleaseFiltersOutDev(): void
    {
        $versions = ['4.2.0-RELEASE', '4.3.0-SNAPSHOT', '4.1.0', '4.2.5-RC1'];
        // Mode release : seules 4.2.0-RELEASE et 4.1.0 sont eligibles -> 4.2.0
        $this->assertSame('4.2.0-RELEASE', LatestVersionResolver::pickLatest($versions, true));
    }

    public function testPickLatestReleaseReturnsNullWhenAllAreDev(): void
    {
        $versions = ['1.0.0-SNAPSHOT', '1.0.1-SNAPSHOT', '1.1.0-RC1'];
        $this->assertNull(LatestVersionResolver::pickLatest($versions, true));
        // Mais en mode overall on a quand meme une response
        $this->assertSame('1.1.0-RC1', LatestVersionResolver::pickLatest($versions, false));
    }

    public function testPickLatestRealisticParkScenario(): void
    {
        // Cas reel : 1 appli scannée sous plusieurs versions sur 6 mois
        $versions = [
            '4.0.0-RELEASE',
            '4.0.1-RELEASE',
            '4.0.1-SNAPSHOT',  // SNAPSHOT du meme num que ci-dessus (depose par erreur en CI)
            '4.1.0-RELEASE',
            '4.2.0-RELEASE',
            '4.2.1-SNAPSHOT',  // version en cours
            '4.2.2-RELEASE',   // dernière release stable
            '4.3.0-SNAPSHOT',  // prochain cycle dev
        ];

        // RSSI veut savoir : qu'est-ce qui est en prod aujourd'hui ?
        $this->assertSame('4.2.2-RELEASE', LatestVersionResolver::pickLatest($versions, true));

        // Lead dev veut savoir : ou en est-on dans le cycle ?
        $this->assertSame('4.3.0-SNAPSHOT', LatestVersionResolver::pickLatest($versions, false));
    }

    public function testPickLatestPreservesInputOrderInTies(): void
    {
        // 2 versions equivalentes (1.0.0 == 1.0.0-FINAL == 1.0.0-RELEASE) :
        // la 1ere lue est conservee (strictement >, pas >=)
        $versions = ['1.0.0', '1.0.0-RELEASE', '1.0.0-FINAL'];
        $this->assertSame('1.0.0', LatestVersionResolver::pickLatest($versions, true));

        $versions = ['1.0.0-FINAL', '1.0.0', '1.0.0-RELEASE'];
        $this->assertSame('1.0.0-FINAL', LatestVersionResolver::pickLatest($versions, true));
    }
}
