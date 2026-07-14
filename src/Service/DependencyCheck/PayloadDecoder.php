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

namespace App\Service\DependencyCheck;

use App\Entity\DcProcessingQueue;

/**
 * [Description PayloadDecodeException]
 * MODIF 2026-05-08  : decode et valide les payloads OWASP DependencyCheck.
 *
 * Pipeline en 7 contrôles :
 *   1. Taille brute (max 10 MB par défaut)
 *   2. Detection format par magic bytes vs Content-Type
 *   3. Decompression contrôlée (zip-bomb safe)
 *   4. Taille décompressée (max 50 MB par défaut)
 *   5. Parsing JSON strict (JSON_THROW_ON_ERROR)
 *   6. Validation schema minimal (reportSchema + projectInfo + dependencies)
 *   7. sha256 du JSON décompresse (idempotence + traçabilité)
 *
 * Résultat : DTO PayloadDecodeResult ou exception PayloadDecodeException.
 */
class PayloadDecoder
{
    public function __construct(
        private int $maxRawSize        = 10 * 1024 * 1024,    // 10 MB
        private int $maxDecodedSize    = 50 * 1024 * 1024,    // 50 MB
    ) {}

    /**
     * [Description for decode]
     * Decode un payload depuis les bytes bruts recus.
     *
     * @param string $rawBody
     * @param string|null $declaredContentType
     * @throws PayloadDecodeException si une etape echoue
     *
     * @return PayloadDecodeResult
     *
     * Created at: 11/05/2026 00:09:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function decode(string $rawBody, ?string $declaredContentType): PayloadDecodeResult
    {
        // ------- 1. Taille brute --------------------------------------------
        $rawSize = strlen($rawBody);
        if ($rawSize === 0) {
            throw new PayloadDecodeException(400, 'Payload vide.');
        }
        if ($rawSize > $this->maxRawSize) {
            throw new PayloadDecodeException(
                413,
                sprintf('Payload trop volumineux : %d octets (max %d).', $rawSize, $this->maxRawSize)
            );
        }

        // ------- 2. Detection format ----------------------------------------
        $detectedFormat = $this->detectFormat($rawBody);
        $this->assertFormatMatchesContentType($detectedFormat, $declaredContentType);

        // ------- 3 + 4. Decompression contrôlée -----------------------------
        $jsonString = match ($detectedFormat) {
            DcProcessingQueue::CONTENT_JSON => $rawBody,
            DcProcessingQueue::CONTENT_GZIP => $this->decodeGzip($rawBody),
            DcProcessingQueue::CONTENT_ZIP  => $this->decodeZip($rawBody),
            DcProcessingQueue::CONTENT_TGZ  => $this->decodeTgz($rawBody),
            default => throw new PayloadDecodeException(415, 'Format inconnu.'),
        };

        $decodedSize = strlen($jsonString);
        if ($decodedSize > $this->maxDecodedSize) {
            throw new PayloadDecodeException(
                413,
                sprintf('Payload décompresse trop volumineux : %d octets (max %d).', $decodedSize, $this->maxDecodedSize)
            );
        }

        // ------- 5. Parsing JSON strict -------------------------------------
        try {
            $parsed = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new PayloadDecodeException(400, 'JSON invalide : ' . $e->getMessage(), $e);
        }
        if (!is_array($parsed)) {
            throw new PayloadDecodeException(400, 'JSON racine doit être un objet.');
        }

        // ------- 6. Validation schema minimal -------------------------------
        $project = $this->extractProjectInfo($parsed);

        // ------- 7. sha256 -------------------------------------------------
        $sha256 = hash('sha256', $jsonString);

        return new PayloadDecodeResult(
            jsonString:      $jsonString,
            decodedSize:     $decodedSize,
            contentType:     $detectedFormat,
            sha256:          $sha256,
            projectGroup:    $project['groupID'],
            projectArtifact: $project['artifactID'],
            projectVersion:  $project['version'],
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
     * Created at: 11/05/2026 00:10:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function detectFormat(string $body): string
    {
        // gzip : 1F 8B
        if (strlen($body) >= 2 && $body[0] === "\x1F" && $body[1] === "\x8B") {
            // tgz = tar dans un gzip => on regarde apres décodage gzip pour confirmer
            // Ici on retourne 'gzip' par défaut, le décodeur gzip détectera si c'est tar.
            return $this->probeGzipForTar($body)
                ? DcProcessingQueue::CONTENT_TGZ
                : DcProcessingQueue::CONTENT_GZIP;
        }
        // zip : 50 4B 03 04 (local file header)
        if (strlen($body) >= 4 && substr($body, 0, 4) === "PK\x03\x04") {
            return DcProcessingQueue::CONTENT_ZIP;
        }
        // json : { ou [ apres skip whitespace
        $trimmed = ltrim($body);
        if ($trimmed !== '' && ($trimmed[0] === '{' || $trimmed[0] === '[')) {
            return DcProcessingQueue::CONTENT_JSON;
        }
        throw new PayloadDecodeException(415, 'Format non reconnu (magic bytes).');
    }

    /**
     * [Description for probeGzipForTar]
     * Probe rapide : tar inside gzip ?
     * Decode juste le debut et cherche "ustar" a l'offset 257.
     *
     * @param string $body
     *
     * @return bool
     *
     * Created at: 11/05/2026 00:11:22 (Europe/Paris)
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
     * Vérifie la coherence Content-Type vs format détecté.
     * Tolerant : si Content-Type est absent, on fait confiance aux magic bytes.
     *
     * @param string $detected
     * @param string|null $declared
     *
     * @return void
     *
     * Created at: 11/05/2026 00:11:43 (Europe/Paris)
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
            DcProcessingQueue::CONTENT_JSON => ['application/json', 'text/json'],
            DcProcessingQueue::CONTENT_GZIP => ['application/gzip', 'application/x-gzip'],
            DcProcessingQueue::CONTENT_ZIP  => ['application/zip', 'application/x-zip-compressed'],
            DcProcessingQueue::CONTENT_TGZ  => ['application/gzip', 'application/x-gzip', 'application/x-tar', 'application/x-compressed-tar'],
        ];
        if (!in_array($declared, $expectedDeclarations[$detected] ?? [], true)) {
            throw new PayloadDecodeException(
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
     * Created at: 11/05/2026 00:12:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeGzip(string $body): string
    {
        $decoded = @gzdecode($body, $this->maxDecodedSize);
        if ($decoded === false) {
            throw new PayloadDecodeException(422, 'Décompression gzip échouée (format corrompu).');
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
     * Created at: 11/05/2026 00:13:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeZip(string $body): string
    {
        // ZipArchive ne supporte que les fichiers : on ecrit en temp.
        $tmpZip = tempnam(sys_get_temp_dir(), 'dc-zip-');
        if ($tmpZip === false) {
            throw new PayloadDecodeException(500, 'Impossible de créer le fichier temporaire pour zip.');
        }
        try {
            file_put_contents($tmpZip, $body);
            $zip = new \ZipArchive();
            $code = $zip->open($tmpZip);
            if ($code !== true) {
                throw new PayloadDecodeException(422, sprintf('Archive ZIP invalide (code %d).', $code));
            }
            try {
                if ($zip->numFiles !== 1) {
                    throw new PayloadDecodeException(
                        422,
                        sprintf('Archive ZIP doit contenir exactement 1 fichier (%d trouvés).', $zip->numFiles)
                    );
                }
                $name = $zip->getNameIndex(0);
                if ($name === false || !str_ends_with(strtolower($name), '.json')) {
                    throw new PayloadDecodeException(422, 'L\'archive ZIP doit contenir un fichier .json.');
                }
                $stream = $zip->getStream($name);
                if ($stream === false) {
                    throw new PayloadDecodeException(422, 'Lecture du fichier dans le ZIP échouée.');
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
     * Created at: 11/05/2026 00:13:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeTgz(string $body): string
    {
        // Decode gzip -> tar
        $tar = $this->decodeGzip($body);

        // PharData nécessite un fichier
        $tmpTar = tempnam(sys_get_temp_dir(), 'dc-tar-') . '.tar';
        try {
            file_put_contents($tmpTar, $tar);
            try {
                $phar = new \PharData($tmpTar);
            } catch (\Throwable $e) {
                throw new PayloadDecodeException(422, 'Archive TAR invalide : ' . $e->getMessage(), $e);
            }
            $jsonContent = null;
            foreach (new \RecursiveIteratorIterator($phar) as $file) {
                /** @var \PharFileInfo $file */
                if (str_ends_with(strtolower($file->getFilename()), '.json')) {
                    if ($jsonContent !== null) {
                        throw new PayloadDecodeException(422, 'L\'archive TGZ doit contenir un seul fichier .json.');
                    }
                    $size = $file->getSize();
                    if ($size > $this->maxDecodedSize) {
                        throw new PayloadDecodeException(413, 'Fichier extrait trop volumineux.');
                    }
                    $jsonContent = $file->getContent();
                }
            }
            if ($jsonContent === null) {
                throw new PayloadDecodeException(422, 'Aucun fichier .json trouve dans l\'archive TGZ.');
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
     * @param mixed $stream
     * @param int $limit
     *
     * @return string
     *
     * Created at: 11/05/2026 00:13:36 (Europe/Paris)
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
                throw new PayloadDecodeException(422, 'Erreur de lecture du stream.');
            }
            $buffer .= $chunk;
            if (strlen($buffer) > $limit) {
                throw new PayloadDecodeException(413, 'Décompression dépasse la limite (zip-bomb suspecte).');
            }
        }
        return $buffer;
    }

    /**
     * [Description for extractProjectInfo]
     * Validation du schema minimal du rapport DependencyCheck.
     *
     * @param array<string, mixed> $parsed
     *
     * @return array{groupID: string, artifactID: string, version: string}
     *
     * Created at: 11/05/2026 00:14:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractProjectInfo(array $parsed): array
    {
        if (!isset($parsed['reportSchema'])) {
            throw new PayloadDecodeException(422, 'Champ "reportSchema" absent.');
        }
        if (!isset($parsed['projectInfo']) || !is_array($parsed['projectInfo'])) {
            throw new PayloadDecodeException(422, 'Bloc "projectInfo" absent ou invalide.');
        }
        if (!isset($parsed['dependencies']) || !is_array($parsed['dependencies'])) {
            throw new PayloadDecodeException(422, 'Bloc "dependencies" absent ou invalide.');
        }

        $info = $parsed['projectInfo'];
        $required = ['groupID', 'artifactID', 'version'];
        foreach ($required as $key) {
            if (!isset($info[$key]) || !is_string($info[$key]) || trim($info[$key]) === '') {
                throw new PayloadDecodeException(
                    422,
                    sprintf('Champ "projectInfo.%s" absent ou vide.', $key)
                );
            }
        }

        return [
            'groupID'    => trim($info['groupID']),
            'artifactID' => trim($info['artifactID']),
            'version'    => trim($info['version']),
        ];
    }
}
