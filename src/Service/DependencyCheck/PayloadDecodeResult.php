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
