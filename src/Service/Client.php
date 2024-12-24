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
    public static $erreur400="Erreur 400 - La requête est incorrecte.";
    public static $erreur401="Erreur 401 - Erreur d'Authentification. La clé n'est pas correcte.";
    public static $erreur403="Erreur 403 - Vous n’êtes pas autorisé à vous connecter.";
    public static $erreur404="Erreur 404 - Le service n'a pas trouvé les éléments.";
    public static $erreur500="Erreur 500 - Le fichier JSON n'est pas valide.";
    public static $erreur503="Erreur 503 - Le service est actuellement indisponible. Impossible d'établir une connexion.";
    public static $erreur504="Erreur 504 - Temps d’attente d’une réponse écoulé...";

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
        $this->logger->error("Erreur de transport : " . $e->getMessage());
        return ['code' => 504, 'erreur' => static::$erreur504];
    }

    private function handleTransportException(TransportException $e): array {
        $errorMessage = $e->getMessage() ?: "Erreur de transport non spécifiée.";

        if (strpos($errorMessage, "Failed to open stream") !== false) {
            $errorMessage = "Erreur 503 - Le service est actuellement indisponible. Impossible d'établir une connexion.";
        } elseif (strpos($errorMessage, "Could not resolve host") !== false) {
            $errorMessage = "Erreur 503 - La résolution DNS n'a pas permis d'accéder au serveur SonarQube.";
        } elseif (strpos($errorMessage, "Invalid HTTP proxy") !== false) {
            $errorMessage = "Erreur 503 - L'adresse définit pour le proxy n'est pas correcte.";
        }

        $this->logger->error("Erreur de transport (Erreur 504) : " . $e->getMessage());
        return ['code' => 504, 'erreur' => $errorMessage];
    }

    private function handleClientException(ClientException $e): array {
        $response = $e->getResponse();
        $body = $response->getContent(false);
        $errorCode = $e->getCode();
        $errorMessage = match ($errorCode) {
            400 => static::$erreur400,
            401 => static::$erreur401,
            403 => static::$erreur403,
            404 => static::$erreur404,
            default => "Erreur client non spécifiée.",
        };
        $this->logger->error("Erreur du client : " . $body);
        return ['code' => $errorCode, 'erreur' => $errorMessage];
    }

    private function handleServerException(ServerException $e): array {
        $response = $e->getResponse();
        $body = $response->getContent(false);
        $errorCode = $e->getCode();
        $errorMessage = "Le service est indisponible (Erreur 500).";

        $this->logger->error($body);
        return ['code' => $errorCode, 'erreur' => $errorMessage];
    }

    private function handleGenericException(\Exception $e): array {
        $this->logger->error("Erreur inattendue : " . $e->getMessage());
        return ['code' => 500, 'erreur' => "Une erreur inattendue s'est produite."];
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
            $message = "[" . $response->getInfo('http_method') . "] - " .
                $response->getInfo('http_code') . " - " .
                $response->getInfo('total_time') . " - " .
                $response->getInfo('url');
            $this->logger->info($message);

            $responseJson = $response->getContent();
            return [
                    'message' => $message,
                    'code' => $response->getStatusCode(),
                    'json' => json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR)
                ];
        } catch (TimeoutException $e) {
            return $this->handleTimeoutException($e);
        } catch (TransportException $e) {
            return $this->handleTransportException($e);
        } catch (ClientException $e) {
            return $this->handleClientException($e);
        } catch (ServerException $e) {
            return $this->handleServerException($e);
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
            $message = "[" . $response->getInfo('http_method') . "] - " .
                $response->getInfo('http_code') . " - " .
                $response->getInfo('total_time') . " - " .
                $response->getInfo('url');
            $this->logger->info($message);

            /** On retourne la réponse. */
            $responseJson = $response->getContent();
            return [
                'message' => $message,
                'code' => $response->getStatusCode(),
                'json' => json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR)
            ];
        } catch (TimeoutException $e) {
            return $this->handleTimeoutException($e);
        } catch (TransportException $e) {
            return $this->handleTransportException($e);
        } catch (ClientException $e) {
            return $this->handleClientException($e);
        } catch (ServerException $e) {
            return $this->handleServerException($e);
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
            $message = "[" . $response->getInfo('http_method') . "] - " .
                $response->getInfo('http_code') . " - " .
                $response->getInfo('total_time') . " - " .
                $response->getInfo('url');
            $this->logger->info($message);

            $responseJson = $response->getContent();
            return [
                    'message' => $message,
                    'code' => $response->getStatusCode(),
                    'json' => json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR)
                ];
        } catch (TimeoutException $e) {
            return $this->handleTimeoutException($e);
        } catch (TransportException $e) {
            return $this->handleTransportException($e);
        } catch (ClientException $e) {
            return $this->handleClientException($e);
        } catch (ServerException $e) {
            return $this->handleServerException($e);
        } catch (\Exception $e) {
            return $this->handleGenericException($e);
        }

    }

}
