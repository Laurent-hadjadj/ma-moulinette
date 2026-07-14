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

/**
 * [Description PayloadDecodeResult]
 * MODIF 2026-05-08 : DTO immuable résultat de PayloadDecoder::decode().
 */
final readonly class PayloadDecodeResult
{
    public function __construct(
        public string $jsonString,
        public int    $decodedSize,
        public string $contentType,
        public string $sha256,
        public string $projectGroup,
        public string $projectArtifact,
        public string $projectVersion,
    ) {}
}
