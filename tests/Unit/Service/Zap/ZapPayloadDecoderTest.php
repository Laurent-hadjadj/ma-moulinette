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

namespace App\Tests\Unit\Service\Zap;

use App\Entity\ZapProcessingQueue;
use App\Service\Zap\ZapPayloadDecodeException;
use App\Service\Zap\ZapPayloadDecoder;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-10-13 : test unit du ZapPayloadDecoder, sur le gabarit
 * PayloadDecoderTest (Dependency-Check).
 *
 * Couvre les 7 contrôles d'intégrité, dont la validation schema propre a
 * ZAP (site[] non vide, 1 seul site, alerts[] présent).
 */
class ZapPayloadDecoderTest extends TestCase
{
    private ZapPayloadDecoder $decoder;

    private const VALID_JSON = '{"@version":"2.17.0","@generated":"Tue, 13 Oct 2026 12:00:00","site":[{"@name":"http://exemple.local/","alerts":[]}]}';

    protected function setUp(): void
    {
        $this->decoder = new ZapPayloadDecoder(maxRawSize: 5 * 1024, maxDecodedSize: 25 * 1024);
    }

    // ═════════════════════ CONTROL 1 — taille brute ═══════════════════════════

    public function testRejectsEmptyPayload(): void
    {
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('Payload vide');
        try {
            $this->decoder->decode('', 'application/json');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(400, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsRawSizeOverLimit(): void
    {
        $oversized = str_repeat('a', 6 * 1024);
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('trop volumineux');
        try {
            $this->decoder->decode($oversized, 'application/json');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(413, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 2 — magic bytes ════════════════════════════

    public function testRejectsUnknownMagicBytes(): void
    {
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('Format non reconnu');
        try {
            $this->decoder->decode('garbage non identifiable', 'application/json');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(415, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsContentTypeMismatch(): void
    {
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('incompatible');
        try {
            $this->decoder->decode(self::VALID_JSON, 'application/zip');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(415, $e->httpStatus);
            throw $e;
        }
    }

    public function testToleratesAbsentContentType(): void
    {
        $result = $this->decoder->decode(self::VALID_JSON, null);
        $this->assertSame(ZapProcessingQueue::CONTENT_JSON, $result->contentType);
    }

    // ═════════════════════ CONTROL 3+4 — decompression et taille décodée ══════

    public function testAcceptsValidJson(): void
    {
        $result = $this->decoder->decode(self::VALID_JSON, 'application/json');
        $this->assertSame(ZapProcessingQueue::CONTENT_JSON, $result->contentType);
        $this->assertSame(strlen(self::VALID_JSON), $result->decodedSize);
    }

    public function testAcceptsValidGzip(): void
    {
        $gz = gzencode(self::VALID_JSON, 6);
        $result = $this->decoder->decode($gz, 'application/gzip');
        $this->assertSame(ZapProcessingQueue::CONTENT_GZIP, $result->contentType);
        $this->assertSame(self::VALID_JSON, $result->jsonString);
    }

    public function testRejectsDecodedSizeOverLimit(): void
    {
        // Même marge que PayloadDecoderTest (DC) : un dépassement trop large fait
        // échouer gzdecode() lui-même (retourne false, cf. anti zip-bomb "dur"),
        // avant d'atteindre le contrôle explicite de taille décodée — il faut
        // rester proche de la limite pour exercer CE contrôle précis.
        $big = '{"reportSchema":"1.1","padding":"' . str_repeat('a', 26 * 1024) . '"}';
        $compressed = gzencode($big, 9);
        $this->assertLessThan(5 * 1024, strlen($compressed), 'Pre-condition : compressed sous 5 KB');
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('trop volumineux');
        try {
            $this->decoder->decode($compressed, 'application/gzip');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(413, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 5 — JSON strict ═════════════════════════════

    public function testRejectsInvalidJson(): void
    {
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('JSON invalide');
        try {
            $this->decoder->decode('{"site":[', 'application/json');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(400, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 6 — schema minimal ZAP ══════════════════════

    public function testRejectsMissingVersion(): void
    {
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('@version');
        try {
            $this->decoder->decode('{"site":[{"@name":"x","alerts":[]}]}', 'application/json');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsMissingSite(): void
    {
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('"site"');
        try {
            $this->decoder->decode('{"@version":"2.17.0"}', 'application/json');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsMultiSiteReport(): void
    {
        $multiSite = '{"@version":"2.17.0","site":[{"@name":"a","alerts":[]},{"@name":"b","alerts":[]}]}';
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('multi-site');
        try {
            $this->decoder->decode($multiSite, 'application/json');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsMissingAlerts(): void
    {
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('alerts');
        try {
            $this->decoder->decode('{"@version":"2.17.0","site":[{"@name":"x"}]}', 'application/json');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 7 — sha256 ═══════════════════════════════════

    public function testComputesSha256OfDecodedJson(): void
    {
        $result = $this->decoder->decode(self::VALID_JSON, 'application/json');
        $this->assertSame(hash('sha256', self::VALID_JSON), $result->sha256);
    }
}
