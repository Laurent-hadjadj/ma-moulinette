<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
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

/** Logger */
use Psr\Log\LoggerInterface;

/**
 * [Description Client]
 */
class Client
{
    /** Définition des constantes */
    public static $strContentType = 'application/json';
    public static $regex = "/\s+/u";
    public static $erreur400="Erreur 400 - L'URL n'est pas correcte.";
    public static $erreur401="Erreur 401 - Erreur d'Authentification. La clé n'est pas correcte.";
    public static $erreur404="Erreur 404 - Le service n'a pas trouvé les éléments.";

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
    public function http($url): array
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
        /** Fix problème Error:141A318A:SSL routines:tls_process_ske_dhe:dh key too small */
        $ciphers = "DEFAULT:!DH";
        $response = $this->client->request('GET', $url,
            [
                'ciphers' => trim(preg_replace(static::$regex, " ", $ciphers)),
                'auth_basic' => [$user, $password], 'timeout' => 45,
                'headers' => [
                    'Accept' => static::$strContentType,
                    'Content-Type' => static::$strContentType,
                    "verify_peer" => 0, "verify_host" => 0
                ]
            ]
        );
        /** catch les erreurs 400, 404 les erreurs 401 et eutres génére une erreur 500 */
        if (200 !== $response->getStatusCode()) {
            if ($response->getStatusCode() == 400) {
                $this->logger->ERROR(static::$erreur400);
                return ['code'=> 400, 'erreur'=>static::$erreur400];
            }
            if ($response->getStatusCode() == 401) {
                $this->logger->ERROR(static::$erreur401);
                return ['code'=> 401, 'erreur'=>static::$erreur401];
            }
            if ($response->getStatusCode() == 404) {
                $this->logger->ERROR(static::$erreur404);
                return ['code'=> 404, 'erreur'=>static::$erreur404];
            }
        }


        /** Si tous va bien on ajoute une trace dans les log */
        $message="[".$response->getInfo('http_method')."] - ".
                    $response->getInfo('http_code')." - ".
                    $response->getInfo('total_time')." - ".
                    $response->getInfo('url');
        $this->logger->INFO($message);

        /** On retourne la réponse. */
        $responseJson = $response->getContent();
        return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
    }

    public function httpActuator($url, $user, $password): array
    {
        /** Fix problème Error:141A318A:SSL routines:tls_process_ske_dhe:dh key too small */
        $ciphers = "DEFAULT:!DH";
        $response = $this->client->request('GET', $url,
            [
                'ciphers' => trim(preg_replace(static::$regex, " ", $ciphers)),
                'auth_basic' => [$user, $password], 'timeout' => 45,
                'headers' => [
                    'Accept' => static::$strContentType,
                    'Content-Type' => static::$strContentType,
                    "verify_peer" => 0, "verify_host" => 0
                ]
            ]
        );
        /** catch les erreurs 400, 404 les erreurs 401 et eutres génére une erreur 500 */
        if (200 !== $response->getStatusCode()) {
            if ($response->getStatusCode() == 400) {
                $this->logger->ERROR(static::$erreur400);
                return ['code'=> 400, 'erreur'=>static::$erreur400];
            }
            if ($response->getStatusCode() == 401) {
                $this->logger->ERROR(static::$erreur401);
                return ['code'=> 401, 'erreur'=>static::$erreur401];
            }
            if ($response->getStatusCode() == 404) {
                $this->logger->ERROR(static::$erreur404);
                return ['code'=> 404, 'erreur'=>static::$erreur404];
            }
        }


        /** Si tous va bien on ajoute une trace dans les log */
        $message="[".$response->getInfo('http_method')."] - ".
                    $response->getInfo('http_code')." - ".
                    $response->getInfo('total_time')." - ".
                    $response->getInfo('url');
        $this->logger->INFO($message);

        /** On retourne la réponse. */
        $responseJson = $response->getContent();
        return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
    }

}
