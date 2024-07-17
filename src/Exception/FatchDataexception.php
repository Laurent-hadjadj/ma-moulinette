<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * [Description FetchDataException]
 */
class FetchDataException extends Exception
{
    private $debug;
    private $render;

    public function __construct(string $message, string $debug, array $render, int $code = 0, Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->debug = $debug;
        $this->render = $render;
    }

    public function getDebug(): string
    {
        return $this->debug;
    }

    public function getRender(): array
    {
        return $this->render;
    }
}
