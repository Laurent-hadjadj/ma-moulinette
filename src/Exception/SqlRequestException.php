<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Exception;

use RuntimeException;

/**
 * [Description SqlRequestException]
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
