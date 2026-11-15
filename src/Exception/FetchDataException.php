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

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * [Description FetchDataException]
 */
class FetchDataException extends Exception
{
    private string $debug;
    /** @var array<int|string, mixed> */
    private array $render;

    /**
     * @param array<int|string, mixed> $render
     */
    public function __construct(string $message, string $debug, array $render, int $code = 0, ?Exception $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->debug = $debug;
        $this->render = $render;
    }

    public function getDebug(): string
    {
        return $this->debug;
    }

    /** @return array<int|string, mixed> */
    public function getRender(): array
    {
        return $this->render;
    }
}
