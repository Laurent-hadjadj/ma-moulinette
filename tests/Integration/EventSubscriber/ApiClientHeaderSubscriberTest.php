<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Integration\EventSubscriber;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ApiClientHeaderSubscriberTest extends WebTestCase
{
    private string $internalHeaderName = 'X-Internal-Front';
    private string $internalHeaderValue = 'front-app';
    private const API_SECURE_TEST = '/api/secure/test';

    public function testSecureApiWithValidHeadersIsAllowed(): void
    {
        /* MODIF 2026-05-07 : remplacement de l Origin par localhost.
         * APP_ALLOWED_ORIGINS=localhost,127.0.0.1 (cf .env.test.local).
         */
        $client = static::createClient();

        $client->request('GET', self::API_SECURE_TEST, [], [], [
            'HTTP_Origin'             => 'https://localhost',
            'HTTP_' . str_replace('-', '_', strtoupper($this->internalHeaderName)) => $this->internalHeaderValue,
        ]);

        $response = $client->getResponse();

        $this->assertNotEquals(403, $response->getStatusCode(), 'Requête valide ne doit pas être bloquée.');
    }

    public function testSecureApiWithoutInternalHeaderIsForbidden(): void
    {
        $client = static::createClient();

        $client->request('GET', self::API_SECURE_TEST, [], [], [
            'HTTP_Origin' => 'https://front.monsite.com',
        ]);

        $response = $client->getResponse();

        $this->assertSame(403, $response->getStatusCode(), 'Requête sans header interne doit être bloquée.');
    }

    public function testSecureApiWithBadOriginIsForbidden(): void
    {
        $client = static::createClient();

        $client->request('GET', self::API_SECURE_TEST, [], [], [
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
