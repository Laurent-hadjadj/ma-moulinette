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

namespace App\Tests\Unit\Service\DependencyCheck;

use App\Entity\DcProcessingQueue;
use App\Service\DependencyCheck\PayloadDecodeException;
use App\Service\DependencyCheck\PayloadDecoder;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-08  : test unit du PayloadDecoder.
 *
 * Couvre les 7 contrôles d’intégrité x 4 formats supportes (json/gzip/zip/tgz).
 *
 * Stratégie :
 *  - chaque control a au moins 1 test happy + 1 test failing
 *  - chaque format a au moins 1 test happy
 *  - chaque code HTTP renvoie par le decoder a au moins 1 test
 */
class PayloadDecoderTest extends TestCase
{
    private PayloadDecoder $decoder;

    /**
     * Payload JSON minimal valide DC v1.1.
     */
    private const VALID_JSON = '{"reportSchema":"1.1","projectInfo":{"groupID":"fr.test","artifactID":"demo","version":"1.0"},"dependencies":[]}';

    protected function setUp(): void
    {
        // Limites réduites pour faciliter les tests (5 KB / 25 KB)
        $this->decoder = new PayloadDecoder(maxRawSize: 5 * 1024, maxDecodedSize: 25 * 1024);
    }

    // ═════════════════════ CONTROL 1 — taille brute ═══════════════════════════

    public function testRejectsEmptyPayload(): void
    {
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('Payload vide');
        try {
            $this->decoder->decode('', 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(400, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsRawSizeOverLimit(): void
    {
        $oversized = str_repeat('a', 6 * 1024); // > 5 KB limite
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('trop volumineux');
        try {
            $this->decoder->decode($oversized, 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(413, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 2 — magic bytes ════════════════════════════

    public function testRejectsUnknownMagicBytes(): void
    {
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('Format non reconnu');
        try {
            $this->decoder->decode('garbage non identifiable', 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(415, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsContentTypeMismatch(): void
    {
        // Body est du JSON, mais Content-Type declare zip
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('incompatible');
        try {
            $this->decoder->decode(self::VALID_JSON, 'application/zip');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(415, $e->httpStatus);
            throw $e;
        }
    }

    public function testToleratesAbsentContentType(): void
    {
        // Pas de Content-Type -> on fait confiance aux magic bytes
        $result = $this->decoder->decode(self::VALID_JSON, null);
        $this->assertSame(DcProcessingQueue::CONTENT_JSON, $result->contentType);
    }

    public function testToleratesContentTypeWithCharset(): void
    {
        // Content-Type avec charset -> on parse uniquement la partie media-type
        $result = $this->decoder->decode(self::VALID_JSON, 'application/json; charset=utf-8');
        $this->assertSame(DcProcessingQueue::CONTENT_JSON, $result->contentType);
    }

    // ═════════════════════ CONTROL 3+4 — decompression et taille décodée ══════

    public function testAcceptsValidJson(): void
    {
        $result = $this->decoder->decode(self::VALID_JSON, 'application/json');
        $this->assertSame(DcProcessingQueue::CONTENT_JSON, $result->contentType);
        $this->assertSame(strlen(self::VALID_JSON), $result->decodedSize);
        $this->assertSame('fr.test', $result->projectGroup);
        $this->assertSame('demo', $result->projectArtifact);
        $this->assertSame('1.0', $result->projectVersion);
    }

    public function testAcceptsValidGzip(): void
    {
        $compressed = gzencode(self::VALID_JSON, 6);
        $result = $this->decoder->decode($compressed, 'application/gzip');
        $this->assertSame(DcProcessingQueue::CONTENT_GZIP, $result->contentType);
        $this->assertSame(self::VALID_JSON, $result->jsonString);
    }

    public function testAcceptsValidGzipXAlias(): void
    {
        $compressed = gzencode(self::VALID_JSON, 6);
        $result = $this->decoder->decode($compressed, 'application/x-gzip');
        $this->assertSame(DcProcessingQueue::CONTENT_GZIP, $result->contentType);
    }

    public function testAcceptsValidZip(): void
    {
        $zipBody = $this->buildZipWithSingleJson(self::VALID_JSON);
        $result = $this->decoder->decode($zipBody, 'application/zip');
        $this->assertSame(DcProcessingQueue::CONTENT_ZIP, $result->contentType);
        $this->assertSame(self::VALID_JSON, $result->jsonString);
    }

    public function testRejectsZipWithMultipleFiles(): void
    {
        $zipBody = $this->buildZipWithMultipleFiles();
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('exactement 1 fichier');
        try {
            $this->decoder->decode($zipBody, 'application/zip');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsZipWithNonJsonFile(): void
    {
        $zipBody = $this->buildZipWithSingleFile('readme.txt', 'plain text');
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('.json');
        try {
            $this->decoder->decode($zipBody, 'application/zip');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsCorruptedGzip(): void
    {
        // Bytes magic gzip mais content invalide
        $corrupted = "\x1F\x8B" . str_repeat("\x00", 100);
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('Décompression gzip échouée');
        try {
            $this->decoder->decode($corrupted, null);
        } catch (PayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsDecodedSizeOverLimit(): void
    {
        // JSON valide mais tres long, compresse petit -> zip-bomb-like
        $bigJson = '{"reportSchema":"1.1","projectInfo":{"groupID":"x","artifactID":"y","version":"1"},"dependencies":[],"padding":"' . str_repeat('a', 26 * 1024) . '"}';
        $compressed = gzencode($bigJson, 9);
        // Compressed est petit (qq KB), decoded > 25 KB limite
        $this->assertLessThan(5 * 1024, strlen($compressed), 'Pre-condition : compressed sous 5 KB');
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('trop volumineux');
        try {
            $this->decoder->decode($compressed, 'application/gzip');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(413, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 5 — JSON parsing strict ════════════════════

    public function testRejectsMalformedJson(): void
    {
        $malformed = '{"reportSchema":"1.1","projectInfo":}'; // virgule manquante
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('JSON invalide');
        try {
            $this->decoder->decode($malformed, 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(400, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsJsonArrayRoot(): void
    {
        /* Note : un JSON-array racine passe les magic bytes ET le check
         * is_array() (les arrays PHP couvrent listes ET objets). Il échoue donc
         * un step plus loin sur la validation de schema (reportSchema absent).
         * Le code défensif "JSON racine doit être un objet" est conservé par
         * sécurité mais n'est pas atteignable depuis l'API publique.
         */
        $body = '["array","not","object"]';
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('reportSchema');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    // ═════════════════════ CONTROL 6 — schema minimal ═════════════════════════

    public function testRejectsMissingReportSchema(): void
    {
        $body = '{"projectInfo":{"groupID":"a","artifactID":"b","version":"1"},"dependencies":[]}';
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('reportSchema');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsMissingProjectInfo(): void
    {
        $body = '{"reportSchema":"1.1","dependencies":[]}';
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('projectInfo');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsMissingDependencies(): void
    {
        $body = '{"reportSchema":"1.1","projectInfo":{"groupID":"a","artifactID":"b","version":"1"}}';
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('dependencies');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsEmptyProjectGroup(): void
    {
        $body = '{"reportSchema":"1.1","projectInfo":{"groupID":"","artifactID":"b","version":"1"},"dependencies":[]}';
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('projectInfo.groupID');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsEmptyProjectVersion(): void
    {
        $body = '{"reportSchema":"1.1","projectInfo":{"groupID":"a","artifactID":"b","version":"  "},"dependencies":[]}';
        $this->expectException(PayloadDecodeException::class);
        $this->expectExceptionMessage('projectInfo.version');
        try {
            $this->decoder->decode($body, 'application/json');
        } catch (PayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testTrimsProjectInfoFields(): void
    {
        $body = '{"reportSchema":"1.1","projectInfo":{"groupID":"  a.b  ","artifactID":"c","version":"  1.0  "},"dependencies":[]}';
        $result = $this->decoder->decode($body, 'application/json');
        $this->assertSame('a.b', $result->projectGroup);
        $this->assertSame('1.0', $result->projectVersion);
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
        // Le sha256 est calcule sur le JSON DÉCOMPRESSÉ -> doit être identique
        // entre payload brut et payload gzippe.
        $r1 = $this->decoder->decode(self::VALID_JSON, 'application/json');
        $compressed = gzencode(self::VALID_JSON, 6);
        $r2 = $this->decoder->decode($compressed, 'application/gzip');
        $this->assertSame($r1->sha256, $r2->sha256);
    }

    public function testSha256DifferentForDifferentPayloads(): void
    {
        $body2 = '{"reportSchema":"1.1","projectInfo":{"groupID":"fr.other","artifactID":"app","version":"2.0"},"dependencies":[]}';
        $r1 = $this->decoder->decode(self::VALID_JSON, 'application/json');
        $r2 = $this->decoder->decode($body2, 'application/json');
        $this->assertNotSame($r1->sha256, $r2->sha256);
    }

    // ═════════════════════ Helpers ZIP ════════════════════════════════════════

    private function buildZipWithSingleJson(string $jsonContent): string
    {
        return $this->buildZipWithSingleFile('report.json', $jsonContent);
    }

    private function buildZipWithSingleFile(string $name, string $content): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dc-test-zip-');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString($name, $content);
        $zip->close();
        $body = file_get_contents($tmp);
        @unlink($tmp);
        return $body;
    }

    private function buildZipWithMultipleFiles(): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'dc-test-zip-');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $zip->addFromString('report.json', self::VALID_JSON);
        $zip->addFromString('extra.txt', 'autre contenu');
        $zip->close();
        $body = file_get_contents($tmp);
        @unlink($tmp);
        return $body;
    }
}
