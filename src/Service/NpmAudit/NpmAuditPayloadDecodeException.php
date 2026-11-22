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
 * [Description NpmAuditPayloadDecodeException]
 * Exception portant un statut HTTP traduisible directement par le
 * contrôleur en JsonResponse — même pattern que PayloadDecodeException (DC).
 */
class NpmAuditPayloadDecodeException extends \RuntimeException
{
    public function __construct(
        public readonly int $httpStatus,
        string $message,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }
}
