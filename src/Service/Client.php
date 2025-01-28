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
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

use \Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Component\HttpClient\Exception\ServerException;
use Symfony\Component\HttpClient\Exception\TransportException;

/** Logger */
use Psr\Log\LoggerInterface;

/**
 * [Description Client]
 */
class Client
{
    /** Définition des constantes */
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

    public static $responseData="Response Data: ";
    public static $erreurTransport = "Erreur de transport : ";

    public function __construct(
        private HttpClientInterface $client,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
    ) {
        $this->client = $client;
        $this->params = $params;
        $this->logger = $logger;
    }

    private function handleTimeoutException(TimeoutException $e): array {
        $this->logger->error(static::$erreurTransport . $e->getMessage());
        return ['code' => 504, 'erreur' => static::$erreur504];
    }

    private const ERROR_TRANSPORT_MESSAGES = [
        'Failed to open stream' => "Erreur 503 - Le service est actuellement indisponible. Impossible d'établir une connexion.",
        'Could not resolve host' => "Erreur 503 - La résolution DNS n'a pas permis d'accéder au serveur SonarQube.",
        'Invalid HTTP proxy' => "Erreur 503 - L'adresse définit pour le proxy n'est pas correcte."
    ];

    private function handleTransportException(TransportException $e): array {
        $errorMessage = "Erreur 503|504 de transport non spécifiée. | Détails : " . $e->getMessage();
        $customMessage = false;

        foreach (self::ERROR_TRANSPORT_MESSAGES as $key => $customMsg) {
            if (strpos($errorMessage, $key) !== false) {
                $errorMessage = $customMsg;
                $customMessage = true;
                break;
            }
        }

        if ($customMessage) {
            $this->logger->error(static::$erreurTransport . $errorMessage);
            $code=503;
        } else {
            $this->logger->error(static::$erreurTransport . $errorMessage . " | Détails : " . $e->getMessage());
            $code=504;
        }

        return ['code' => $code, 'erreur' => $errorMessage];
    }

    private function handleClientException(HttpExceptionInterface $e): array {
        $response = $e->getResponse();
        $body = $response->getContent(false);
        $errorCode = $e->getCode();

        $errorMessage = match ($errorCode) {
            400 => static::$erreur400,
            401 => static::$erreur401,
            403 => static::$erreur403,
            404 => static::$erreur404,
            407 => static::$erreur407,
            414 => static::$erreur414,
            418 => static::$erreur418,
            429 => static::$erreur429,
            default => "Erreur du client non spécifiée.",
        };

        $this->logger->error("Erreur ".$errorCode . " du client : " . $body);
        return ['code' => $errorCode, 'erreur' => $errorMessage];
    }

    private function handleServerException(HttpExceptionInterface $e): array {
        $response = $e->getResponse();
        $body = $response->getContent(false);
        $errorCode = $e->getCode();

        $errorMessage = match ($errorCode) {
            500 => static::$erreur500,
            502 => static::$erreur502,
            505 => static::$erreur505,
            default => "Erreur du serveur non spécifiée.",
        };
        $this->logger->error("Erreur ".$errorCode . " du serveur : " . $body);
        return ['code' => $errorCode, 'erreur' => $errorMessage];
    }

    private function handleGenericException(\Exception $e): array {
        $this->logger->error("Une erreur inattendue du serveur s'est produite : " . $e->getMessage());

        $key = 'Environment variable not found: "CIPHERS".';
        $message = "Une erreur inattendue du serveur s'est produite (Erreur 500).";
        if (strpos($e->getMessage(), $key) !== false){
            $message = "La variable 'ciphers' n'a pas été définie correctement.";
        }
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
            return ['code'=> 401];
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
    public function httpActivity($url): array
    {
        if (empty($this->params->get('sonar.activity.token')) && empty($this->params->get('sonar.activity.user'))){
            return ['code'=> 401, 'erreur' => static::$erreur401];
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

    }

}
