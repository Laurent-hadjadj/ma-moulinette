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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

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

            $response = $this->client->request('GET', $url, [
                'auth_basic' => [$user, $password],
                'timeout' => 45,
                'headers' => static::genericHeaders(),
                # Définition des ciphers TLS1.3
                'ciphers' => $ciphers,
                # vérification des certificats
                'verify_host' => $verify_host,
                'verify_peer' => $verify_peer
            ]);

            /** Si tout va bien, ajoute une trace dans les logs */
            $message = "[" . $response->getInfo('http_method') . "] - " .
                $response->getInfo('http_code') . " - " .
                $response->getInfo('total_time') . " - " .
                $response->getInfo('url');
            $this->logger->info($message);

            /** On retourne la réponse. */
            $responseJson = $response->getContent();
            return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
        } catch (TimeoutException $e){
                // Gestion du timeout
                $this->logger->error("Erreur de transport : " . $e->getMessage());
                return ['code' => 504, 'erreur' => static::$erreur504];
        } catch (TransportException $e) {
            $errorMessage = $e->getMessage() ?: 'Erreur de transport non spécifiée.';
            /* Vérifier si l'erreur contient "Failed to open stream" */
            if (strpos($errorMessage, 'Failed to open stream') !== false) {
                // Si l'erreur mentionne "Failed to open stream", on définit un message personnalisé pour l'erreur 503
                $errorMessage = 'Erreur 503 - Le service est actuellement indisponible. Impossible d\'établir une connexion.';
            }
            $this->logger->error("Erreur de transport (503) : " . $errorMessage);
            return ['code' => 503, 'erreur' => $errorMessage];
        } catch (ClientException $e) {
            // Gère les erreurs 4xx (ex: 404, 401, etc.)
            $response=$e->getResponse();
            $body = $response->getContent(false);
            $errorCode = $e->getCode();
            $errorMessage = '';
            switch ($errorCode) {
                case 400:
                    $errorMessage = static::$erreur400;
                    break;
                case 401:
                    $errorMessage = static::$erreur401;
                    break;
                case 404:
                    $errorMessage = static::$erreur404;
                    break;
                default:
                    $errorMessage = "Erreur client non spécifiée.";
                    break;
            }
            $this->logger->error("Erreur du client : " . $body);
            return ['code' => $errorCode, 'erreur' => $errorMessage];
        } catch (ServerException $e) {
            // Gère les erreurs 5xx (ex: 500, 502, etc.)
            $response=$e->getResponse();
            $body = $response->getContent(false);
            $errorCode = $e->getCode();
            $errorMessage = 'Le service est indisponible (Erreur 500).';
            // Journaliser l'erreur
            $this->logger->error($body);

            return ['code' => $errorCode, 'erreur' => $errorMessage];
        } catch (\Exception $e) {
            // Gère toutes les autres exceptions
            $response=$e->getResponse();
            $body = $response->getContent(false);
            $errorCode = $e->getCode();
            $errorMessage = "Une erreur inétendue s'est produite !";

            // Journaliser l'erreur
            $this->logger->error($body);
            return ['code' => $errorCode, 'erreur' => $errorMessage];
        }
    }

    /**
     * [Description for httpActuator]
     *
     * @param string $url
     * @param string $user
     * @param string $password
     *
     * @return Response
     *
     * Created at: 27/06/2024 21:02:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function httpActuator(string $url, string $user, string $password): JsonResponse
    {
        /** Options sans Auth_http_basic */
        $options=[
            'timeout' => 45,
            'headers' => static::genericHeaders(),
        ];

        /** Si on a un login/password défini avec actuator */
        $authHttpBasic=['auth_basic' => [$user, $password]];

        /** on ajout Auth_http_basic si $user&&$password != null */
        if($user!=null && $password!=null) {
            $options=array_merge($options, $authHttpBasic);
        }

        $response = $this->client->request('GET', $url, $options);
        /** catch les erreurs 400, 404, les erreurs 401 et les autres génère une erreur 500 */
        if (200 !== $response->getStatusCode()) {
            if ($response->getStatusCode() == 400) {
                $this->logger->ERROR(static::$erreur400);
                return new JsonResponse(['code'=> 400, 'erreur'=>static::$erreur400], Response::HTTP_OK);
            }
            if ($response->getStatusCode() == 401) {
                $this->logger->ERROR(static::$erreur401);
                return new JsonResponse(['code'=> 401, 'erreur'=>static::$erreur401], Response::HTTP_OK);
            }
            if ($response->getStatusCode() == 403) {
                $this->logger->ERROR(static::$erreur403);
                return new JsonResponse(['code'=> 403, 'erreur'=>static::$erreur403], Response::HTTP_OK);
            }
            if ($response->getStatusCode() == 404) {
                $this->logger->ERROR(static::$erreur404);
                return new JsonResponse(['code'=> 404, 'erreur'=>static::$erreur404], Response::HTTP_OK);
            }
        }

        /** Si tous va bien on ajoute une trace dans les log */
        $message = "[".$response->getInfo('http_method')."] - ".
                    $response->getInfo('http_code')." - ".
                    $response->getInfo('total_time')." - ".
                    $response->getInfo('url');
        $this->logger->INFO($message);

        /** On retourne la réponse. */
        return new JsonResponse(json_decode($response->getContent(), JSON_THROW_ON_ERROR), Response::HTTP_OK);
    }

}
