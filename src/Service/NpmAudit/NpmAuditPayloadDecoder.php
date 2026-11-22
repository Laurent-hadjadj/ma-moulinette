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

namespace App\Service\NpmAudit;

use App\Entity\NpmProcessingQueue;

/**
 * [Description NpmAuditPayloadDecoder]
 * Décode et valide les payloads npm audit — même pipeline anti-abus en 7
 * contrôles que PayloadDecoder (DC), schéma de validation adapté :
 *   1. Taille brute (max 10 MB par défaut)
 *   2. Détection format par magic bytes vs Content-Type
 *   3+4. Décompression contrôlée (zip-bomb safe), taille décompressée (max 50 MB)
 *   5. Parsing JSON strict (JSON_THROW_ON_ERROR)
 *   6. Validation schéma minimal : {"full": {...npm audit --json...},
 *      "prodOnly"?: {...}, "installed"?: {...npm ls --all --json...}} —
 *      PAS de projectInfo a valider ici (vient des headers HTTP, lus par le
 *      contrôleur). "installed" n'a pas de schéma strict imposé (sa forme
 *      varie selon la version de npm) : juste un objet, la lecture réelle
 *      (NpmAuditIngester) reste défensive champ par champ.
 *   7. sha256 du JSON décompressé (idempotence + traçabilité)
 */
class NpmAuditPayloadDecoder
{
    public function __construct(
        private int $maxRawSize     = 10 * 1024 * 1024,    // 10 MB
        private int $maxDecodedSize = 50 * 1024 * 1024,    // 50 MB
    ) {}

    public function decode(string $rawBody, ?string $declaredContentType): NpmAuditPayloadDecodeResult
    {
        // ------- 1. Taille brute --------------------------------------------
        $rawSize = strlen($rawBody);
        if ($rawSize === 0) {
            throw new NpmAuditPayloadDecodeException(400, 'Payload vide.');
        }
        if ($rawSize > $this->maxRawSize) {
            throw new NpmAuditPayloadDecodeException(
                413,
                sprintf('Payload trop volumineux : %d octets (max %d).', $rawSize, $this->maxRawSize)
            );
        }

        // ------- 2. Detection format ----------------------------------------
        $detectedFormat = $this->detectFormat($rawBody);
        $this->assertFormatMatchesContentType($detectedFormat, $declaredContentType);

        // ------- 3 + 4. Decompression contrôlée -----------------------------
        $jsonString = match ($detectedFormat) {
            NpmProcessingQueue::CONTENT_JSON => $rawBody,
            NpmProcessingQueue::CONTENT_GZIP => $this->decodeGzip($rawBody),
            NpmProcessingQueue::CONTENT_ZIP  => $this->decodeZip($rawBody),
            NpmProcessingQueue::CONTENT_TGZ  => $this->decodeTgz($rawBody),
            default => throw new NpmAuditPayloadDecodeException(415, 'Format inconnu.'),
        };

        $decodedSize = strlen($jsonString);
        if ($decodedSize > $this->maxDecodedSize) {
            throw new NpmAuditPayloadDecodeException(
                413,
                sprintf('Payload décompressé trop volumineux : %d octets (max %d).', $decodedSize, $this->maxDecodedSize)
            );
        }

        // ------- 5. Parsing JSON strict -------------------------------------
        try {
            $parsed = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new NpmAuditPayloadDecodeException(400, 'JSON invalide : ' . $e->getMessage(), $e);
        }
        if (!is_array($parsed)) {
            throw new NpmAuditPayloadDecodeException(400, 'JSON racine doit être un objet.');
        }

        // ------- 6. Validation schema minimal -------------------------------
        $this->validateSchema($parsed);

        // ------- 7. sha256 -------------------------------------------------
        $sha256 = hash('sha256', $jsonString);

        return new NpmAuditPayloadDecodeResult(
            jsonString:  $jsonString,
            decodedSize: $decodedSize,
            contentType: $detectedFormat,
            sha256:      $sha256,
        );
    }

    private function detectFormat(string $body): string
    {
        if (strlen($body) >= 2 && $body[0] === "\x1F" && $body[1] === "\x8B") {
            return $this->probeGzipForTar($body)
                ? NpmProcessingQueue::CONTENT_TGZ
                : NpmProcessingQueue::CONTENT_GZIP;
        }
        if (strlen($body) >= 4 && substr($body, 0, 4) === "PK\x03\x04") {
            return NpmProcessingQueue::CONTENT_ZIP;
        }
        $trimmed = ltrim($body);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            return NpmProcessingQueue::CONTENT_JSON;
        }
        throw new NpmAuditPayloadDecodeException(415, 'Format non reconnu (magic bytes).');
    }

    private function probeGzipForTar(string $body): bool
    {
        $decoded = @gzdecode($body, 512);
        if ($decoded === false || strlen($decoded) < 263) {
            return false;
        }
        return substr($decoded, 257, 5) === 'ustar';
    }

    private function assertFormatMatchesContentType(string $detected, ?string $declared): void
    {
        if ($declared === null || $declared === '') {
            return;
        }
        $declared = strtolower(trim(explode(';', $declared)[0]));
        $expectedDeclarations = [
            NpmProcessingQueue::CONTENT_JSON => ['application/json', 'text/json'],
            NpmProcessingQueue::CONTENT_GZIP => ['application/gzip', 'application/x-gzip'],
            NpmProcessingQueue::CONTENT_ZIP  => ['application/zip', 'application/x-zip-compressed'],
            NpmProcessingQueue::CONTENT_TGZ  => ['application/gzip', 'application/x-gzip', 'application/x-tar', 'application/x-compressed-tar'],
        ];
        if (!in_array($declared, $expectedDeclarations[$detected] ?? [], true)) {
            throw new NpmAuditPayloadDecodeException(
                415,
                sprintf('Content-Type "%s" incompatible avec le format détecté "%s".', $declared, $detected)
            );
        }
    }

    private function decodeGzip(string $body): string
    {
        $decoded = @gzdecode($body, $this->maxDecodedSize);
        if ($decoded === false) {
            throw new NpmAuditPayloadDecodeException(422, 'Décompression gzip échouée (format corrompu).');
        }
        return $decoded;
    }

    private function decodeZip(string $body): string
    {
        $tmpZip = tempnam(sys_get_temp_dir(), 'npmaudit-zip-');
        if ($tmpZip === false) {
            throw new NpmAuditPayloadDecodeException(500, 'Impossible de créer le fichier temporaire pour zip.');
        }
        try {
            file_put_contents($tmpZip, $body);
            $zip = new \ZipArchive();
            $code = $zip->open($tmpZip);
            if ($code !== true) {
                throw new NpmAuditPayloadDecodeException(422, sprintf('Archive ZIP invalide (code %d).', $code));
            }
            try {
                if ($zip->numFiles !== 1) {
                    throw new NpmAuditPayloadDecodeException(
                        422,
                        sprintf('Archive ZIP doit contenir exactement 1 fichier (%d trouvés).', $zip->numFiles)
                    );
                }
                $name = $zip->getNameIndex(0);
                if ($name === false || !str_ends_with(strtolower($name), '.json')) {
                    throw new NpmAuditPayloadDecodeException(422, 'L\'archive ZIP doit contenir un fichier .json.');
                }
                $stream = $zip->getStream($name);
                if ($stream === false) {
                    throw new NpmAuditPayloadDecodeException(422, 'Lecture du fichier dans le ZIP échouée.');
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

    private function decodeTgz(string $body): string
    {
        $tar = $this->decodeGzip($body);

        $tmpTar = tempnam(sys_get_temp_dir(), 'npmaudit-tar-') . '.tar';
        try {
            file_put_contents($tmpTar, $tar);
            try {
                $phar = new \PharData($tmpTar);
            } catch (\Throwable $e) {
                throw new NpmAuditPayloadDecodeException(422, 'Archive TAR invalide : ' . $e->getMessage(), $e);
            }
            $jsonContent = null;
            foreach (new \RecursiveIteratorIterator($phar) as $file) {
                /** @var \PharFileInfo $file */
                if (str_ends_with(strtolower($file->getFilename()), '.json')) {
                    if ($jsonContent !== null) {
                        throw new NpmAuditPayloadDecodeException(422, 'L\'archive TGZ doit contenir un seul fichier .json.');
                    }
                    $size = $file->getSize();
                    if ($size > $this->maxDecodedSize) {
                        throw new NpmAuditPayloadDecodeException(413, 'Fichier extrait trop volumineux.');
                    }
                    $jsonContent = $file->getContent();
                }
            }
            if ($jsonContent === null) {
                throw new NpmAuditPayloadDecodeException(422, 'Aucun fichier .json trouvé dans l\'archive TGZ.');
            }
            return $jsonContent;
        } finally {
            @unlink($tmpTar);
        }
    }

    /**
     * @param resource $stream
     */
    private function readStreamWithLimit($stream, int $limit): string
    {
        $buffer = '';
        $chunkSize = 65536;
        while (!feof($stream)) {
            $chunk = fread($stream, $chunkSize);
            if ($chunk === false) {
                throw new NpmAuditPayloadDecodeException(422, 'Erreur de lecture du stream.');
            }
            $buffer .= $chunk;
            if (strlen($buffer) > $limit) {
                throw new NpmAuditPayloadDecodeException(413, 'Décompression dépasse la limite (zip-bomb suspectée).');
            }
        }
        return $buffer;
    }

    /**
     * Validation du schéma minimal de l'enveloppe npm audit :
     * {"full": {...}, "prodOnly"?: {...}}, chaque rapport présent devant
     * avoir auditReportVersion + vulnerabilities (objet) + metadata
     * (vulnerabilities + dependencies, objets).
     *
     * @param array<string, mixed> $parsed
     */
    private function validateSchema(array $parsed): void
    {
        if (!isset($parsed['full']) || !is_array($parsed['full'])) {
            throw new NpmAuditPayloadDecodeException(422, 'Bloc "full" absent ou invalide.');
        }
        $this->validateAuditReport($parsed['full'], 'full');

        if (isset($parsed['prodOnly'])) {
            if (!is_array($parsed['prodOnly'])) {
                throw new NpmAuditPayloadDecodeException(422, 'Bloc "prodOnly" invalide.');
            }
            $this->validateAuditReport($parsed['prodOnly'], 'prodOnly');
        }

        // "installed" (npm ls --all --json) : optionnel, pour résoudre la
        // version réellement installée de chaque paquet. Pas de schéma
        // auditReportVersion/metadata ici (format différent de npm audit) —
        // juste un objet, validation fine déléguée à l'ingester.
        if (isset($parsed['installed']) && !is_array($parsed['installed'])) {
            throw new NpmAuditPayloadDecodeException(422, 'Bloc "installed" invalide.');
        }
    }

    /**
     * @param array<string, mixed> $report
     */
    private function validateAuditReport(array $report, string $label): void
    {
        if (!isset($report['vulnerabilities']) || !is_array($report['vulnerabilities'])) {
            throw new NpmAuditPayloadDecodeException(422, sprintf('Bloc "%s.vulnerabilities" absent ou invalide.', $label));
        }
        if (!isset($report['metadata']) || !is_array($report['metadata'])) {
            throw new NpmAuditPayloadDecodeException(422, sprintf('Bloc "%s.metadata" absent ou invalide.', $label));
        }
        if (!isset($report['metadata']['vulnerabilities']) || !is_array($report['metadata']['vulnerabilities'])) {
            throw new NpmAuditPayloadDecodeException(422, sprintf('Bloc "%s.metadata.vulnerabilities" absent ou invalide.', $label));
        }
        if (!isset($report['metadata']['dependencies']) || !is_array($report['metadata']['dependencies'])) {
            throw new NpmAuditPayloadDecodeException(422, sprintf('Bloc "%s.metadata.dependencies" absent ou invalide.', $label));
        }
    }
}
