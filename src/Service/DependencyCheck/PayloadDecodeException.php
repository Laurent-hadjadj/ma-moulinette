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
* [Description PayloadDecodeException]
* MODIF 2026-05-08 : exception portant un statut HTTP traduisible
 * directement par le controller en JsonResponse.
 *
 * Permet a PayloadDecoder de remonter un code semantiquement correct
 * (400/413/415/422) sans coupler la couche service a HttpFoundation.
 */
class PayloadDecodeException extends \RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
