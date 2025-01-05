<?php

namespace App\Tests\Mocks;

use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class TestClientException extends \Exception implements HttpExceptionInterface
{
    private $response;

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
