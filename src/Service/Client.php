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

class Client
{
  /** Définition des constantes */
  public static $strContentType = 'application/json';
  public static $regex = "/\s+/u";

  public function __construct(
    private HttpClientInterface $client,
    private ParameterBagInterface $params,
    private LoggerInterface $logger,
	) {
      $this->client = $client;
      $this->params = $params;
      $this->logger = $logger;
    }


  public function http($url):array
  {
    if (empty($this->params->get('sonar.token'))) {
      $user = $this->params->get('sonar.user');
      $password = $this->params->get('sonar.password');
    } else {
      $user = $this->params->get('sonar.token');
      $password = '';
    }

    /** Fix problème Error:141A318A:SSL routines:tls_process_ske_dhe:dh key too small */
    $ciphers="DEFAULT:!DH";
    $response = $this->client->request(
      'GET', $url,
      [
        'ciphers' => trim(preg_replace(static::$regex, " ", $ciphers)),
        'auth_basic' => [$user, $password], 'timeout' => 45,
        'headers' => [
        'Accept' => static::$strContentType,
        'Content-Type' => static::$strContentType
        ]
      ]
    );

    if (200 !== $response->getStatusCode()) {
      if ($response->getStatusCode() == 401) {
        throw new \UnexpectedValueException('Erreur d\'Authentification. La clé n\'est pas correcte.');
      } else {
        throw new \UnexpectedValueException('Retour de la réponse différent de ce qui est prévu. Erreur ' . $response->getStatusCode());
      }
    }

    $contentType = $response->getHeaders()['content-type'][0];
    $this->logger->INFO('** ContentType *** '.isset($contentType));
    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
  }
}
