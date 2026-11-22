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

/**
 * [Description NpmAuditPayloadDecodeResult]
 * DTO immuable résultat de NpmAuditPayloadDecoder::decode(). Contrairement
 * à PayloadDecodeResult (DC), ne porte pas projectGroup/Artifact/Version :
 * npm audit --json ne les contient pas, ils viennent des headers HTTP,
 * lus séparément par le contrôleur.
 */
final readonly class NpmAuditPayloadDecodeResult
{
    public function __construct(
        public string $jsonString,
        public int    $decodedSize,
        public string $contentType,
        public string $sha256,
    ) {}
}
