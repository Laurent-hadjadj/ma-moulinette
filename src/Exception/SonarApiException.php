<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2024-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */


namespace App\Exception;

/**
 * [Description SonarApiException]
 * Exception levée lors d'une erreur avec l'API Sonar
 */
class SonarApiException extends \RuntimeException
{
    private array $response;

    public function __construct(
        string $message,
        array $response = [],
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);

        $this->response = $response;
    }

    public function getResponse(): array
    {
        return $this->response;
    }
}
