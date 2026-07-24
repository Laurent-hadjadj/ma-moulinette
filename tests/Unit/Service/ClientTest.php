<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Service;

use App\Service\ClientService;
use App\Tests\Support\Mocks\TestClientException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\Exception\{ServerException,TimeoutException,TransportException};
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * [Description ClientTest]
 *
 * v2.0.0 : aligné avec ClientService (logs génériques `[handler] ❌ ...`).
 */
#[AllowMockObjectsWithoutExpectations]
class ClientTest extends TestCase
{
    /** Constantes miroir des messages du service ClientService. */
    public static string $erreur400 = 'La requête est incorrecte  (Erreur 400).';
    public static string $erreur401 = "Erreur d'Authentification. La clé n'est pas correcte  (Erreur 401).";
    public static string $erreur403 = 'Vous n’êtes pas autorisé à vous connecter (Erreur 403).';
    public static string $erreur404 = "Le service n'a pas trouvé les éléments (Erreur 404).";
    public static string $erreur407 = "La requête n'a pas été appliquée à cause d'un manque d'authentification (Erreur 407).";
    public static string $erreur414 = "L'URI demandée par le client est plus trop longue (Erreur 414).";
    public static string $erreur418 = '«Je suis une théière », je refuse de préparer du café (Erreur 418).';
    public static string $erreur429 = 'Le client a envoyé trop de requêtes en un temps donné (Erreur 429).';
    public static string $erreur500 = "Le serveur a rencontré un problème inattendu qui l'empêche de répondre à la requête (Erreur 500).";
    public static string $erreur502 = 'Le serveur, agissant comme une passerelle ou un proxy, a reçu une réponse invalide (Erreur 502).';
    public static string $erreur504 = 'Temps d’attente d’une réponse écoulé... (Erreur 504).';
    public static string $erreur505 = "La version du protocole HTTP utilisée dans la requête n'est pas prise en charge par le serveur (Erreur 505).";

    public static string $sonarToken = '123456789ABCDEF';

    /** @var HttpClientInterface */
    private HttpClientInterface $httpClient;
    /** @var ParameterBagInterface */
    private ParameterBagInterface $params;
    /** @var LoggerInterface */
    private LoggerInterface $logger;
    /** @var ClientService */
    private ClientService $client;

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = new ClientService($this->httpClient, $this->params, $this->logger);

        $this->params->method('get')->willReturnMap([
            ['sonar.url', 'https://sonarqube.ma-petite-entreprise.fr'],
            ['proxy', ''],
            ['ciphers', 'DEFAULT:!DH'],
            ['sonar.token', self::$sonarToken],
            ['sonar.user', 'laurent.hadjadj'],
            ['sonar.password', 'change-me'],
            ['sonar.activity.token', self::$sonarToken],
            ['sonar.activity.user', 'laurent.hadjadj'],
            ['sonar.activity.password', 'change-me'],
            ['verify.host', 'true'],
            ['verify.peer', 'true'],
        ]);
    }

    private function invokePrivateMethod(string $methodName, array $parameters = []): mixed
    {
        $reflection = new \ReflectionMethod(ClientService::class, $methodName);
        return $reflection->invokeArgs($this->client, $parameters);
    }

    private function mockResponse(string $body): ResponseInterface
    {
        $mock = $this->createMock(ResponseInterface::class);
        $mock->method('getContent')->willReturn($body);
        return $mock;
    }

    public function testHandleTimeoutException(): void
    {
        $exception = new TimeoutException('Timeout message');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('[handleTimeoutException] ❌ ' . self::$erreur504, [
                'code' => 504,
                'erreur' => 'Timeout message',
            ]);

        $result = $this->invokePrivateMethod('handleTimeoutException', [$exception]);

        $this->assertSame(504, $result['code']);
        $this->assertSame(self::$erreur504, $result['erreur']);
    }

    public function testHandleTransportExceptionFailedToOpenStream(): void
    {
        $exception = new TransportException('Failed to open stream');
        $expected = "Le service est actuellement indisponible. Impossible d'établir une connexion (Erreur 503).";

        $this->logger->expects($this->once())
            ->method('error')
            ->with('[handleTransportException] ❌ ' . $expected, [
                'code' => 503,
                'erreur' => 'Failed to open stream',
            ]);

        $result = $this->invokePrivateMethod('handleTransportException', [$exception]);

        $this->assertSame(503, $result['code']);
        $this->assertSame($expected, $result['erreur']);
    }

    public function testHandleTransportExceptionCouldNotResolveHost(): void
    {
        $exception = new TransportException('Could not resolve host');
        $expected = "La résolution DNS n'a pas permis d'accéder au serveur SonarQube (Erreur 503).";

        $this->logger->expects($this->once())
            ->method('error')
            ->with('[handleTransportException] ❌ ' . $expected, [
                'code' => 503,
                'erreur' => 'Could not resolve host',
            ]);

        $result = $this->invokePrivateMethod('handleTransportException', [$exception]);

        $this->assertSame(503, $result['code']);
        $this->assertSame($expected, $result['erreur']);
    }

    public function testHandleTransportExceptionInvalidHttpProxy(): void
    {
        $exception = new TransportException('Invalid HTTP proxy');
        $expected = "L'adresse définit pour le proxy n'est pas correcte (Erreur 503).";

        $this->logger->expects($this->once())
            ->method('error')
            ->with('[handleTransportException] ❌ ' . $expected, [
                'code' => 503,
                'erreur' => 'Invalid HTTP proxy',
            ]);

        $result = $this->invokePrivateMethod('handleTransportException', [$exception]);

        $this->assertSame(503, $result['code']);
        $this->assertSame($expected, $result['erreur']);
    }

    public function testHandleTransportExceptionGenericError(): void
    {
        $exception = new TransportException('Gateway Timeout');
        $expected = 'Erreur 503|504 de transport non spécifiée.';

        $this->logger->expects($this->once())
            ->method('error')
            ->with('[handleTransportException] ❌ ' . $expected, [
                'code' => 504,
                'erreur' => 'Gateway Timeout',
            ]);

        $result = $this->invokePrivateMethod('handleTransportException', [$exception]);

        $this->assertSame(504, $result['code']);
        $this->assertSame($expected, $result['erreur']);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('clientExceptionCodesProvider')]
    public function testHandleClientException(int $code, string $expectedKey): void
    {
        $exception = new TestClientException($code, $this->mockResponse('Not JSON body'), 'msg');

        $this->logger->expects($this->once())
            ->method('error')
            ->with('[handleClientException] ❌ Une erreur non spécifiée est survenue. ', [
                'code' => $code,
                'body' => null,
            ]);

        $result = $this->invokePrivateMethod('handleClientException', [$exception]);

        $this->assertSame($code, $result['code']);
        $this->assertSame(self::${$expectedKey}, $result['erreur']);
    }

    public static function clientExceptionCodesProvider(): iterable
    {
        yield '400' => [400, 'erreur400'];
        yield '401' => [401, 'erreur401'];
        yield '403' => [403, 'erreur403'];
        yield '404' => [404, 'erreur404'];
        yield '407' => [407, 'erreur407'];
        yield '414' => [414, 'erreur414'];
        yield '418' => [418, 'erreur418'];
        yield '429' => [429, 'erreur429'];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('serverExceptionCodesProvider')]
    public function testHandleServerException(int $code, string $expectedKey): void
    {
        // JSON avec `detail` pour ne déclencher qu'un seul log (le final).
        $exception = $this->buildServerExceptionMock($code, '{"detail":{"k":"v"}}');

        $self = $this;
        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                '[handleServerException] ❌ Une erreur non spécifiée est survenue. ',
                $this->callback(function ($ctx) use ($code, $self) {
                    $self->assertSame($code, $ctx['code']);
                    $self->assertSame('{"detail":{"k":"v"}}', $ctx['body']);
                    return true;
                })
            );

        $result = $this->invokePrivateMethod('handleServerException', [$exception]);

        $this->assertSame($code, $result['code']);
        $this->assertSame(self::${$expectedKey}, $result['erreur']);
    }

    public static function serverExceptionCodesProvider(): iterable
    {
        yield '500' => [500, 'erreur500'];
        yield '502' => [502, 'erreur502'];
        yield '505' => [505, 'erreur505'];
    }

    private function buildServerExceptionMock(int $code, string $body): ServerException
    {
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($body);
        $response->method('getInfo')->willReturnCallback(static fn(string $key) => match ($key) {
            'http_code' => $code,
            'url' => 'http://test.example/path',
            'response_headers' => [],
            default => null,
        });

        return new ServerException($response);
    }

    public function testHandleGenericException(): void
    {
        $exception = new \Exception('Erreur inconnue');

        $this->logger->expects($this->once())
            ->method('error')
            ->with("Une erreur inattendue du serveur s'est produite : Erreur inconnue");

        $result = $this->invokePrivateMethod('handleGenericException', [$exception]);

        $this->assertSame(500, $result['code']);
        $this->assertSame(
            "Une erreur globale inattendue du serveur s'est produite (Erreur 500).",
            $result['erreur']
        );
    }

    public function testHandleGenericExceptionEnvCiphers(): void
    {
        $exception = new \Exception('Environment variable not found: "CIPHERS".');

        $result = $this->invokePrivateMethod('handleGenericException', [$exception]);

        $this->assertSame(500, $result['code']);
        $this->assertSame("La variable 'CIPHERS' n'a pas été définie correctement.", $result['erreur']);
    }

    public function testHttpSonarQubeRequestSuccess(): void
    {
        $url = 'https://sonarqube.ma-petite-entreprise.fr/api/issues/tags';
        $body = json_encode(['tags' => ['convention', 'security']]);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($body);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'],
            ['http_code', 200],
            ['total_time', 0.123],
            ['url', $url],
        ]);

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->client->httpSonarQube($url);

        $this->assertSame(200, $result['code']);
        $this->assertArrayHasKey('json', $result);
        $this->assertSame(['tags' => ['convention', 'security']], $result['json']);
    }

    public function testHttpActivityRequestSuccess(): void
    {
        $url = 'https://sonarqube.ma-petite-entreprise.fr/api/ce/activity';
        $data = ['tasks' => [['id' => 'BU_dO1vsORa8', 'status' => 'SUCCESS']]];
        $body = json_encode($data);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($body);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'],
            ['http_code', 200],
            ['total_time', 0.423],
            ['url', $url],
        ]);

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->client->httpActivity($url);

        $this->assertSame(200, $result['code']);
        $this->assertSame($data, $result['json']);
    }

    public function testHttpActuatorRequestSuccess(): void
    {
        $url = 'https://ma-moulinette.ma-petite-entreprise.fr/actuator/info';
        $data = ['nom' => 'app', 'version' => '1.0.0'];
        $body = json_encode($data);

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn($body);
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'],
            ['http_code', 200],
            ['total_time', 0.5],
            ['url', $url],
        ]);

        $this->httpClient->method('request')->willReturn($response);

        $result = $this->client->httpActuator($url, 'user', 'pass');

        $this->assertSame(200, $result['code']);
        $this->assertSame($data, $result['json']);
    }

    /**
     * MODIF 2026-07-23 : timeout dédié court (3s, au lieu des 45s génériques) —
     * Actuator est un appel "best effort" annexe à la collecte projet.
     */
    public function testHttpActuatorUsesShortDedicatedTimeout(): void
    {
        $url = 'https://ma-moulinette.ma-petite-entreprise.fr/actuator/info';

        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{}');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'], ['http_code', 200], ['total_time', 0.1], ['url', $url],
        ]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->callback(fn ($opts) => ($opts['timeout'] ?? null) === 3))
            ->willReturn($response);

        $this->client->httpActuator($url, 'user', 'pass');
    }

    /* ============ 401 : token + user vides ============ */

    public function testHttpSonarQubeReturns401WhenTokenAndUserMissing(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['sonar.token', ''],
            ['sonar.user', ''],
        ]);
        $client = new ClientService($this->httpClient, $params, $this->logger);

        $result = $client->httpSonarQube('https://sonar/api/test');

        $this->assertSame(401, $result['code']);
        $this->assertStringContainsString('Authentification', $result['erreur']);
    }

    public function testHttpActivityReturns401WhenTokenAndUserMissing(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['sonar.activity.token', ''],
            ['sonar.activity.user', ''],
        ]);
        $client = new ClientService($this->httpClient, $params, $this->logger);

        $result = $client->httpActivity('https://sonar/api/activity');

        $this->assertSame(401, $result['code']);
    }

    /* ============ httpSonarQube : user/password only (no token) ============ */

    public function testHttpSonarQubeUsesUserPasswordWhenTokenEmpty(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['sonar.token', ''],
            ['sonar.user', 'laurent'],
            ['sonar.password', 'secret'],
            ['proxy', ''],
            ['ciphers', 'DEFAULT'],
            ['verify.host', 'true'],
            ['verify.peer', 'true'],
        ]);
        $client = new ClientService($this->httpClient, $params, $this->logger);

        $url = 'https://sonar/api/test';
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"ok":true}');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'], ['http_code', 200], ['total_time', 0.1], ['url', $url],
        ]);
        $this->httpClient->method('request')->willReturn($response);

        $result = $client->httpSonarQube($url);

        $this->assertSame(200, $result['code']);
    }

    /* ============ httpSonarQube : proxy non vide ============ */

    public function testHttpSonarQubeAddsProxyOption(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['sonar.token', self::$sonarToken],
            ['sonar.user', 'laurent'],
            ['proxy', 'http://proxy.example.com:8080'],
            ['ciphers', 'DEFAULT'],
            ['verify.host', 'true'],
            ['verify.peer', 'true'],
        ]);
        $client = new ClientService($this->httpClient, $params, $this->logger);

        $url = 'https://sonar/api/test';
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{}');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'], ['http_code', 200], ['total_time', 0.1], ['url', $url],
        ]);
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->callback(fn($opts) => isset($opts['proxy']) && $opts['proxy'] === 'http://proxy.example.com:8080'))
            ->willReturn($response);

        $result = $client->httpSonarQube($url);

        $this->assertSame(200, $result['code']);
    }

    /* ============ httpSonarQube : response body non-JSON (texte brut) ============ */

    public function testHttpSonarQubeReturnsTextWhenBodyIsNotJson(): void
    {
        $url = 'https://sonar/api/server/version';
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('10.4.0-SNAPSHOT');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'], ['http_code', 200], ['total_time', 0.1], ['url', $url],
        ]);
        $this->httpClient->method('request')->willReturn($response);

        $result = $this->client->httpSonarQube($url);

        $this->assertSame(200, $result['code']);
        $this->assertSame('10.4.0-SNAPSHOT', $result['json']['texte']);
    }

    /* ============ httpActivity : user/password only (no token) ============ */

    public function testHttpActivityUsesUserPasswordWhenTokenEmpty(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['sonar.activity.token', ''],
            ['sonar.activity.user', 'laurent'],
            ['sonar.activity.password', 'secret'],
            ['proxy', ''],
            ['ciphers', 'DEFAULT'],
            ['verify.host', 'true'],
            ['verify.peer', 'true'],
        ]);
        $client = new ClientService($this->httpClient, $params, $this->logger);

        $url = 'https://sonar/api/ce/activity';
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"tasks":[]}');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'], ['http_code', 200], ['total_time', 0.1], ['url', $url],
        ]);
        $this->httpClient->method('request')->willReturn($response);

        $result = $client->httpActivity($url);

        $this->assertSame(200, $result['code']);
    }

    /* ============ httpActivity : proxy non vide ============ */

    public function testHttpActivityAddsProxyOption(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['sonar.activity.token', self::$sonarToken],
            ['sonar.activity.user', 'laurent'],
            ['proxy', 'http://proxy:8080'],
            ['ciphers', 'DEFAULT'],
            ['verify.host', 'true'],
            ['verify.peer', 'true'],
        ]);
        $client = new ClientService($this->httpClient, $params, $this->logger);

        $url = 'https://sonar/api/ce/activity';
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{"tasks":[]}');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'], ['http_code', 200], ['total_time', 0.1], ['url', $url],
        ]);
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->callback(fn($opts) => isset($opts['proxy'])))
            ->willReturn($response);

        $result = $client->httpActivity($url);

        $this->assertSame(200, $result['code']);
    }

    /* ============ httpActuator : sans auth (user/password null) ============ */

    public function testHttpActuatorWithoutAuth(): void
    {
        $url = 'https://app/actuator/info';
        $response = $this->createMock(ResponseInterface::class);
        $response->method('getContent')->willReturn('{}');
        $response->method('getStatusCode')->willReturn(200);
        $response->method('getInfo')->willReturnMap([
            ['http_method', 'GET'], ['http_code', 200], ['total_time', 0.1], ['url', $url],
        ]);
        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', $url, $this->callback(fn($opts) => !isset($opts['auth_basic'])))
            ->willReturn($response);

        $result = $this->client->httpActuator($url, '', '');

        $this->assertSame(200, $result['code']);
    }
}
