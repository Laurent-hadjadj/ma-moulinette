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

use Exception;

/**
 * [Description SonarApiException]
 *
 * Levée quand un appel à l'API SonarQube renvoie un code HTTP non-200.
 * Conserve le payload de la réponse pour permettre au caller de logger ou réagir.
 *
 * Created at: 2026-05-01  — classe ajoutée pour corriger PHPStan class.notFound
 * dans SonarMetricsFetcherService::fetchMetrics().
 */
class SonarApiException extends Exception
{
    /** @var array<int|string, mixed> */
    private array $response;

    /**
     * @param array<int|string, mixed> $response
     */
    public function __construct(string $message, array $response, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->response = $response;
    }

    /**
     * @return array<int|string, mixed>
     */
    public function getResponse(): array
    {
        return $this->response;
    }
}
