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

/**
 * [Description ZapPayloadDecodeResult]
 * MODIF 2026-10-13 : DTO immuable résultat de ZapPayloadDecoder::decode().
 *
 * Contrairement à PayloadDecodeResult (Dependency-Check), ne porte aucune
 * identification projet : le rapport ZAP lui-même n'en contient pas, elle
 * est fournie par la CI via les en-têtes d'upload (X-Maven-Key,
 * X-Project-Version).
 */
final readonly class ZapPayloadDecodeResult
{
    public function __construct(
        public string $jsonString,
        public int    $decodedSize,
        public string $contentType,
        public string $sha256,
    ) {}
}
