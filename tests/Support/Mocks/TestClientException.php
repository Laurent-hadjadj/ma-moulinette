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

namespace App\Tests\Support\Mocks;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TestClientException extends \Exception implements HttpExceptionInterface
{
    private ResponseInterface $response;

    public function __construct(int $code, ResponseInterface $response, string $message = "")
    {
        parent::__construct($message, $code);
        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
