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

namespace App\Service\Zap;

/**
 * [Description ZapPayloadDecodeException]
 * MODIF 2026-10-13 : exception portant un statut HTTP traduisible
 * directement par le controller en JsonResponse, sur le gabarit
 * PayloadDecodeException (Dependency-Check).
 *
 * Permet a ZapPayloadDecoder de remonter un code semantiquement correct
 * (400/413/415/422) sans coupler la couche service a HttpFoundation.
 */
class ZapPayloadDecodeException extends \RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
