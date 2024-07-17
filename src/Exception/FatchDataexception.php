<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

/**
 * [Description FetchDataException]
 */
class FetchDataException extends Exception
{
    private $render;

    public function __construct($message, $render)
    {
        parent::__construct($message);
        $this->render = $render;
    }

    public function getRender()
    {
        return $this->render;
    }
}
