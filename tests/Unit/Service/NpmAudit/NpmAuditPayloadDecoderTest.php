<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\Service\NpmAudit;

use App\Entity\NpmProcessingQueue;
use App\Service\NpmAudit\NpmAuditPayloadDecodeException;
use App\Service\NpmAudit\NpmAuditPayloadDecoder;
use PHPUnit\Framework\TestCase;

/**
 * Couvre les 7 contrôles d'intégrité x formats supportés (json/gzip/zip),
 * sur le modèle de PayloadDecoderTest (DC) — le schéma validé diffère :
 * enveloppe {"full": {...}, "prodOnly"?: {...}}, pas de projectInfo (vient
 * des headers HTTP, validés par le contrôleur, pas par ce decoder).
 */
class NpmAuditPayloadDecoderTest extends TestCase
{
    private NpmAuditPayloadDecoder $decoder;

    private const VALID_JSON = '{"full":{"auditReportVersion":2,"vulnerabilities":{},"metadata":{"vulnerabilities":{"info":0,"low":0,"moderate":0,"high":0,"critical":0,"total":0},"dependencies":{"prod":1,"dev":1,"optional":0,"peer":0,"peerOptional":0,"total":2}}}}';

    protected function setUp(): void
    {
        $this->decoder = new NpmAuditPayloadDecoder(maxRawSize: 5 * 1024, maxDecodedSize: 25 * 1024);
    }

    // ═════════════════════ CONTROL 1 — taille brute ═══════════════════════════

    public function testRejectsEmptyPayload(): void
    {
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('Payload vide');
        try {
            $this->decoder->decode('', 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(400, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsRawSizeOverLimit(): void
    {
        $oversized = str_repeat('a', 6 * 1024);
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('trop volumineux');
        try {
            $this->decoder->decode($oversized, 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(413, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 2 — magic bytes ════════════════════════════

    public function testRejectsUnknownMagicBytes(): void
    {
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('Format non reconnu');
        try {
            $this->decoder->decode('garbage non identifiable', 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(415, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsContentTypeMismatch(): void
    {
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('incompatible');
        try {
            $this->decoder->decode(self::VALID_JSON, 'application/zip');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(415, $e->httpStatus);
            throw $e;
        }
    }

    public function testToleratesAbsentContentType(): void
    {
        $result = $this->decoder->decode(self::VALID_JSON, null);
        $this->assertSame(NpmProcessingQueue::CONTENT_JSON, $result->contentType);
    }

    // ═════════════════════ CONTROL 3+4 — decompression et taille décodée ══════

    public function testAcceptsValidJson(): void
    {
        $result = $this->decoder->decode(self::VALID_JSON, 'application/json');
        $this->assertSame(NpmProcessingQueue::CONTENT_JSON, $result->contentType);
        $this->assertSame(strlen(self::VALID_JSON), $result->decodedSize);
    }

    public function testAcceptsValidGzip(): void
    {
        $compressed = gzencode(self::VALID_JSON, 6);
        self::assertNotFalse($compressed);
        $result = $this->decoder->decode($compressed, 'application/gzip');
        $this->assertSame(NpmProcessingQueue::CONTENT_GZIP, $result->contentType);
        $this->assertSame(self::VALID_JSON, $result->jsonString);
    }

    public function testAcceptsValidZip(): void
    {
        $zipBody = $this->buildZipWithSingleFile('report.json', self::VALID_JSON);
        $result = $this->decoder->decode($zipBody, 'application/zip');
        $this->assertSame(NpmProcessingQueue::CONTENT_ZIP, $result->contentType);
        $this->assertSame(self::VALID_JSON, $result->jsonString);
    }

    public function testRejectsZipWithMultipleFiles(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'npmaudit-test-zip-');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('report.json', self::VALID_JSON);
        $zip->addFromString('extra.txt', 'autre contenu');
        $zip->close();
        $zipBody = file_get_contents($tmp);
        @unlink($tmp);
        self::assertNotFalse($zipBody);

        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('exactement 1 fichier');
        try {
            $this->decoder->decode($zipBody, 'application/zip');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsCorruptedGzip(): void
    {
        $corrupted = "\x1F\x8B" . str_repeat("\x00", 100);
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('Décompression gzip échouée');
        try {
            $this->decoder->decode($corrupted, null);
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsDecodedSizeOverLimit(): void
    {
        /* MODIF 2026-11-20 : padding juste au-dessus de la limite (pas
         * +26 KB comme le test DC) — gzdecode(..., maxLength) échoue
         * purement (retourne false) plutôt que de tronquer dès que le
         * dépassement est trop important (comportement lié à son buffer
         * interne, constaté empiriquement, pas documenté de façon fiable).
         * Un dépassement léger reste dans la zone où gzdecode renvoie la
         * chaîne complète, ce qui laisse notre propre contrôle de taille
         * (413) s'exécuter — celui qu'on veut exercer ici. */
        $bigJson = '{"full":{"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}},"padding":"' . str_repeat('a', 25700) . '"}}';
        $compressed = gzencode($bigJson, 9);
        self::assertNotFalse($compressed);
        $this->assertLessThan(5 * 1024, strlen($compressed), 'Pre-condition : compressed sous 5 KB');
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('trop volumineux');
        try {
            $this->decoder->decode($compressed, 'application/gzip');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(413, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 5 — JSON parsing strict ════════════════════

    public function testRejectsMalformedJson(): void
    {
        $malformed = '{"full":}';
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('JSON invalide');
        try {
            $this->decoder->decode($malformed, 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(400, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 6 — schéma minimal ═════════════════════════

    public function testRejectsMissingFullBlock(): void
    {
        $body = '{"prodOnly":{"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}}}';
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('"full"');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsFullWithoutVulnerabilities(): void
    {
        $body = '{"full":{"metadata":{"vulnerabilities":{},"dependencies":{}}}}';
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('full.vulnerabilities');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsFullWithoutMetadata(): void
    {
        $body = '{"full":{"vulnerabilities":{}}}';
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('full.metadata');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsFullMetadataWithoutDependencies(): void
    {
        $body = '{"full":{"vulnerabilities":{},"metadata":{"vulnerabilities":{}}}}';
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('full.metadata.dependencies');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testAcceptsProdOnlyWhenValid(): void
    {
        $body = '{"full":{"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}},"prodOnly":{"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}}}';
        $result = $this->decoder->decode($body, 'application/json');
        $this->assertSame(NpmProcessingQueue::CONTENT_JSON, $result->contentType);
    }

    public function testRejectsInvalidProdOnly(): void
    {
        $body = '{"full":{"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}},"prodOnly":{"vulnerabilities":{}}}';
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('prodOnly.metadata');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testAcceptsInstalledWhenValid(): void
    {
        $body = '{"full":{"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}},"installed":{"dependencies":{"body-parser":{"version":"1.19.0"}}}}';
        $result = $this->decoder->decode($body, 'application/json');
        $this->assertSame(NpmProcessingQueue::CONTENT_JSON, $result->contentType);
    }

    public function testRejectsInstalledWhenNotAnObject(): void
    {
        $body = '{"full":{"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}},"installed":"oops"}';
        $this->expectException(NpmAuditPayloadDecodeException::class);
        $this->expectExceptionMessage('"installed"');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (NpmAuditPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 7 — sha256 ═════════════════════════════════

    public function testSha256IsDeterministic(): void
    {
        $r1 = $this->decoder->decode(self::VALID_JSON, 'application/json');
        $r2 = $this->decoder->decode(self::VALID_JSON, 'application/json');
        $this->assertSame($r1->sha256, $r2->sha256);
        $this->assertSame(64, strlen($r1->sha256));
    }

    public function testSha256SameAcrossCompressionFormats(): void
    {
        $r1 = $this->decoder->decode(self::VALID_JSON, 'application/json');
        $compressed = gzencode(self::VALID_JSON, 6);
        self::assertNotFalse($compressed);
        $r2 = $this->decoder->decode($compressed, 'application/gzip');
        $this->assertSame($r1->sha256, $r2->sha256);
    }

    private function buildZipWithSingleFile(string $name, string $content): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'npmaudit-test-zip-');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString($name, $content);
        $zip->close();
        $body = file_get_contents($tmp);
        @unlink($tmp);
        self::assertNotFalse($body);
        return $body;
    }
}
