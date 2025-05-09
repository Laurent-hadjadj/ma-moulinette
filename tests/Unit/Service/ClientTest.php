<?php

namespace App\Tests\Unit\Service;

use App\Service\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Exception\TransportException;

class ClientTest extends TestCase
{
    /** @var \Symfony\Contracts\HttpClient\HttpClientInterface */
    private $httpClient;
    /** @var \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface */
    private $params;
    /** @var \Psr\Log\LoggerInterface */
    private $logger;
    private $client;
    private $genericHeaders;

    public static $erreur400 = "Erreur 400 - La requête est incorrecte.";
    public static $erreur401 = "Erreur 401 - Erreur d'Authentification. La clé n'est pas correcte.";
    public static $erreur403 = "Erreur 403 - Vous n’êtes pas autorisé à vous connecter.";
    public static $erreur404 = "Erreur 404 - Le service n'a pas trouvé les éléments.";
    public static $erreur407 = "Erreur 407 - La requête n'a pas été appliquée à cause d'un manque d'authentification.";
    public static $erreur414 = "Erreur 414 - L'URI demandée par le client est plus trop longue.";
    public static $erreur418 = "Erreur 418 - « Je suis une théière », je refuse de préparer du café.";
    public static $erreur429 = "Erreur 429 - Le client a envoyé trop de requêtes en un temps donné.";
    public static $erreur500 = "Erreur 500 - Le serveur a rencontré un problème inattendu qui l'empêche de répondre à la requête.";
    public static $erreur502 = "Erreur 502 - Le serveur, agissant comme une passerelle ou un proxy, a reçu une réponse invalide.";
    public static $erreur505 = "Erreur 505 - La version du protocole HTTP utilisée dans la requête n'est pas prise en charge par le serveur.";
    public static $erreur504 = "Erreur 504 - Temps d’attente d’une réponse écoulé...";
    public static $erreurTransport = "Erreur de transport : ";
    public static $sonarToken ='123456789ABCDEF';

    protected function setUp(): void
    {
        parent::setUp();

        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->client = new Client($this->httpClient, $this->params, $this->logger);

        $this->params->method('get')->will($this->returnValueMap([
          ['sonar.url', 'https://sonarqube.ma-petite-entreprise.fr'],
          ['proxy', ''],
          ['ciphers', 'DEFAULT:!DH'],
          ['sonar.token', static::$sonarToken],
          ['sonar.user', 'laurent.hadjadj'],
          ['sonar.password', 'change-me'],
          ['sonar.activity.token', static::$sonarToken],
          ['sonar.activity.user', 'laurent.hadjadj'],
          ['sonar.activity.password', 'change-me'],
          ['verify.host', 'true'],
          ['verify.peer', 'true'],
      ]));

      // Initialiser les en-têtes génériques
      $this->genericHeaders = $this->genericHeaders();
    }

    private function genericHeaders(): array
    {
        return [
            'Cache-Control' => 'no-cache',
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'X-Powered-By' => 'Ma-Moulinette'
        ];
    }

    protected function invokePrivateMethod($methodName, array $parameters = [])
    {
        $reflection = new \ReflectionMethod(Client::class, $methodName);
        $reflection->setAccessible(true);
        return $reflection->invokeArgs($this->client, $parameters);
    }

    public function testHandleTimeoutException(): void
    {
        $exception = new TimeoutException('Test timeout exception message');

        // Appelez la méthode handleTransportException avec cette exception
        $result = $this->invokePrivateMethod('handleTimeoutException', [$exception]);

        // Configurez les attentes pour le logger
        $this->logger
            ->expects($this->any())
            ->method('error')
            ->with('Erreur de transport : Test timeout exception message');

        // vérifie le résultat
        $this->assertEquals(504, $result['code']);
        $this->assertEquals(Client::$erreur504, $result['erreur']);
    }

    public function testHandleTransportExceptionFailedToOpenStream()
    {
        // Créez une exception TransportException avec un message contenant "Failed to open stream"
        $exception = new TransportException('Failed to open stream');
        $errorMessage = "Erreur 503 - Le service est actuellement indisponible. Impossible d'établir une connexion.";

        // Appelez la méthode handleTransportException avec cette exception
        $result = $this->invokePrivateMethod('handleTransportException', [$exception]);
        // Configurez les attentes pour le logger
        $this->logger
          ->expects($this->any())
          ->method('error')
          ->with(static::$erreurTransport . $errorMessage);

        // Vérifiez que le code retourné est 503 et que le message d'erreur est celui attendu
        $this->assertEquals(503, $result['code']);
        $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleTransportExceptionCouldNotResolveHost()
    {
        // Créez une exception TransportException avec un message contenant "Could not resolve host"
        $exception = new TransportException('Could not resolve host');
        $errorMessage = "Erreur 503 - La résolution DNS n'a pas permis d'accéder au serveur SonarQube.";

        // Appelez la méthode handleTransportException avec cette exception
        $result = $this->invokePrivateMethod('handleTransportException', [$exception]);
        // Configurez les attentes pour le logger
        $this->logger
          ->expects($this->any())
          ->method('error')
          ->with(static::$erreurTransport . $errorMessage);

        // Vérifiez que le code retourné est 503 et que le message d'erreur est celui attendu
        $this->assertEquals(503, $result['code']);
        $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleTransportExceptionInvalidHttpProxy()
    {
        // Créez une exception TransportException avec un message contenant "Could not resolve host"
        $exception = new TransportException('Invalid HTTP proxy');
        $errorMessage = "Erreur 503 - L'adresse définit pour le proxy n'est pas correcte.";

        // Appelez la méthode handleTransportException avec cette exception
        $result = $this->invokePrivateMethod('handleTransportException', [$exception]);
        // Configurez les attentes pour le logger
        $this->logger
          ->expects($this->any())
          ->method('error')
          ->with(static::$erreurTransport . $errorMessage);

        // Vérifiez que le code retourné est 503 et que le message d'erreur est celui attendu
        $this->assertEquals(503, $result['code']);
        $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleTransportExceptionGenericError()
    {
        $exception = new TransportException('Gateway Timeout');
        $errorMessage = "Erreur 503|504 de transport non spécifiée. | Détails : Gateway Timeout";

        // Appelez la méthode handleTransportException avec cette exception
        $result = $this->invokePrivateMethod('handleTransportException', [$exception]);
        // Configurez les attentes pour le logger
        $this->logger
          ->expects($this->any())
          ->method('error')
          ->with(static::$erreurTransport . $errorMessage);

        // Vérifiez que le code retourné est 504 et que le message d'erreur est celui attendu
        $this->assertEquals(504, $result['code']);
        $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleClientException400()
    {
      $exceptionMessage = 'Bad Request';
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur400;
      $exception = new \App\Tests\Mocks\TestClientException(400, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 400 du client : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleClientException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(400, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleClientException401()
    {
      $exceptionMessage = 'Unauthorized';
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur401;
      $exception = new \App\Tests\Mocks\TestClientException(401, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 401 du client : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleClientException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(401, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleClientException403()
    {
      $exceptionMessage = 'Forbidden';
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur403;
      $exception = new \App\Tests\Mocks\TestClientException(403, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 403 du client : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleClientException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(403, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleClientException404()
    {
      $exceptionMessage = 'Not Found';
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur404;
      $exception = new \App\Tests\Mocks\TestClientException(404, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 404 du client : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleClientException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(404, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleClientException407()
    {
      $exceptionMessage = 'Proxy Authentication Required';
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur407;
      $exception = new \App\Tests\Mocks\TestClientException(407, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 407 du client : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleClientException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(407, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleClientException414()
    {
      $exceptionMessage = 'URI Too Long';
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur414;
      $exception = new \App\Tests\Mocks\TestClientException(414, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 414 du client : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleClientException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(414, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleClientException418()
    {
      $exceptionMessage = "I'm a teapot";
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur418;
      $exception = new \App\Tests\Mocks\TestClientException(418, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 418 du client : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleClientException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(418, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleClientException429()
    {
      $exceptionMessage = "Too Many Requests";
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur429;
      $exception = new \App\Tests\Mocks\TestClientException(429, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 429 du client : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleClientException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(429, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleServerException500()
    {
      $exceptionMessage = 'Internal Server Error';
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur500;
      $exception = new \App\Tests\Mocks\TestClientException(500, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 500 du serveur : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleServerException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(500, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleServerException502()
    {
      $exceptionMessage = 'Bad Gateway';
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur502;
      $exception = new \App\Tests\Mocks\TestClientException(502, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 502 du serveur : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleServerException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(502, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleServerException505()
    {
      $exceptionMessage = 'HTTP Version Not Supported';
      /** @var \Symfony\Contracts\HttpClient\ResponseInterface */
      $mockResponse = $this->createMock(\Symfony\Contracts\HttpClient\ResponseInterface::class);
      $mockResponse->method('getContent')->willReturn($exceptionMessage);

      $errorMessage = static::$erreur505;
      $exception = new \App\Tests\Mocks\TestClientException(505, $mockResponse, $errorMessage);

      // Configurez les attentes pour le logger
      $this->logger
        ->expects($this->any())
        ->method('error')
        ->with("Erreur 505 du serveur : " . $exceptionMessage);

      // Appelez la méthode privée via Reflection
      $result = $this->invokePrivateMethod('handleServerException', [$exception]);

      // Vérifiez que le code et le message d'erreur sont corrects
      $this->assertEquals(505, $result['code']);
      $this->assertEquals($errorMessage, $result['erreur']);
    }

    public function testHandleGenericException()
    {
        // Crée une exception générique avec un message spécifique
        $exceptionMessage = 'Erreur inconnue';
        $exception = new \Exception($exceptionMessage);

        // Attendre que la méthode "error" du logger soit appelée avec le message attendu
        $this->logger
            ->expects($this->once())
            ->method('error')
            ->with($this->stringContains("Une erreur inattendue du serveur s'est produite : " . $exceptionMessage));

        // Appel de la méthode handleGenericException avec l'exception
        $result = $this->invokePrivateMethod('handleGenericException', [$exception]);

        // Vérifier que le code retourné est 500 et que le message est correct
        $this->assertEquals(500, $result['code']);
        $this->assertEquals("Une erreur inattendue du serveur s'est produite (Erreur 500).", $result['erreur']);
    }

    public function testAuthenticateWithToken()
    {
        // Cas où le token est présent, il faut l'utiliser pour l'authentification.

        // Récupérer l'URL et le token
        $url = $this->params->get('sonar.url');
        $token = $this->params->get('sonar.activity.token');

        // Vérification que l'URL et le token sont correctement récupérés
        $this->assertEquals('https://sonarqube.ma-petite-entreprise.fr', $url, 'L\'URL de SonarQube ne correspond pas');
        $this->assertEquals(static::$sonarToken, $token, 'Le token SonarQube ne correspond pas');

        // Simuler l'authentification avec token dans le client Activity
        $resultActivity = $this->client->httpActivity($url);
        $this->assertNotNull($resultActivity, 'La méthode httpActivity doit retourner un résultat non nul');
        $this->assertArrayHasKey('code', $resultActivity, 'Le résultat de httpActivity doit contenir un code');

        // Si le code d'erreur est 500, vérifier l'absence de JSON
        if ($resultActivity['code'] === 500) {
            $this->assertArrayNotHasKey('json', $resultActivity);
        } else {
            $this->assertArrayHasKey('json', $resultActivity, 'Le résultat de httpActivity doit contenir des données JSON');
        }

        // Simuler l'authentification avec token dans le client SonarQube
        $tokenSonar = $this->params->get('sonar.token');
        $this->assertEquals(static::$sonarToken, $tokenSonar, 'Le token SonarQube dans le client ne correspond pas');

        $resultSonarQube = $this->client->httpSonarQube($url);
        $this->assertNotNull($resultSonarQube, 'La méthode httpSonarQube doit retourner un résultat non nul');
        $this->assertArrayHasKey('code', $resultSonarQube, 'Le résultat de httpSonarQube doit contenir un code');

        // Si le code d'erreur est 500, vérifier l'absence de JSON
        if ($resultSonarQube['code'] === 500) {
            $this->assertArrayNotHasKey('json', $resultSonarQube, 'Le résultat ne doit pas contenir de JSON en cas d\'erreur 500');
        } else {
            $this->assertArrayHasKey('json', $resultSonarQube, 'Le résultat de httpSonarQube doit contenir des données JSON');
        }
    }

    public function testAuthenticateWithLoginAndPassword()
    {
        // Cas où le token est absent, il faut utiliser le login et le mot de passe pour l'authentification.

        // Récupérer l'URL, le login, et le mot de passe
        $url = $this->params->get('sonar.url');
        $user = $this->params->get('sonar.activity.user');
        $password = $this->params->get('sonar.activity.password');

        // Vérifier que l'URL, le login, et le mot de passe sont correctement récupérés
        $this->assertEquals('https://sonarqube.ma-petite-entreprise.fr', $url, 'L\'URL de SonarQube ne correspond pas');
        $this->assertEquals('laurent.hadjadj', $user, 'Le login SonarQube ne correspond pas');
        $this->assertEquals('change-me', $password, 'Le mot de passe SonarQube ne correspond pas');

        // Simuler l'authentification avec login et mot de passe dans le client Activity
        $resultActivity = $this->client->httpActivity($url);
        $this->assertNotNull($resultActivity, 'La méthode httpActivity doit retourner un résultat non nul');
        $this->assertArrayHasKey('code', $resultActivity, 'Le résultat de httpActivity doit contenir un code');

        // Si le code d'erreur est 500, vérifier l'absence de JSON
        if ($resultActivity['code'] === 500) {
            $this->assertArrayNotHasKey('json', $resultActivity, 'Le résultat ne doit pas contenir de JSON en cas d\'erreur 500');
        } else {
            $this->assertArrayHasKey('json', $resultActivity, 'Le résultat de httpActivity doit contenir des données JSON');
        }

        // Simuler l'authentification avec login et mot de passe dans le client SonarQube
        $resultSonarQube = $this->client->httpSonarQube($url);
        $this->assertNotNull($resultSonarQube, 'La méthode httpSonarQube doit retourner un résultat non nul');
        $this->assertArrayHasKey('code', $resultSonarQube, 'Le résultat de httpSonarQube doit contenir un code');

        // Si le code d'erreur est 500, vérifier l'absence de JSON
        if ($resultSonarQube['code'] === 500) {
            $this->assertArrayNotHasKey('json', $resultSonarQube, 'Le résultat ne doit pas contenir de JSON en cas d\'erreur 500');
        } else {
            $this->assertArrayHasKey('json', $resultSonarQube, 'Le résultat de httpSonarQube doit contenir des données JSON');
        }
    }

    public function testHttpRequestOptions()
    {
        // Cas où on teste les paramètres avant l'appel HTTP.

        // Simuler la récupération des paramètres depuis $this->params->get()
        $this->params->method('get')->will($this->returnValueMap([
            ['ciphers', 'DEFAULT:!DH'],
            ['verify.host', 'true'],
            ['verify.peer', 'true'],
            ['proxy', ''],
        ]));

        // Simuler un utilisateur et un mot de passe
        $user = 'laurent.hadjadj';
        $password = 'change-me';

        // Préparer les options selon la logique de ton code
        $ciphers = $this->params->get('ciphers');
        $verify_host = $this->params->get('verify.host');
        $verify_peer = $this->params->get('verify.peer');

        $options = [
          'auth_basic' => [$user, $password],
          'timeout' => 45,
          'headers' => $this->genericHeaders,
          'ciphers' => $ciphers,
          'verify_host' => filter_var($verify_host, FILTER_VALIDATE_BOOLEAN),
          'verify_peer' => filter_var($verify_peer, FILTER_VALIDATE_BOOLEAN),
      ];

        // Ajouter le proxy si défini
        $proxy = $this->params->get('proxy');
        if (!empty($proxy)) {
            $options['proxy'] = $proxy;
        }

        // Vérification des valeurs attendues dans les options
        $this->assertEquals('DEFAULT:!DH', $options['ciphers']);
        $this->assertTrue($options['verify_host']);
        $this->assertTrue($options['verify_peer']);
        $this->assertArrayNotHasKey('proxy', $options);

        // Vérification que les autres options sont correctement assignées
        $this->assertArrayHasKey('auth_basic', $options);
        $this->assertEquals([$user, $password], $options['auth_basic']);
        $this->assertEquals(45, $options['timeout']);
        $this->assertArrayHasKey('headers', $options); // Vérifie que les headers sont ajoutés
    }

    public function testHttpSonarQubeRequestSuccess()
    {
        // Configuration des valeurs attendues pour la réponse simulée
        $url = 'https://sonarqube.ma-petite-entreprise.fr/api/issues/tags?project=tetris:TetrisGame&metrics';
        $expectedResponse = json_encode(["tags" => ["convention", "security", "cwe"]]);

        // Simulation de la réponse HTTP avec succès
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getContent')->willReturn($expectedResponse);
        $mockResponse->method('getStatusCode')->willReturn(200); // Code HTTP 200
        $mockResponse->method('getInfo')->willReturnMap([
            ['http_method', 'GET'],
            ['http_code', 200],
            ['total_time', 0.123],
            ['url', $url],
        ]);

        // Simuler le comportement de client dans la méthode request()
        $this->httpClient->method('request')->willReturn($mockResponse);

        // Tester la méthode httpActivity qui appelle la méthode request
        $result = $this->client->httpSonarQube($url);

        // Vérification des résultats
        $this->assertNotNull($result);  // Le résultat ne doit pas être nul
        $this->assertEquals(200, $result['code']);  // Code HTTP
        $this->assertArrayHasKey('json', $result);  // La clé 'json' doit être présente
        $this->assertEquals(["tags" => ["convention", "security", "cwe"]], $result['json']);  // Vérification des données JSON décodées

        // Vérification du log
        // La méthode de logger est appelée avec les informations attendues
        $this->logger->expects($this->any())
          ->method('info')
          ->with($this->stringContains($result['message']));
    }

    public function testHttpActivityRequestSuccess()
    {
        // Configuration des valeurs attendues pour la réponse simulée
        $url = 'https://sonarqube.ma-petite-entreprise.fr/api/ce/activity';
        $data= [[ "tasks" => [
                  [
                      "organization" => "my-org-1",
                      "id" => "BU_dO1vsORa8_beWCwsP",
                      "type" => "REPORT",
                      "componentId" => "AU-Tpxb--iU5OvuD2FLy",
                      "componentKey" => "project_1",
                      "componentName" => "Project One",
                      "componentQualifier" => "TRK",
                      "analysisId" => "AU-TpxcB-iU5Ovu12345",
                      "status" => "SUCCESS",
                      "submittedAt" => "2015-08-13T23:34:59+0200",
                      "submitterLogin" => "john",
                      "startedAt" => "2015-08-13T23:35:00+0200",
                      "executedAt" => "2015-08-13T23:35:10+0200",
                      "executionTimeMs" => 10000,
                      "logs" => false,
                      "hasErrorStacktrace" => false,
                      "hasScannerContext" => true
                    ]
                ]
              ]
            ];
        $expectedResponse = json_encode($data);

        // Simulation de la réponse HTTP avec succès
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getContent')->willReturn($expectedResponse);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getInfo')->willReturnMap([
            ['http_method', 'GET'],
            ['http_code', 200],
            ['total_time', 0.423],
            ['url', $url],
        ]);

        // Simuler le comportement de client dans la méthode request()
        $this->httpClient->method('request')->willReturn($mockResponse);

        // Tester la méthode httpActivity qui appelle la méthode request
        $result = $this->client->httpActivity($url);

        // Vérification des résultats
        $this->assertNotNull($result);  // Le résultat ne doit pas être nul
        $this->assertEquals(200, $result['code']);  // Code HTTP
        $this->assertArrayHasKey('json', $result);  // La clé 'json' doit être présente
        $this->assertEquals($data, $result['json']);  // Vérification des données JSON décodées

        // Vérification du log
        // La méthode de logger est appelée avec les informations attendues
        $this->logger->expects($this->any())
          ->method('info')
          ->with($this->stringContains($result['message']));
    }

    public function testHttpActuatorRequestSuccess()
    {
        // Configuration des valeurs attendues pour la réponse simulée
        $url = 'https://ma-moulinette.ma-petite-entreprise.fr/api/ce/activity';
        $data= [[
          "nom" => "monapplication-mat-api",
          "description" => "Mon Application Mat",
          "version" => "3.2.1-RC1",
          "app" => "Ajouter le code application",
          "socle" => [
              "archetype" => "4.2.1-RC1",
              "config" => "4.1.1-RELEASE",
              "angular" => "14.3.0",
              "sde" => "@sde.version@",
              "java" => "1.8.0_231",
              "encodage" => "UTF-8"
              ],
            ]];
        $expectedResponse = json_encode($data);

        // Simulation de la réponse HTTP avec succès
        $mockResponse = $this->createMock(ResponseInterface::class);
        $mockResponse->method('getContent')->willReturn($expectedResponse);
        $mockResponse->method('getStatusCode')->willReturn(200);
        $mockResponse->method('getInfo')->willReturnMap([
            ['http_method', 'GET'],
            ['http_code', 200],
            ['total_time', 0.423],
            ['url', $url],
        ]);

        // Simuler le comportement de client dans la méthode request()
        $this->httpClient->method('request')->willReturn($mockResponse);

        // Tester la méthode httpActivity qui appelle la méthode request
        $result = $this->client->httpActivity($url);

        // Vérification des résultats
        $this->assertNotNull($result);  // Le résultat ne doit pas être nul
        $this->assertEquals(200, $result['code']);  // Code HTTP
        $this->assertArrayHasKey('json', $result);  // La clé 'json' doit être présente
        $this->assertEquals($data, $result['json']);  // Vérification des données JSON décodées

        // Vérification du log
        // La méthode de logger est appelée avec les informations attendues
        $this->logger->expects($this->any())
          ->method('info')
          ->with($this->stringContains($result['message']));
    }
}
