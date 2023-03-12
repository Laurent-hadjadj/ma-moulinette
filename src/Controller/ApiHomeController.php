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

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Accès aux tables SLQLite */
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Main\Tags;
use App\Entity\Main\Profiles;
use App\Entity\Main\ListeProjet;

/** Gestion de accès aux API */
//use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

class ApiHomeController extends AbstractController
{
  /** Définition des constantes */
  public static $sonarUrl = "sonar.url";
  public static $dateFormatShort = "Y-m-d";
  public static $dateFormat = "Y-m-d H:i:s";
  public static $europeParis = "Europe/Paris";
  public static $regex = "/\s+/u";

  /**
   * [Description for __construct]
   *
   * @param  private
   * @param  private
   * @param mixed
   *
   * Created at: 15/12/2022, 21:12:55 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct(
    private LoggerInterface $logger,
    private EntityManagerInterface $em,
    )
  {
    $this->logger = $logger;
    $this->em = $em;
  }

  /**
   * [Description for sonarStatus]
   * Vérifie si le serveur sonarqube est UP
   * http://{url}}/api/system/status
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:13:23 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/status', name: 'sonar_status', methods: ['GET'])]
  public function sonarStatus(Client $client):response
  {
    $url = $this->getParameter(static::$sonarUrl) . "/api/system/status";

    /** On appel le client http */
    $result = $client->http($url);

    return new JsonResponse($result, Response::HTTP_OK);
  }

  /**
   * [Description for sonarHealth]
   * Vérifie l'état du serveur
   * http://{url}}/api/system/health
   * Encore une fois, c'est null, il faut être admin pour récupérrer le résultat.
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:14:20 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
   * [Description for informationSysteme]
   * On récupère les informations système du serveur
   * http://{url}}/api/system/info
   *
   * Attention, il faut avoir le role sonar administrateur
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:14:39 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/system/info', name: 'information_systeme', methods: ['GET'])]
  public function informationSysteme(Client $client):response
  {
    $url = $this->getParameter(static::$sonarUrl) . "/api/system/info";

    /** On appel le client http */
    $result = $client->http($url);
    return new JsonResponse($result, Response::HTTP_OK);
  }

  /**
   * [Description for projetListe]
   * Récupération de la liste des projets.
   * http://{url}}/api/components/search?qualifiers=TRK&ps=500
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:15:04 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/projet/liste', name: 'projet_liste', methods: ['GET'])]
  public function projetListe(Request $request,Client $client): response
  {
    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    /** On vérifie si on a activé le mode test */
    if (is_null($request->get('mode'))) {
      $mode="null";
    } else {
      $mode = $request->get('mode');
    }

    $url = $this->getParameter(static::$sonarUrl) . "/api/components/search?qualifiers=TRK&ps=500&p=1";

    /** On appel le client http */
    $result = $client->http($url);
    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));
    $nombre = 0;

    /** On supprime les données de la table avant d'importer les données. */
    $sql = "DELETE FROM liste_projet";
    $delete = $this->em->getConnection()->prepare($sql);
    if ($mode!='TEST') {
      $delete->executeQuery();
    }

    /**  On compte le nombre de projet et on insert les projets dans la table liste_projet. */
    foreach ($result["components"] as $component) {
      /**
       *  On exclue les projets archivés avec la particule "-SVN".
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
        if ($mode!='TEST') {
          $this->em->flush();
        }
      }
    }

    /** On met à jour la table propietes */
    $dateModificationProjet = $date->format(static::$dateFormatShort);

    $sql="UPDATE properties
    SET projet_bd = ${nombre},
        projet_sonar = ${nombre},
        date_modification_projet = '${dateModificationProjet}'
    WHERE type = 'properties'";
    if ($mode!='TEST') {
      $this->em->getConnection()
                ->prepare(trim(preg_replace(static::$regex, " ", $sql)))
                ->executeQuery();
    }

    return $response->setData(["nombre" => $nombre, 'mode'=>$mode, Response::HTTP_OK]);
  }


  /**
   * [Description for listeQualityProfiles]
   * liste_quality_profiles
   * Renvoie la liste des profils qualités
   * http://{url}/api/qualityprofiles/search?qualityProfile={name}
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:15:43 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/quality/profiles', name: 'liste_quality_profiles', methods: ['GET'])]
  public function listeQualityProfiles(Request $request, Client $client): response
  {
    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    /** On vérifie si on a activé le mode test */
    $mode = $request->get('mode');

    $url1 = $this->getParameter(static::$sonarUrl)
          . "/api/qualityprofiles/search?qualityProfile="
          . $this->getParameter('sonar.profiles');

    /** On appel le client http */
    $result = $client->http($url);

    $date = new DateTime();
    $date->setTimezone(new DateTimeZone(static::$europeParis));
    $nombre = 0;

    /** On supprime les données de la table avant d'importer les données;*/
    $sql = "DELETE FROM profiles";
    $delete = $this->em->getConnection()->prepare($sql);
    if ($mode!="TEST") {
      $delete->executeQuery();
    }

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
      if ($mode!="TEST") {
        $this->em->flush();
      }
    }

    /** On récupère la liste des profils; */
    $sql = "SELECT name as profil, language_name as langage,
            active_rule_count as regle, rules_update_at as date,
            is_default as actif FROM profiles";
    $select = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();
    $liste = $select->fetchAllAssociative();

    /** On met à jour la table proprietes */
    $dateModificationProfil = $date->format(static::$dateFormatShort);

    $sql="UPDATE properties
    SET profil_bd = ${nombre},
        profil_sonar = ${nombre},
        date_modification_profil = '${dateModificationProfil}'
    WHERE type = 'properties'";

    if ($mode!="TEST") {
        $this->em->getConnection()
              ->prepare(trim(preg_replace(static::$regex, " ", $sql)))
              ->executeQuery();
    }
    return $response->setData(["mode"=>$mode, "listeProfil" => $liste, Response::HTTP_OK]);
  }

  /**
   * [Description for tags]
   * http://{url}/api/components/search_projects
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:16:25 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/tags', name: 'tags', methods: ['GET'])]
  public function tags(Request $request, Client $client): response
  {
    /** oN créé un objet réponse */
    $response = new JsonResponse();

    /** On on vérifie si on a activé le mode test */
    if (is_null($request->get('mode'))) {
      $mode="null";
    } else {
      $mode = $request->get('mode');
    }

    $url = $this->getParameter(static::$sonarUrl)."/api/components/search_projects?ps=500";

    /** On appel le client http */
    $result = $client->http($url);

    /** On, initialiser les variables  */
    $public=$private=$emptyTags=0;

    /** On créé un objet DateTime */
    $date = new DateTime();
    $timezone = new DateTimeZone(static::$europeParis);
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

    /**
     * Si la table est vide on insert les résultats et
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
        if ($mode!='TEST') {
          $this->em->flush();
        }

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
      $message="Initialisation de la liste des Tags...";
      return $response->setData(
        [
          "mode"=>$mode,
          "message" => $message,
          "public"=> $public,
          "private"=>$private,
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
      [ "mode"=>$mode,
        "message" => $message,
        "public"=>$public, "private"=>$private,
        "empty_tags"=>$emptyTags,
        Response::HTTP_OK
    ]);
  }

  /**
   * [Description for visibility]
   * Renvoi le nombre de projet private ou public
   * Il faut avoir un droit Administrateur !!!
   * http://{url}/api/projects/search?qualifiers=TRK&ps=500
   *
   * @return response
   *
   * Created at: 15/12/2022, 21:17:03 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/visibility', name: 'visibility', methods: ['GET'])]
  public function visibility(Client $client): response
  {
    $url = $this->getParameter(static::$sonarUrl) .
          "/api/projects/search?qualifiers=TRK&ps=500";

    /** On appel le client http */
    $components = $client->http($url);

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
