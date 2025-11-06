<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Service;

/** Gestion de accès aux API */
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

use App\Exception\UnexpectedExecutionPathException;

/**
 * [Description Client]
 */
class ClientService
{
    /** Définition des constantes */
    private static $erreur400 = "La requête est incorrecte  (Erreur 400).";
    private static $erreur401 = "Erreur d'Authentification. La clé n'est pas correcte  (Erreur 401).";
    private static $erreur403 = "Vous n’êtes pas autorisé à vous connecter (Erreur 403).";
    private static $erreur404 = "Le service n'a pas trouvé les éléments (Erreur 404).";
    private static $erreur407 = "La requête n'a pas été appliquée à cause d'un manque d'authentification (Erreur 407).";
    private static $erreur414 = "L'URI demandée par le client est plus trop longue (Erreur 414).";
    private static $erreur418 = "«Je suis une théière », je refuse de préparer du café (Erreur 418).";
    private static $erreur422 = " (Erreur 422).";
    private static $erreur429 = "Le client a envoyé trop de requêtes en un temps donné (Erreur 429).";
    private static $erreur500 = "Le serveur a rencontré un problème inattendu qui l'empêche de répondre à la requête (Erreur 500).";
    private static $erreur502 = "Le serveur, agissant comme une passerelle ou un proxy, a reçu une réponse invalide (Erreur 502).";
    private static $erreur504 = "Temps d’attente d’une réponse écoulé... (Erreur 504).";
    private static $erreur505 = "La version du protocole HTTP utilisée dans la requête n'est pas prise en charge par le serveur (Erreur 505).";

    private static $responseData = "Response Data: ";

    private static $exceptionInattendu = "Chemin d'exécution inattendu dans ";

    public function __construct(
        private HttpClientInterface $client,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
    ) {
        $this->client = $client;
        $this->params = $params;
    }

    /**
     * [Description for handleTimeoutException]
     *
     * @param TimeoutException $e
     *
     * @return array
     *
     * Created at: 30/12/2024 08:05:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function handleTimeoutException(TimeoutException $e): array {
        $this->logger->error("[handleTimeoutException] ❌ ". static::$erreur504, [
                'code' => 504,
                'erreur' => $e->getMessage() ? $e->getMessage() : null,
            ]);
        return ['code' => 504, 'erreur' => static::$erreur504];
    }

    private const ERROR_TRANSPORT_MESSAGES = [
        'Failed to open stream' => "Le service est actuellement indisponible. Impossible d'établir une connexion (Erreur 503).",
        'Could not resolve host' => "La résolution DNS n'a pas permis d'accéder au serveur SonarQube (Erreur 503).",
        'Invalid HTTP proxy' => "L'adresse définit pour le proxy n'est pas correcte (Erreur 503)."
    ];

    /**
     * [Description for handleTransportException]
     *
     * @param TransportException $e
     *
     * @return array
     *
     * Created at: 30/12/2024 08:05:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function handleTransportException(TransportException $e): array {
        $errorMessage = "Erreur 503|504 de transport non spécifiée.";
        $message = $e->getMessage();
        $isCustomMessage = false;

        foreach (self::ERROR_TRANSPORT_MESSAGES as $key => $customMsg) {
            if (strpos($message, $key) !== false) {
                $errorMessage = $customMsg;
                $isCustomMessage = true;
                break;
            }
        }

        if ($isCustomMessage) {
            $code = 503;
            $this->logger->error("[handleTransportException] ❌ $errorMessage", [
                'code' => $code,
                'erreur' => $e->getMessage() ? $e->getMessage() : null,
            ]);
        } else {
            $code = 504;
            $this->logger->error("[handleTransportException] ❌ $errorMessage", [
                'code' => $code,
                'erreur' => $e->getMessage() ? $e->getMessage():null,
            ]);
        }

        return [ 'code' => $code, 'erreur' => $errorMessage ];
    }

    /**
     * [Description for handleClientException]
     *
     * @param ClientException $e
     *
     * @return array
     *
     * Created at: 30/12/2024 08:05:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function handleClientException(HttpExceptionInterface $e): array {
        $response = $e->getResponse();
        $body = $response->getContent(false);
        $errorCode = $e->getCode();
        $firstTenLines = null;

        // Décoder le JSON en tableau associatif
        $data = json_decode($body, true);
        // Vérifier si 'detail' existe dans les données
        if (isset($data['detail']) && is_array($data['detail'])) {
            $firstTenLines = substr($body, 0, 1000);
        } else {
            $this->logger->error("[handleClientException] ❌ Aucune donnée dans le bloc 'detail'.");
        }

        $errorMessage = match ($errorCode) {
            400 => static::$erreur400,
            401 => static::$erreur401,
            403 => static::$erreur403,
            404 => static::$erreur404,
            407 => static::$erreur407,
            414 => static::$erreur414,
            418 => static::$erreur418,
            422 => static::$erreur422,
            429 => static::$erreur429,
            default => "Erreur du client non spécifiée (Erreur {$errorCode}).",
        };

        $this->logger->error("[handleClientException] ❌ Une erreur non spécifiée est survenue. ", [
            'code' => $errorCode,
            'body' => $firstTenLines
        ]);
        return ['code' => $errorCode, 'erreur' => $errorMessage];
    }

    /**
     * [Description for handleServerException]
     *
     * @param ServerException $e
     *
     * @return array
     *
     * Created at: 30/12/2024 08:05:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function handleServerException(ServerException $e): array {
        $response = $e->getResponse();
        $body = $response->getContent(false);
        $errorCode = $e->getCode();
        $firstTenLines = null;

        // Décoder le JSON en tableau associatif
        $data = json_decode($body, true);
        // Vérifier si 'detail' existe dans les données
        if (isset($data['detail']) && is_array($data['detail'])) {
            $firstTenLines = substr($body, 0, 1000);
        } else {
            $this->logger->error("[handleServerException] ❌ Aucune donnée dans le bloc 'detail'.");
        }

        $errorMessage = match ($errorCode) {
            500 => static::$erreur500,
            502 => static::$erreur502,
            505 => static::$erreur505,
            default => "Une erreur non spécifiée est survenue (Erreur {$errorCode}).",
        };

        $this->logger->error("[handleServerException] ❌ Une erreur non spécifiée est survenue. ", [
            'code' => $errorCode,
            'body' => $firstTenLines
        ]);
        return ['code' => $errorCode, 'erreur' => $errorMessage];
    }

    /**
     * [Description for handleGenericException]
     *
     * @param \Exception $e
     *
     * @return array
     *
     * Created at: 19/10/2025 09:09:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function handleGenericException(\Exception $e): array {
        $this->logger->error("Une erreur inattendue du serveur s'est produite : " . $e->getMessage());

        $messages = [
            'key1' => 'Environment variable not found: "CIPHERS".',
            'key2' => 'Environment variable not found: "VERIFY_HOST".',
            'key3' => 'Environment variable not found: "VERIFY_PEER".'
        ];

        $message = match (true) {
            strpos($e->getMessage(), $messages['key1']) !== false => "La variable 'CIPHERS' n'a pas été définie correctement.",
            strpos($e->getMessage(), $messages['key2']) !== false => "La variable 'VERIFY_HOST' n'a pas été définie correctement.",
            strpos($e->getMessage(), $messages['key3']) !== false => "La variable 'VERIFY_PEER' n'a pas été définie correctement.",
            default => "Une erreur globale inattendue du serveur s'est produite (Erreur 500).",
        };

        return ['code' => 500, 'erreur' => $message];
    }

    /**
     * [Description for genericHeaders]
     *
     * @return array
     *
     * Created at: 30/10/2024 17:43:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericHeaders(): array
    {
        return [
            'Cache-Control' => 'no-cache',
            'Accept' => '*/*',
            'Content-Type' => 'application/json',
            'X-Powered-By' => 'Ma-Moulinette'
        ];
    }

    /**
     * [Description for http]
     *
     * @param string $url
     *
     * @return array
     *
     * Created at: 17/04/2024 18:46:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function httpSonarQube(string $url): array
    {
        if (empty($this->params->get('sonar.token')) && empty($this->params->get('sonar.user'))){
            return [
                    'code' => 401,
                    'erreur' => static::$erreur401
                    ];
        }

        if (empty($this->params->get('sonar.token'))) {
            $user = $this->params->get('sonar.user');
            $password = $this->params->get('sonar.password');
        } else {
            $user = $this->params->get('sonar.token');
            $password = '';
        }

        try {
            $ciphers = $this->params->get('ciphers');
            $verify_host = $this->params->get('verify.host');
            $verify_peer =$this->params->get('verify.peer');

            $options = [
                'auth_basic' => [$user, $password],
                'timeout' => 45,
                'headers' => static::genericHeaders(),
                # Définition des ciphers TLS1.3
                'ciphers' => $ciphers,
                # vérification des certificats
                'verify_host' => $verify_host,
                'verify_peer' => $verify_peer
            ];

            /** On a ajoute le proxy aux options s'il est défini*/
            $proxy = $this->params->get('proxy');
            if (!empty($proxy)){
                $options['proxy']=$proxy;
            }

            $json = null;

            $response = $this->client->request('GET', $url, $options);
            $statusCode = $response->getStatusCode();
            // On lève une erreur si possible catché par le handler.
            $response->getContent();

            try {
                // On récupère toujours le corps brut, sans lever d'exception
                $responseBody = $response->getContent(false);
                $json = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException $e) {
                // pas un JSON valide : on créé un tableau pour le texte brute
                $json = $json = !empty(trim($responseBody))
                        ? ['texte' => $responseBody]
                        : [ 'texte' => null ];
            }

            /** Extraire les informations de la réponse */
            $httpMethod = $response->getInfo('http_method');
            $httpCode = $response->getInfo('http_code');
            $totalTime = $response->getInfo('total_time');
            $url = $response->getInfo('url') . " - " . static::$responseData . substr($responseBody, 0, 100);

            // Log des informations
            $message = "[" . $httpMethod . "] - " . $httpCode . " - " . $totalTime . " - " . $url;
            $this->logger->info("[API-INFO] 📶 $message");

            return [
                'code' => $statusCode,
                'message' => $message,
                'json' =>  $json,
                'erreur' => "L'exception n'a pas été catché correctement par le handler (Erreur sympathique)."
            ];
        } catch (TimeoutException $e) {
            return $this->handleTimeoutException($e);
        } catch (TransportException $e) {
            return $this->handleTransportException($e);
        } catch (HttpExceptionInterface $e) {
            if ($e instanceof ClientException) {
                return $this->handleClientException($e);
            } elseif ($e instanceof ServerException) {
                return $this->handleServerException($e);
            }
        } catch (\Exception $e) {
            return $this->handleGenericException($e);
        }

        // Ce point ne devrait jamais être atteint, donc on peut lancer une exception.
        throw new UnexpectedExecutionPathException(static::$exceptionInattendu . __METHOD__);
    }

    /**
     * [Description for httpActuator]
     *
     * @param string $url
     * @param string $user
     * @param string $password
     *
     * @return array
     *
     * Created at: 27/06/2024 21:02:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function httpActuator(string $url, string $user, string $password): array
    {
        try {
            $ciphers = $this->params->get('ciphers');
            $verify_host = $this->params->get('verify.host');
            $verify_peer =$this->params->get('verify.peer');

            /** Options sans Auth_http_basic */
            $options=[
                'timeout' => 45,
                'headers' => static::genericHeaders(),
                # Définition des ciphers TLS1.3
                'ciphers' => $ciphers,
                # vérification des certificats
                'verify_host' => $verify_host,
                'verify_peer' => $verify_peer
            ];

            /** Si on a un login/password défini avec actuator */
            $authHttpBasic=['auth_basic' => [$user, $password]];

            /** On ajout Auth_http_basic si $user&&$password != null */
            if($user!=null && $password!=null) {
                $options=array_merge($options, $authHttpBasic);
            }

            $response = $this->client->request('GET', $url, $options);

            /** Si tout va bien, ajoute une trace dans les logs */
            $responseJson = $response->getContent();

            /** Extraire les informations de la réponse */
            $httpMethod = $response->getInfo('http_method');
            $httpCode = $response->getInfo('http_code');
            $totalTime = $response->getInfo('total_time');
            $url = $response->getInfo('url') . " - " .
                static::$responseData . substr($responseJson, 0, 100);

            // Log des informations
            $message = "[" . $httpMethod . "] - " . $httpCode . " - " . $totalTime . " - " . $url;

            $this->logger->info($message);
        } catch (TimeoutException $e) {
            return $this->handleTimeoutException($e);
        } catch (TransportException $e) {
            return $this->handleTransportException($e);
        } catch (HttpExceptionInterface $e) {
            if ($e instanceof ClientException) {
                return $this->handleClientException($e);
            } elseif ($e instanceof ServerException) {
                return $this->handleServerException($e);
            }
        } catch (\Exception $e) {
            return $this->handleGenericException($e);
        }
        return [
            'message' => $message,
            'code' => $response->getStatusCode(),
            'json' => json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR)
        ];
    }

    /**
     * [Description for httpActivity]
     *
     * @param string $url
     *
     * @return array
     *
     * Created at: 22/05/2024 15:03:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function httpActivity(string $url): array
    {
        if (empty($this->params->get('sonar.activity.token')) && empty($this->params->get('sonar.activity.user'))){
            return [
                'code'=> 401,
                'erreur' => static::$erreur401
            ];
        }

        if (empty($this->params->get('sonar.activity.token'))) {
            $user = $this->params->get('sonar.activity.user');
            $password = $this->params->get('sonar.activity.password');
        } else {
            $user = $this->params->get('sonar.activity.token');
            $password = '';
        }

        try {
            $ciphers = $this->params->get('ciphers');
            $verify_host = $this->params->get('verify.host');
            $verify_peer =$this->params->get('verify.peer');

            $options = [
                'auth_basic' => [$user, $password],
                'timeout' => 10,
                'headers' => static::genericHeaders(),
                # Définition des ciphers TLS1.3
                'ciphers' => $ciphers,
                # vérification des certificats
                'verify_host' => $verify_host,
                'verify_peer' => $verify_peer
            ];

            /** On a ajoute le proxy aux options s'il est défini*/
            $proxy = $this->params->get('proxy');
            if (!empty($proxy)){
                $options['proxy']=$proxy;
            }

            $response = $this->client->request('GET', $url, $options);

            /** Si tout va bien, ajoute une trace dans les logs */
            $responseJson = $response->getContent();

            /** Extraire les informations de la réponse */
            $httpMethod = $response->getInfo('http_method');
            $httpCode = $response->getInfo('http_code');
            $totalTime = $response->getInfo('total_time');
            $url = $response->getInfo('url') . " - " .
                static::$responseData . substr($responseJson, 0, 100);

            // Log des informations
            $message = "[" . $httpMethod . "] - " . $httpCode . " - " . $totalTime . " - " . $url;

            $this->logger->info($message);
            return [
                'message' => $message,
                'code' => $response->getStatusCode(),
                'json' => json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR)
            ];
        } catch (TimeoutException $e) {
            return $this->handleTimeoutException($e);
        } catch (TransportException $e) {
            return $this->handleTransportException($e);
        } catch (HttpExceptionInterface $e) {
            if ($e instanceof ClientException) {
                return $this->handleClientException($e);
            } elseif ($e instanceof ServerException) {
                return $this->handleServerException($e);
            }
        } catch (\Exception $e) {
            return $this->handleGenericException($e);
        }

        // Ce point ne devrait jamais être atteint, donc on peut lancer une exception.
        throw new UnexpectedExecutionPathException(static::$exceptionInattendu . __METHOD__);
    }

}
