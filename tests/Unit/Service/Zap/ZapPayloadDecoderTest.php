<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
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
        self::assertNotFalse($gz);
        $result = $this->decoder->decode($gz, 'application/gzip');
        $this->assertSame(ZapProcessingQueue::CONTENT_GZIP, $result->contentType);
        $this->assertSame(self::VALID_JSON, $result->jsonString);
    }

    public function testToleratesContentTypeWithCharset(): void
    {
        $result = $this->decoder->decode(self::VALID_JSON, 'application/json; charset=utf-8');
        $this->assertSame(ZapProcessingQueue::CONTENT_JSON, $result->contentType);
    }

    public function testRejectsCorruptedGzip(): void
    {
        // Magic bytes gzip valides, corps corrompu derrière -> gzdecode() renvoie false.
        $corrupted = "\x1F\x8B\x08\x00" . str_repeat("\x00", 20);
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('corrompu');
        try {
            $this->decoder->decode($corrupted, 'application/gzip');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testAcceptsValidZip(): void
    {
        $zipBody = $this->buildZipWithSingleFile('report.json', self::VALID_JSON);
        $result = $this->decoder->decode($zipBody, 'application/zip');
        $this->assertSame(ZapProcessingQueue::CONTENT_ZIP, $result->contentType);
        $this->assertSame(self::VALID_JSON, $result->jsonString);
    }

    public function testRejectsZipWithMultipleFiles(): void
    {
        $zipBody = $this->buildZipWithFiles(['report.json' => self::VALID_JSON, 'extra.txt' => 'autre']);
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('exactement 1 fichier');
        try {
            $this->decoder->decode($zipBody, 'application/zip');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsZipWithNonJsonFile(): void
    {
        $zipBody = $this->buildZipWithSingleFile('readme.txt', 'contenu texte');
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('.json');
        try {
            $this->decoder->decode($zipBody, 'application/zip');
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testAcceptsValidTgz(): void
    {
        $tgzBody = $this->buildTgzWithFiles(['report.json' => self::VALID_JSON]);
        $result = $this->decoder->decode($tgzBody, null);
        $this->assertSame(ZapProcessingQueue::CONTENT_TGZ, $result->contentType);
        $this->assertSame(self::VALID_JSON, $result->jsonString);
    }

    public function testRejectsTgzWithMultipleJsonFiles(): void
    {
        $tgzBody = $this->buildTgzWithFiles(['a.json' => self::VALID_JSON, 'b.json' => self::VALID_JSON]);
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('un seul fichier .json');
        try {
            $this->decoder->decode($tgzBody, null);
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsTgzWithoutJsonFile(): void
    {
        $tgzBody = $this->buildTgzWithFiles(['readme.txt' => 'rien ici']);
        $this->expectException(ZapPayloadDecodeException::class);
        $this->expectExceptionMessage('Aucun fichier .json');
        try {
            $this->decoder->decode($tgzBody, null);
        } catch (ZapPayloadDecodeException $e) {
            $this->assertSame(422, $e->httpStatus);
            throw $e;
        }
    }

    public function testRejectsDecodedSizeOverLimit(): void
    {
        // Même marge que PayloadDecoderTest (DC) : un dépassement trop large fait
        // échouer gzdecode() lui-même (retourne false, cf. anti zip-bomb "dur"),
        // avant d'atteindre le contrôle explicite de taille décodée — il faut
        // rester proche de la limite pour exercer CE contrôle précis.
        $big = '{"reportSchema":"1.1","padding":"' . str_repeat('a', 26 * 1024) . '"}';
        $compressed = gzencode($big, 9);
        self::assertNotFalse($compressed);
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

    // ═════════════════════ Helpers de construction d'archives ═════════════════

    private function buildZipWithSingleFile(string $name, string $content): string
    {
        return $this->buildZipWithFiles([$name => $content]);
    }

    /**
     * @param array<string, string> $files
     */
    private function buildZipWithFiles(array $files): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'zap-test-zip-');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();
        $body = file_get_contents($tmp);
        @unlink($tmp);
        self::assertNotFalse($body);
        return $body;
    }

    /**
     * Construit une archive .tar.gz contenant les fichiers donnés - même
     * format que produit la CI pour un rapport ZAP volumineux.
     *
     * Construit le TAR octet par octet (format USTAR minimal) plutôt que via
     * `PharData::addFromString()`/`compress()` : ces 2 méthodes sont des
     * écritures Phar, bloquées par `phar.readonly=1` (valeur par défaut du
     * php.ini de ce poste, et donc de toute exécution standard de la suite
     * via `composer test:unit`) - sans ce détour, le test échouait
     * silencieusement (archive vide/tronquée) sous l'invocation normale,
     * tout en passant sous `-d phar.readonly=0` explicite. `PharData` reste
     * utilisé en LECTURE (comme le fait `ZapPayloadDecoder::decodeTgz()`
     * lui-même), ce qui ne nécessite jamais `phar.readonly=0`.
     *
     * @param array<string, string> $files
     */
    private function buildTgzWithFiles(array $files): string
    {
        $tar = '';
        foreach ($files as $name => $content) {
            $tar .= $this->buildTarHeader($name, strlen($content));
            $tar .= $content;
            $padding = (512 - (strlen($content) % 512)) % 512;
            $tar .= str_repeat("\0", $padding);
        }
        $tar .= str_repeat("\0", 1024); // 2 blocs nuls = fin d'archive TAR.

        $gz = gzencode($tar, 6);
        self::assertNotFalse($gz);
        return $gz;
    }

    /**
     * En-tête USTAR minimal (512 octets) pour un seul fichier régulier.
     */
    private function buildTarHeader(string $name, int $size): string
    {
        $name = substr($name, 0, 100);
        $header = $name . str_repeat("\0", 100 - strlen($name));
        $header .= sprintf('%07o', 0o644) . "\0";    // mode
        $header .= sprintf('%07o', 0) . "\0";         // uid
        $header .= sprintf('%07o', 0) . "\0";         // gid
        $header .= sprintf('%011o', $size) . "\0";    // size
        $header .= sprintf('%011o', time()) . "\0";   // mtime
        $header .= str_repeat(' ', 8);                // checksum (placeholder)
        $header .= '0';                               // typeflag : fichier régulier
        $header .= str_repeat("\0", 100);             // linkname
        $header .= "ustar\0";                         // magic
        $header .= '00';                              // version
        $header .= str_repeat("\0", 32);              // uname
        $header .= str_repeat("\0", 32);              // gname
        $header .= sprintf('%07o', 0) . "\0";         // devmajor
        $header .= sprintf('%07o', 0) . "\0";         // devminor
        $header .= str_repeat("\0", 155);             // prefix
        $header .= str_repeat("\0", 12);              // padding -> 512 octets

        $sum = 0;
        foreach (str_split($header) as $byte) {
            $sum += ord($byte);
        }
        $checksum = sprintf('%06o', $sum) . "\0 ";
        return substr_replace($header, $checksum, 148, 8);
    }
}
