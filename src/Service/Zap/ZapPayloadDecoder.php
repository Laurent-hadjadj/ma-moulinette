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

namespace App\Service\Zap;

use App\Entity\ZapProcessingQueue;

/**
 * [Description ZapPayloadDecoder]
 * MODIF 2026-10-13 : decode et valide les payloads ZAP (rapport
 * `traditional-json`), sur le gabarit PayloadDecoder (Dependency-Check).
 *
 * Pipeline en 7 contrôles :
 *   1. Taille brute (max 10 MB par défaut)
 *   2. Detection format par magic bytes vs Content-Type
 *   3. Decompression contrôlée (zip-bomb safe)
 *   4. Taille décompressée (max 50 MB par défaut)
 *   5. Parsing JSON strict (JSON_THROW_ON_ERROR)
 *   6. Validation schema minimal ZAP (`@version` + `site[]` non vide, 1 seul
 *      site, chaque site a `alerts[]`)
 *   7. sha256 du JSON décompresse (idempotence + traçabilité)
 *
 * Contrairement à PayloadDecoder (DC), aucune identification projet n'est
 * extraite du payload : le rapport ZAP n'en contient pas (fournie par la CI
 * via les en-têtes d'upload X-Maven-Key/X-Project-Version).
 *
 * Résultat : DTO ZapPayloadDecodeResult ou exception ZapPayloadDecodeException.
 */
class ZapPayloadDecoder
{
    public function __construct(
        private int $maxRawSize     = 10 * 1024 * 1024, // 10 MB
        private int $maxDecodedSize = 50 * 1024 * 1024, // 50 MB
    ) {}

    /**
     * [Description for decode]
     * Decode un payload depuis les bytes bruts recus.
     *
     * @param string $rawBody
     * @param string|null $declaredContentType
     * @throws ZapPayloadDecodeException si une etape echoue
     *
     * @return ZapPayloadDecodeResult
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function decode(string $rawBody, ?string $declaredContentType): ZapPayloadDecodeResult
    {
        // ------- 1. Taille brute --------------------------------------------
        $rawSize = strlen($rawBody);
        if ($rawSize === 0) {
            throw new ZapPayloadDecodeException(400, 'Payload vide.');
        }
        if ($rawSize > $this->maxRawSize) {
            throw new ZapPayloadDecodeException(
                413,
                sprintf('Payload trop volumineux : %d octets (max %d).', $rawSize, $this->maxRawSize)
            );
        }

        // ------- 2. Detection format ----------------------------------------
        $detectedFormat = $this->detectFormat($rawBody);
        $this->assertFormatMatchesContentType($detectedFormat, $declaredContentType);

        // ------- 3 + 4. Decompression contrôlée -----------------------------
        $jsonString = match ($detectedFormat) {
            ZapProcessingQueue::CONTENT_JSON => $rawBody,
            ZapProcessingQueue::CONTENT_GZIP => $this->decodeGzip($rawBody),
            ZapProcessingQueue::CONTENT_ZIP  => $this->decodeZip($rawBody),
            ZapProcessingQueue::CONTENT_TGZ  => $this->decodeTgz($rawBody),
            default => throw new ZapPayloadDecodeException(415, 'Format inconnu.'),
        };

        $decodedSize = strlen($jsonString);
        if ($decodedSize > $this->maxDecodedSize) {
            throw new ZapPayloadDecodeException(
                413,
                sprintf('Payload décompresse trop volumineux : %d octets (max %d).', $decodedSize, $this->maxDecodedSize)
            );
        }

        // ------- 5. Parsing JSON strict -------------------------------------
        try {
            $parsed = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ZapPayloadDecodeException(400, 'JSON invalide : ' . $e->getMessage(), $e);
        }
        if (!is_array($parsed)) {
            throw new ZapPayloadDecodeException(400, 'JSON racine doit être un objet.');
        }

        // ------- 6. Validation schema minimal -------------------------------
        $this->assertZapReportSchema($parsed);

        // ------- 7. sha256 -------------------------------------------------
        $sha256 = hash('sha256', $jsonString);

        return new ZapPayloadDecodeResult(
            jsonString:  $jsonString,
            decodedSize: $decodedSize,
            contentType: $detectedFormat,
            sha256:      $sha256,
        );
    }

    /**
     * [Description for detectFormat]
     * Detection par magic bytes.
     *
     * @param string $body
     *
     * @return string
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function detectFormat(string $body): string
    {
        // gzip : 1F 8B
        if (strlen($body) >= 2 && $body[0] === "\x1F" && $body[1] === "\x8B") {
            return $this->probeGzipForTar($body)
                ? ZapProcessingQueue::CONTENT_TGZ
                : ZapProcessingQueue::CONTENT_GZIP;
        }
        // zip : 50 4B 03 04 (local file header)
        if (strlen($body) >= 4 && substr($body, 0, 4) === "PK\x03\x04") {
            return ZapProcessingQueue::CONTENT_ZIP;
        }
        // json : { ou [ apres skip whitespace
        $trimmed = ltrim($body);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            return ZapProcessingQueue::CONTENT_JSON;
        }
        throw new ZapPayloadDecodeException(415, 'Format non reconnu (magic bytes).');
    }

    /**
     * [Description for probeGzipForTar]
     * Probe rapide : tar inside gzip ?
     *
     * @param string $body
     *
     * @return bool
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function probeGzipForTar(string $body): bool
    {
        $decoded = @gzdecode($body, 512);
        if ($decoded === false || strlen($decoded) < 263) {
            return false;
        }
        return substr($decoded, 257, 5) === 'ustar';
    }

    /**
     * [Description for assertFormatMatchesContentType]
     * Vérifie la coherence Content-Type vs format détecté. Tolerant : si
     * Content-Type est absent, on fait confiance aux magic bytes.
     *
     * @param string $detected
     * @param string|null $declared
     *
     * @return void
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function assertFormatMatchesContentType(string $detected, ?string $declared): void
    {
        if ($declared === null || $declared === '') {
            return;
        }
        $declared = strtolower(trim(explode(';', $declared)[0]));
        $expectedDeclarations = [
            ZapProcessingQueue::CONTENT_JSON => ['application/json', 'text/json'],
            ZapProcessingQueue::CONTENT_GZIP => ['application/gzip', 'application/x-gzip'],
            ZapProcessingQueue::CONTENT_ZIP  => ['application/zip', 'application/x-zip-compressed'],
            ZapProcessingQueue::CONTENT_TGZ  => ['application/gzip', 'application/x-gzip', 'application/x-tar', 'application/x-compressed-tar'],
        ];
        if (!in_array($declared, $expectedDeclarations[$detected] ?? [], true)) {
            throw new ZapPayloadDecodeException(
                415,
                sprintf('Content-Type "%s" incompatible avec le format detecte "%s".', $declared, $detected)
            );
        }
    }

    /**
     * [Description for decodeGzip]
     *
     * @param string $body
     *
     * @return string
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeGzip(string $body): string
    {
        $decoded = @gzdecode($body, $this->maxDecodedSize);
        if ($decoded === false) {
            throw new ZapPayloadDecodeException(422, 'Décompression gzip échouée (format corrompu).');
        }
        return $decoded;
    }

    /**
     * [Description for decodeZip]
     *
     * @param string $body
     *
     * @return string
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeZip(string $body): string
    {
        $tmpZip = tempnam(sys_get_temp_dir(), 'zap-zip-');
        if ($tmpZip === false) {
            throw new ZapPayloadDecodeException(500, 'Impossible de créer le fichier temporaire pour zip.');
        }
        try {
            file_put_contents($tmpZip, $body);
            $zip = new \ZipArchive();
            $code = $zip->open($tmpZip);
            if ($code !== true) {
                throw new ZapPayloadDecodeException(422, sprintf('Archive ZIP invalide (code %d).', $code));
            }
            try {
                if ($zip->numFiles !== 1) {
                    throw new ZapPayloadDecodeException(
                        422,
                        sprintf('Archive ZIP doit contenir exactement 1 fichier (%d trouvés).', $zip->numFiles)
                    );
                }
                $name = $zip->getNameIndex(0);
                if ($name === false || !str_ends_with(strtolower($name), '.json')) {
                    throw new ZapPayloadDecodeException(422, 'L\'archive ZIP doit contenir un fichier .json.');
                }
                $stream = $zip->getStream($name);
                if ($stream === false) {
                    throw new ZapPayloadDecodeException(422, 'Lecture du fichier dans le ZIP échouée.');
                }
                $content = $this->readStreamWithLimit($stream, $this->maxDecodedSize);
                fclose($stream);
                return $content;
            } finally {
                $zip->close();
            }
        } finally {
            @unlink($tmpZip);
        }
    }

    /**
     * [Description for decodeTgz]
     *
     * @param string $body
     *
     * @return string
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeTgz(string $body): string
    {
        $tar = $this->decodeGzip($body);

        $tmpTar = tempnam(sys_get_temp_dir(), 'zap-tar-') . '.tar';
        try {
            file_put_contents($tmpTar, $tar);
            try {
                $phar = new \PharData($tmpTar);
            } catch (\Throwable $e) {
                throw new ZapPayloadDecodeException(422, 'Archive TAR invalide : ' . $e->getMessage(), $e);
            }
            $jsonContent = null;
            foreach (new \RecursiveIteratorIterator($phar) as $file) {
                /** @var \PharFileInfo $file */
                if (str_ends_with(strtolower($file->getFilename()), '.json')) {
                    if ($jsonContent !== null) {
                        throw new ZapPayloadDecodeException(422, 'L\'archive TGZ doit contenir un seul fichier .json.');
                    }
                    $size = $file->getSize();
                    if ($size > $this->maxDecodedSize) {
                        throw new ZapPayloadDecodeException(413, 'Fichier extrait trop volumineux.');
                    }
                    $jsonContent = $file->getContent();
                }
            }
            if ($jsonContent === null) {
                throw new ZapPayloadDecodeException(422, 'Aucun fichier .json trouve dans l\'archive TGZ.');
            }
            return $jsonContent;
        } finally {
            @unlink($tmpTar);
        }
    }

    /**
     * [Description for readStreamWithLimit]
     * Lit un stream en abandonnant si la limite est dépassée (anti-zip-bomb).
     *
     * @param resource $stream
     * @param int $limit
     *
     * @return string
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function readStreamWithLimit($stream, int $limit): string
    {
        $buffer = '';
        $chunkSize = 65536; // 64 KB
        while (!feof($stream)) {
            $chunk = fread($stream, $chunkSize);
            if ($chunk === false) {
                throw new ZapPayloadDecodeException(422, 'Erreur de lecture du stream.');
            }
            $buffer .= $chunk;
            if (strlen($buffer) > $limit) {
                throw new ZapPayloadDecodeException(413, 'Décompression dépasse la limite (zip-bomb suspecte).');
            }
        }
        return $buffer;
    }

    /**
     * [Description for assertZapReportSchema]
     * Validation du schema minimal d'un rapport ZAP (template `traditional-json`).
     *
     * Contrairement a PayloadDecoder (DC), ne retourne rien : aucune
     * identification projet n'existe dans le payload ZAP.
     *
     * @param array<string, mixed> $parsed
     *
     * @return void
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function assertZapReportSchema(array $parsed): void
    {
        if (!isset($parsed['@version']) || !is_string($parsed['@version']) || trim($parsed['@version']) === '') {
            throw new ZapPayloadDecodeException(422, 'Champ "@version" absent ou vide.');
        }
        if (!isset($parsed['site']) || !is_array($parsed['site']) || $parsed['site'] === []) {
            throw new ZapPayloadDecodeException(422, 'Bloc "site" absent, invalide ou vide.');
        }
        if (count($parsed['site']) > 1) {
            throw new ZapPayloadDecodeException(
                422,
                sprintf('Rapport multi-site (%d sites) non supporté (hors périmètre v1).', count($parsed['site']))
            );
        }
        $site = $parsed['site'][0];
        if (!is_array($site) || !isset($site['alerts']) || !is_array($site['alerts'])) {
            throw new ZapPayloadDecodeException(422, 'Bloc "site[0].alerts" absent ou invalide.');
        }
    }
}
