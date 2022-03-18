<?php
/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\HttpFoundation\JsonResponse;

// Accès aux tables SLQLite
use App\Entity\ListeProjet;
use App\Entity\Profiles;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Connection;
use DateTime;

class ApiHomeController extends AbstractController
{

  private $client;

  public function __construct(HttpClientInterface $client) { $this->client = $client; }

  public static $strContentType = 'application/json';
  public static $dateFormat = "Y-m-d H:m:s";
  public static $sonarUrl= "sonar.url";

  /**
   * description
   * http_client
  */
  protected function http_client($url) {
    $response = $this->client->request(
      'GET', $url, [ 'auth_basic' => [$this->getParameter('sonar.user'), $this->getParameter('sonar.password')], 'timeout' => 45,'headers' => [ 'Accept' => static::$strContentType, 'Content-Type' => static::$strContentType]
    ]);

    if (200 !== $response->getStatusCode()) {
        if ($response->getStatusCode() == 401) {
          throw new \Exception('Erreur d\'Authentification. La clé n\'est pas correcte.');
        } else {
      throw new \Exception('Retour de la réponse différent de ce qui est prévu. Erreur '.
          $response->getStatusCode());
        }
      }

    $contentType = $response->getHeaders()['content-type'][0];
    $responseJson = $response->getContent();
    return json_decode($responseJson, true, 512, JSON_THROW_ON_ERROR);
    }

    /*
      * description
      * Vérifie si le serveur sonarqube est UP
      * http://{url}}/api/system/status
    */
    #[Route('/api/status', name: 'sonar_status', methods: ['GET'])]
    public function sonar_status(){
      $url=$this->getParameter(static::$sonarUrl)."/api/system/status";

      // on appel le client http
      $result=$this->http_client($url);

      return new JsonResponse($result, Response::HTTP_OK );
    }

  /**
   * description
   * Récupération de la liste des projets.
   * http://{url}}/api/components/search?qualifiers=TRK&ps=500
   */
    #[Route('/api/liste_projet/ajout', name: 'liste_projet_ajout', methods: ['GET'])]
    public function liste_projet(EntityManagerInterface $em): response{
      $url=$this->getParameter(static::$sonarUrl)."/api/components/search?qualifiers=TRK&ps=500&p=1";
      //'auth_basic' => [$this->getParameter('sonar.token'),'']

      // on appel le client http
      $result=$this->http_client($url);

      // On récupère le manager de BD
      $date= new DateTime();
      $nombreProjet=0;

      // On supprime les données de la table avant d'importer les données;
      $sql = "DELETE FROM liste_projet";
      $delete = $em->getConnection()->prepare($sql);
      $delete->executeQuery();

      // On insert les projets dans la tale liste_projet.
      foreach ($result["components"] as $component) {
       // On exclue les projets archivés (-SVN)
       //"project": "fr.franceagrimer:autorisation-plantation-metier-SVN"
       $mystring = $component["project"];
       $findme   = '-SVN';
       if (!strpos($mystring, $findme)){
          $nombreProjet=$nombreProjet+1;
          $listeProjet = new ListeProjet();
          $listeProjet->setName($component["name"]);
          $listeProjet->setMavenKey($component["project"]);
          $listeProjet->setDateEnregistrement($date);
          $em->persist($listeProjet);
          $em->flush();
        }
      }
      $response = new JsonResponse();
      $response->setData(["nombreProjet"=>$nombreProjet, Response::HTTP_OK]);
      return $response;
    }

    /*
      * description
      * récupère la date de mise à jour du référentiel
      * http://{url}}/api/liste_projet/date
    */
    #[Route('/api/liste_projet/date', name: 'liste_projet_date', methods: ['GET'])]
    public function liste_projet_date(Connection $connection) {

      $sql = "SELECT date_enregistrement as date from 'liste_projet' ASC LIMIT 1";
      $rq1 = $connection->fetchAllAssociative($sql);

     if (!$rq1){
        $dateCreation=0;
        $nombreProjet=0;
      }
     else {
        $sql = "SELECT COUNT(*) as total from 'liste_projet'";
        $rq2 = $connection->fetchAllAssociative($sql);
        $dateCreation=$rq1[0]['date'];
        $nombreProjet=$rq2[0]['total'];
      }

      $response = new JsonResponse();
      $response->setData(["dateCreation"=>$dateCreation, "nombreProjet"=>$nombreProjet, Response::HTTP_OK]);
      return $response;
    }

   /**
   * description
   * Renvoie la liste des profils qualité
  * http://{url}/api/qualityprofiles/search?qualityProfile={name}
  */
  #[Route('/api/quality/profiles', name: 'liste_quality_profiles', methods: ['GET'])]
  public function liste_quality_profiles(EntityManagerInterface $em): response {
      $url=$this->getParameter(static::$sonarUrl)."/api/qualityprofiles/search?qualityProfile=".$this->getParameter('sonar.profiles');

      // on appel le client http
      $result=$this->http_client($url);

      // On récupère le manager de BD
      $date= new DateTime();
      $nombreProfil=0;

      // On supprime les données de la table avant d'importer les données;
      $sql = "DELETE FROM profiles";
      $delete = $em->getConnection()->prepare($sql);
      $delete->executeQuery();

      // On insert les profiles dans la table profiles.
      foreach ($result["profiles"] as $profil) {
        $nombreProfil=$nombreProfil+1;

        $profils = new Profiles();
        $profils->setKey($profil["key"]);
        $profils->setName($profil["name"]);
        $profils->setLanguageName($profil["languageName"]);
        $profils->setIsDefault($profil["isDefault"]);
        $profils->setActiveRuleCount($profil["activeRuleCount"]);
        $rulesDate=new DateTime($profil["rulesUpdatedAt"]);
        $profils->setRulesUpdateAt($rulesDate);
        $profils->setDateEnregistrement($date);
        $em->persist($profils);
        $em->flush();
        }

      // On récupère la liste des profiles;
      $sql = "SELECT name as profil, language_name as langage, active_rule_count as regle, rules_update_at as date, is_default as actif FROM profiles";
      $select = $em->getConnection()->prepare($sql)->executeQuery();
      $liste=$select->fetchAllAssociative();

      $response = new JsonResponse();
      return $response->setData(["listeProfil"=>$liste, Response::HTTP_OK]);
    }

   /**
   * description
   * Renvoire le nombre de profil
   */
  #[Route('/api/quality', name: 'nombre_profil', methods: ['GET'])]
  public function nombre_profil(EntityManagerInterface $em): response {
    // On récupère le nombre de profil dans la table profiles;
    $sql = "SELECT count(*) as nombre FROM profiles";
    $select = $em->getConnection()->prepare($sql)->executeQuery();
    $result=$select->fetchAllAssociative();
    if (empty($result)){$nombre=0;} else {$nombre=$result[0]["nombre"];}

    $response = new JsonResponse();
    return $response->setData(["nombre"=>$nombre, Response::HTTP_OK]);
  }
}
