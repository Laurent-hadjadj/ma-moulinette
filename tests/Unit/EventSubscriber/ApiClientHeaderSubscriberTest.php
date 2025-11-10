<?php

namespace App\Tests\Unit\EventSubscriber;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiClientHeaderSubscriberTest extends WebTestCase
{
    private array $allowedOrigins = ['front.monsite.com'];
    private string $internalHeaderName = 'X-Internal-Front';
    private string $internalHeaderValue = 'front-app';
    private string $appClientToken = '123456-FAKE-TOKEN';

    public function testSecureApiWithValidHeadersIsAllowed(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/secure/test', [], [], [
            'HTTP_Origin'             => 'https://front.monsite.com',
            'HTTP_' . str_replace('-', '_', strtoupper($this->internalHeaderName)) => $this->internalHeaderValue,
        ]);

        $response = $client->getResponse();

        $this->assertNotEquals(403, $response->getStatusCode(), 'Requête valide ne doit pas être bloquée.');
    }

    public function testSecureApiWithoutInternalHeaderIsForbidden(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/secure/test', [], [], [
            'HTTP_Origin' => 'https://front.monsite.com',
        ]);

        $response = $client->getResponse();

        $this->assertSame(403, $response->getStatusCode(), 'Requête sans header interne doit être bloquée.');
    }

    public function testSecureApiWithBadOriginIsForbidden(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/secure/test', [], [], [
            'HTTP_Origin'             => 'https://evil.com',
            'HTTP_' . str_replace('-', '_', strtoupper($this->internalHeaderName)) => $this->internalHeaderValue,
        ]);

        $response = $client->getResponse();

        $this->assertSame(403, $response->getStatusCode(), 'Requête depuis un domaine non autorisé doit être bloquée.');
    }

    public function testPublicApiIsNotFiltered(): void
    {
        $client = static::createClient();

        $client->request('GET', '/api/public/test');

        $response = $client->getResponse();

        // Ne doit pas passer par le subscriber (peut retourner 404 si la route n’existe pas)
        $this->assertNotSame(403, $response->getStatusCode(), 'Les routes publiques ne doivent pas être bloquées.');
    }
}
