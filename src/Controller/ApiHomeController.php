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

use DateTime;
use DateTimeZone;
use App\Entity\Main\Tags;
use Psr\Log\LoggerInterface;

/** Gestion de accès aux API */
use App\Entity\Main\Profiles;
use App\Entity\Main\ListeProjet;

/** Accès aux tables SLQLite */
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Logger */
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


class ApiHomeController extends AbstractController
{

  public function __construct(
    private LoggerInterface $logger,
    private HttpClientInterface $client,
    private EntityManagerInterface $em,
    )
  {
    $this->logger = $logger;
    $this->client = $client;
    $this->em = $em;
  }

  public static $strContentType = 'application/json';
  public static $sonarUrl = "sonar.url";
  public static $dateFormatShort = "Y-m-d";
  public static $dateFormat = "Y-m-d H:i:s";

  /**
   * httpClient
   *
   * @param  mixed $url
   * @return reponse
   */
  protected function httpClient($url): array
  {
    /** On peut se connecter avec un user/password ou un token. Nous on préfère le token. */
    if (empty($this->getParameter('sonar.token'))) {
      $user = $this->getParameter('sonar.user');
      $password = $this->getParameter('sonar.password');
    } else {
      $user = $this->getParameter('sonar.token');
      $password = '';
    }

    $response = $this->client->request('GET', $url,
      [
        'ciphers' => `AES128-SHA AES256-SHA DH-DSS-AES128-SHA DH-DSS-AES256-SHA DH-RSA-AES128-SHA
          DH-RSA-AES256-SHA DHE-DSS-AES128-SHA DHE-DSS-AES256-SHA DHE-RSA-AES128-SHA
          DHE-RSA-AES256-SHA ADH-AES128-SHA ADH-AES256-SHA`,
        'auth_basic' => [$user, $password], 'timeout' => 45,
        'headers' => ['Accept' => static::$strContentType,
        'Content-Type' => static::$strContentType]
      ]
    );

    /** Si la réponse est différente de HTTP: 200 alors... */
    if (200 !== $response->getStatusCode()) {
      // Le token ou le password n'est pas correct.
      if ($response->getStatusCode() == 401) {
        throw new \UnexpectedValueException('Erreur d\'Authentification. La clé n\'est pas correcte.');
      } else {
        throw new \UnexpectedValueException('Retour de la réponse différent de ce qui est prévu. Erreur ' .
          $response->getStatusCode());
      }
    }

    $contentType = $response->getHeaders()['content-type'][0];
    $this->logger->INFO('** ContentType *** '.isset($contentType));
    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
  }

  /**
   * sonar_status
   * Vérifie si le serveur sonarqube est UP
   * http://{url}}/api/system/status
   *
   * @return response
   */
  #[Route('/api/status', name: 'sonar_status', methods: ['GET'])]
  public function sonarStatus():response
  {
    $url = $this->getParameter(static::$sonarUrl) . "/api/system/status";

    /** On appel le client http */
    $result = $this->httpClient($url);

    return new JsonResponse($result, Response::HTTP_OK);
  }

  /**
   * sonar_health
   * Vérifie l'état du serveur
   * http://{url}}/api/system/health
   * Encore une fois, c'est null, il faut être admin pour récupérrer le résultat.
   *
   * @return response
   */
  #[Route('/api/health', name: 'sonar_health', methods: ['GET'])]
  public function sonarHealth():response
  {
    $url = $this->getParameter(static::$sonarUrl) . "/api/system/health";

    /** On appel le client http */
    $result = $this->httpClient($url);

    return new JsonResponse($result, Response::HTTP_OK);
  }

/**
   * information_systeme
   * On récupère les informations système du serveur
   * http://{url}}/api/system/info
   *
   * Attention, il faut avoir le role sonar administrateur
   *
   * @return response
   */
  #[Route('/api/system/info', name: 'information_systeme', methods: ['GET'])]
  public function informationSysteme():response
  {
    $url = $this->getParameter(static::$sonarUrl) . "/api/system/info";

    /** On appel le client http */
    $result = $this->httpClient($url);
    return new JsonResponse($result, Response::HTTP_OK);
  }

  /**
   * projet_liste
   * Récupération de la liste des projets.
   * http://{url}}/api/components/search?qualifiers=TRK&ps=500
   * @return response
   */
  #[Route('/api/projet/liste', name: 'projet_liste', methods: ['GET'])]
  public function projetListe(): response
  {
    $url = $this->getParameter(static::$sonarUrl) . "/api/components/search?qualifiers=TRK&ps=500&p=1";

    /** On appel le client http */
    $result = $this->httpClient($url);
    $date = new DateTime();
    $nombre = 0;

    /** On supprime les données de la table avant d'importer les données. */
    $sql = "DELETE FROM liste_projet";
    $delete = $this->em->getConnection()->prepare($sql);
    $delete->executeQuery();

    /**  On compte le nombre de projet et on insert les projets dans la table liste_projet. */
    foreach ($result["components"] as $component) {
      /** On exclue les projets archivés avec la particule "-SVN".
       *  "project": "fr.domaine:mon-application-SVN"
       */
      $mystring = $component["project"];
      $findme   = '-SVN';
      if (!strpos($mystring, $findme)) {
        $nombre = $nombre + 1;
        $listeProjet = new ListeProjet();
        $listeProjet->setName($component["name"]);
        $listeProjet->setMavenKey($component["project"]);
        $listeProjet->setDateEnregistrement($date);
        $this->em->persist($listeProjet);
        $this->em->flush();
      }
    }

    /** On met à jour la table propietes */
    $dateModificationProjet = $date->format("Y-m-d H:m:s");

    $sql="UPDATE properties
    SET projet_bd = ${nombre},
        projet_sonar = ${nombre},
        date_modification_projet = '${dateModificationProjet}'
    WHERE type = 'properties'";
    $this->em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();

    $response = new JsonResponse();
    $response->setData(["nombre" => $nombre, Response::HTTP_OK]);
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
  public function listeQualityProfiles(): response
  {
    $url = $this->getParameter(static::$sonarUrl)
          . "/api/qualityprofiles/search?qualityProfile="
          . $this->getParameter('sonar.profiles');

    /** On appel le client http */
    $result = $this->httpClient($url);

    $date = new DateTime();
    $nombre = 0;

    /** On supprime les données de la table avant d'importer les données;*/
    $sql = "DELETE FROM profiles";
    $delete = $this->em->getConnection()->prepare($sql);
    $delete->executeQuery();

    /** On insert les profiles dans la table profiles. */
    foreach ($result["profiles"] as $profil) {
      $nombre = $nombre + 1;

      $profils = new Profiles();
      $profils->setKey($profil["key"]);
      $profils->setName($profil["name"]);
      $profils->setLanguageName($profil["languageName"]);
      $profils->setIsDefault($profil["isDefault"]);
      $profils->setActiveRuleCount($profil["activeRuleCount"]);
      $rulesDate = new DateTime($profil["rulesUpdatedAt"]);
      $profils->setRulesUpdateAt($rulesDate);
      $profils->setDateEnregistrement($date);
      $this->em->persist($profils);
      $this->em->flush();
    }

    /** On récupère la liste des profils; */
    $sql = "SELECT name as profil, language_name as langage,
            active_rule_count as regle, rules_update_at as date,
            is_default as actif FROM profiles";
    $select = $this->em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
    $liste = $select->fetchAllAssociative();

    /** On met à jour la table propietes */
    $dateModificationProfil = $date->format("Y-m-d H:m:s");

    $sql="UPDATE properties
    SET profil_bd = ${nombre},
        profil_sonar = ${nombre},
        date_modification_profil = '${dateModificationProfil}'
    WHERE type = 'properties'";
    $this->em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();

    $response = new JsonResponse();
    return $response->setData(["listeProfil" => $liste, Response::HTTP_OK]);
  }

  /**
   * Tags
   *
   * http://{url}/api/components/search_projects
   *
   * @return response
   */
  #[Route('/api/tags', name: 'tags', methods: ['GET'])]
  public function tags(): response
  {
    /** oN créé un objet réponse */
    $response = new JsonResponse();

    $url = $this->getParameter(static::$sonarUrl)."/api/components/search_projects?ps=500";

    /** On appel le client http */
    $result = $this->httpClient($url);

    /** On, initialiser les variables  */
    $public=$private=$emptyTags=0;

    /** On créé un objet DateTime */
    $date = new DateTime();
    $timezone = new DateTimeZone('Europe/Paris');
    $date->setTimezone($timezone);

    /** On vérifie que sonarqube a au moins 1 projet */
    if (!$result){
      $message="[001] Je n'ai pas trouvé de projet sur le serveur sonarqube.";
      return $response->setData(["message" => $message, Response::HTTP_OK]);
    }

    /** On récupère le dernier enregistrement */
    $s0 = "SELECT *
            FROM tags
            ORDER BY date_enregistrement
            DESC LIMIT 1";
    $select=$this->em->getConnection()->prepare($s0)->executeQuery();
    $liste = $select->fetchAllAssociative();

    /** Si la table est vide on insert les résultats et
     * on revoie les résultats.
     */
    if (empty($liste)) {
      foreach ($result["components"] as $projet) {
        $tags = new Tags();
        $tags->setMavenKey($projet["key"]);
        $tags->setName($projet["name"]);
        $tags->setTags($projet["tags"]);
        $tags->setVisibility($projet["visibility"]);
        $tags->setDateEnregistrement($date);
        $this->em->persist($tags);
        $this->em->flush();

        /** On calcul le nombre de projet public et privé */
        if ($projet["visibility"]=='public') {
          $public++;
          } else {
          $private++;
        }

        /** On calcul le nombre de projet sans tags */
        if (empty($projet["tags"])) {
          $emptyTags++;
        }
      }
      /** on renvoie les résultats */
      $message="Création";
      return $response->setData(
        [
          "message" => $message,
          "visibility"=> ['public'=>$public, 'pivate'=>$private],
          "empty_tags"=>$emptyTags,
          Response::HTTP_OK
        ]);
    }

    /**
     * La liste des projet existe,
     * On regarde si on doit mettre à jour ou afficher la liste des projets
     */
    if (empty($liste)===false) {
      /** On récupère la date du jour */
      $dateDuJour=$date->format(static::$dateFormatShort);
      $dateEnregistrement= new DateTime($liste[0]['date_enregistrement']);
      /** Si la date d'enregistrement est > 1 jour à alors on met à jour */
      if ($dateDuJour > $dateEnregistrement->format(static::$dateFormatShort)) {
        /** On insert les données dans la tables des projet et tags. */
        foreach ($result["components"] as $projet) {
          $tags = new Tags();
          $tags->setMavenKey($projet["key"]);
          $tags->setName($projet["name"]);
          $tags->setTags($projet["tags"]);
          $tags->setVisibility($projet["visibility"]);
          $tags->setDateEnregistrement($date);
          $this->em->persist($tags);
          $this->em->flush();

          /** On calcul le nombre de projet public et privé */
          if ($projet["visibility"]=='public') {
            $public++;
            } else {
            $private++;
          }

          /** On calcul le nombre de projet sans tags */
          if (empty($projet["tags"])) {
            $emptyTags++;
          }
        }
        $message="[002] On met à jour la liste des projets.";
      } else {
        /** On a déjà mis à jour la table, on récupère les données nécessaires. */
        $tempo=$dateEnregistrement->format(static::$dateFormat);
        $s1="SELECT count(*) as visibility
                FROM tags
                WHERE date_enregistrement='${tempo}'
                AND visibility='public'";
        $r1 = $this->em->getConnection()->prepare($s1)->executeQuery();
        $t = $r1->fetchAllAssociative();
        $public=$t[0]['visibility'];

        $s2="SELECT count(*) as visibility
                FROM tags
                WHERE date_enregistrement='${tempo}'
                AND visibility='private'";
        $r2 = $this->em->getConnection()->prepare($s2)->executeQuery();
        $t = $r2->fetchAllAssociative();
        $private=$t[0]['visibility'];

        $s3="SELECT count(*) as tags
                FROM tags
                WHERE date_enregistrement='${tempo}'
                AND tags='[]'";
        $r3 = $this->em->getConnection()->prepare($s3)->executeQuery();
        $t = $r3->fetchAllAssociative();
        $emptyTags=$t[0]['tags'];

        $message="[003] On collecte les données conernant les projets.";
      }
    }
    //select json_extract(tags, '$.lot.name') as father_name from tags

    return $response->setData(
      [
        "message" => $message,
        "visibility"=> ['public'=>$public, 'private'=>$private],
        "empty_tags"=>$emptyTags,
        Response::HTTP_OK
    ]);
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
  public function visibility(): response
  {
    $url = $this->getParameter(static::$sonarUrl) .
          "/api/projects/search?qualifiers=TRK&ps=500";

    /** On appel le client http */
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
