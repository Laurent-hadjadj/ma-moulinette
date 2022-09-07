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

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

// Accès aux tables SLQLite
use App\Entity\Main\ListeProjet;
use App\Entity\Main\Profiles;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;
use DateTime;

// Logger
use Psr\Log\LoggerInterface;


class ApiHomeController extends AbstractController
{

  private $client;

  public function __construct(HttpClientInterface $client)
  {
    $this->client = $client;
  }

  public static $strContentType = 'application/json';
  public static $dateFormat = "Y-m-d H:m:s";
  public static $sonarUrl = "sonar.url";

  /**
   * httpClient
   *
   * @param  mixed $url
   * @return void
   */
  protected function httpClient($url, LoggerInterface $logger)
  {
    // On peut se connecter avec un user/password ou un token. Nous on préfère le token.
    if (empty($this->getParameter('sonar.token'))) {
      $user = $this->getParameter('sonar.user');
      $password = $this->getParameter('sonar.password');
    } else {
      $user = $this->getParameter('sonar.token');
      $password = '';
    }

    $response = $this->client->request('GET', $url,
      [
        'ciphers' => 'AES128-SHA AES256-SHA DH-DSS-AES128-SHA DH-DSS-AES256-SHA DH-RSA-AES128-SHA DH-RSA-AES256-SHA DHE-DSS-AES128-SHA DHE-DSS-AES256-SHA DHE-RSA-AES128-SHA DHE-RSA-AES256-SHA ADH-AES128-SHA ADH-AES256-SHA',
        'auth_basic' => [$user, $password], 'timeout' => 45,
        'headers' => ['Accept' => static::$strContentType,
        'Content-Type' => static::$strContentType]
      ]
    );

    //Si la réponse est différente de HTTP: 200 alors...
    if (200 !== $response->getStatusCode()) {
      // Le token ou le password n'est pas correct.
      if ($response->getStatusCode() == 401) {
        throw new \Exception('Erreur d\'Authentification. La clé n\'est pas correcte.');
      } else {
        throw new \Exception('Retour de la réponse différent de ce qui est prévu. Erreur ' .
          $response->getStatusCode());
      }
    }

    $contentType = $response->getHeaders()['content-type'][0];
    $logger->INFO('** ContentType *** '.isset($contentType));
    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
  }

  /**
   * sonar_status
   * Vérifie si le serveur sonarqube est UP
   * http://{url}}/api/system/status
   *
   * @return void
   */
  #[Route('/api/status', name: 'sonar_status', methods: ['GET'])]
  public function sonarStatus(LoggerInterface $logger)
  {
    $url = $this->getParameter(static::$sonarUrl) . "/api/system/status";

    // on appel le client http
    $result = $this->httpClient($url, $logger);

    return new JsonResponse($result, Response::HTTP_OK);
  }

/**
   * information_systeme
   * On récupère les informations système du serveur
   * http://{url}}/api/system/info
   *
   * Attention, il faut avoir le role sonar administrateur
   *
   * @return void
   */
  #[Route('/api/system/info', name: 'information_systemes', methods: ['GET'])]
  public function informationSysteme(LoggerInterface $logger)
  {
    $url = $this->getParameter(static::$sonarUrl) . "/api/system/info";

    // on appel le client http
    $result = $this->httpClient($url, $logger);
    return new JsonResponse($result, Response::HTTP_OK);
  }

  /**
   * liste_projet
   * Récupération de la liste des projets.
   * http://{url}}/api/components/search?qualifiers=TRK&ps=500
   *
   * @param  mixed $em
   * @return response
   */
  #[Route('/api/liste_projet/ajout', name: 'liste_projet_ajout', methods: ['GET'])]
  public function listeProjet(EntityManagerInterface $em, ManagerRegistry $doctrine, LoggerInterface $logger): response
  {
    $url = $this->getParameter(static::$sonarUrl) . "/api/components/search?qualifiers=TRK&ps=500&p=1";

    // on appel le client http
    $result = $this->httpClient($url, $logger);

    // On récupère le manager de BD
    $date = new DateTime();
    $nombreProjet = 0;

    // On supprime les données de la table avant d'importer les données;
    $sql = "DELETE FROM liste_projet";
    $delete = $em->getConnection()->prepare($sql);
    $delete->executeQuery();

    // On insert les projets dans la tale liste_projet.
    foreach ($result["components"] as $component) {
      // On exclue les projets archivés avec la particule "-SVN".
      //"project": "fr.domaine:mon-application-SVN"
      $mystring = $component["project"];
      $findme   = '-SVN';
      if (!strpos($mystring, $findme)) {
        $nombreProjet = $nombreProjet + 1;
        $listeProjet = new ListeProjet();
        $listeProjet->setName($component["name"]);
        $listeProjet->setMavenKey($component["project"]);
        $listeProjet->setDateEnregistrement($date);
        $em->persist($listeProjet);
        $em->flush();
      }
    }
    $response = new JsonResponse();
    $response->setData(["nombreProjet" => $nombreProjet, Response::HTTP_OK]);
    return $response;
  }

  /**
   * liste_projet_date
   * récupère la date de mise à jour du référentiel
   * http://{url}}/api/liste_projet/date
   *
   * @param  mixed $connection
   * @return void
   */
  #[Route('/api/liste_projet/date', name: 'liste_projet_date', methods: ['GET'])]
  public function listeProjetDate(Connection $connection)
  {

    $sql = "SELECT date_enregistrement as date from 'liste_projet' ASC LIMIT 1";
    $rq1 = $connection->fetchAllAssociative($sql);

    if (!$rq1) {
      $dateCreation = 0;
      $nombreProjet = 0;
    } else {
      $sql = "SELECT COUNT(*) as total from 'liste_projet'";
      $rq2 = $connection->fetchAllAssociative($sql);
      $dateCreation = $rq1[0]['date'];
      $nombreProjet = $rq2[0]['total'];
    }

    $response = new JsonResponse();
    $response->setData(["dateCreation" => $dateCreation, "nombreProjet" => $nombreProjet, Response::HTTP_OK]);
    return $response;
  }

  /**
   * liste_quality_profiles
   * Renvoie la liste des profils qualité
   * http://{url}/api/qualityprofiles/search?qualityProfile={name}
   *
   * @param  mixed $em
   * @return response
   */
  #[Route('/api/quality/profiles', name: 'liste_quality_profiles', methods: ['GET'])]
  public function listeQualityProfiles(EntityManagerInterface $em, LoggerInterface $logger): response
  {
    $url = $this->getParameter(static::$sonarUrl)
          . "/api/qualityprofiles/search?qualityProfile="
          . $this->getParameter('sonar.profiles');

    // on appel le client http
    $result = $this->httpClient($url, $logger);

    // On récupère le manager de BD
    $date = new DateTime();
    $nombreProfil = 0;

    // On supprime les données de la table avant d'importer les données;
    $sql = "DELETE FROM profiles";
    $delete = $em->getConnection()->prepare($sql);
    $delete->executeQuery();

    // On insert les profiles dans la table profiles.
    foreach ($result["profiles"] as $profil) {
      $nombreProfil = $nombreProfil + 1;

      $profils = new Profiles();
      $profils->setKey($profil["key"]);
      $profils->setName($profil["name"]);
      $profils->setLanguageName($profil["languageName"]);
      $profils->setIsDefault($profil["isDefault"]);
      $profils->setActiveRuleCount($profil["activeRuleCount"]);
      $rulesDate = new DateTime($profil["rulesUpdatedAt"]);
      $profils->setRulesUpdateAt($rulesDate);
      $profils->setDateEnregistrement($date);
      $em->persist($profils);
      $em->flush();
    }

    // On récupère la liste des profiles;
    $sql = "SELECT name as profil, language_name as langage,
            active_rule_count as regle, rules_update_at as date,
            is_default as actif FROM profiles";
    $select = $em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
    $liste = $select->fetchAllAssociative();

    $response = new JsonResponse();
    return $response->setData(["listeProfil" => $liste, Response::HTTP_OK]);
  }

  /**
   * nombre_profil
   * Renvoi le nombre de profil
   *
   * @param  mixed $em
   * @return response
   */
  #[Route('/api/quality', name: 'nombre_profil', methods: ['GET'])]
  public function nombreProfil(EntityManagerInterface $em): response
  {
    // On récupère le nombre de profil dans la table profiles;
    $sql = "SELECT count(*) as nombre FROM profiles";
    $select = $em->getConnection()->prepare($sql)->executeQuery();
    $result = $select->fetchAllAssociative();
    if (empty($result)) {
      $nombre = 0;
    } else {
      $nombre = $result[0]["nombre"];
    }

    $response = new JsonResponse();
    return $response->setData(["nombre" => $nombre, Response::HTTP_OK]);
  }

  /**
   * visibility
   * Renvoi le nombre de projet private ou public
   * Il faut avoir un droit Administrateur !!!
   * http://{url}/api/projects/search?qualifiers=TRK&ps=500
   *
   * @param  mixed $em
   * @return response
   */
  #[Route('/api/visibility', name: 'visibility', methods: ['GET'])]
  public function visibility(LoggerInterface $logger): response
  {
    $url = $this->getParameter(static::$sonarUrl) .
          "/api/projects/search?qualifiers=TRK&ps=500";

    // on appel le client http
    $components = $this->httpClient($url, $logger);

    $private = 0;
    $public = 0;

    foreach ($components["components"] as $component) {
      if ($component == "private") {
        $private++;
      }
      if ($component == "public") {
        $public++;
      }
    }

    $response = new JsonResponse();
    return $response->setData(
      ["private" => $private, "public" => $public, Response::HTTP_OK]);
  }
}
