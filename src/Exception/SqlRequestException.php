<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2024-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Exception;

use RuntimeException;

/**
 * [Description SqlRequestException]
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
*/
class SqlRequestException extends RuntimeException
{
    private int $errorCode;
    private string $errorMessage;
    private string $errorRequest;

    public function __construct(string $errorRequest, int $errorCode, string $errorMessage, ?\Throwable $previous = null)
    {
        $message = sprintf('Échec de la requête %s (Erreur %d): %s', $errorRequest, $errorCode, $errorMessage);
        parent::__construct($message, $errorCode, $previous);

        $this->errorCode = $errorCode;
        $this->errorRequest = $errorRequest;
        $this->errorMessage = $errorMessage;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorRequest(): string
    {
        return $this->errorRequest;
    }

    public function getErrorMessage(): string
    {
        return $this->errorMessage;
    }
}
