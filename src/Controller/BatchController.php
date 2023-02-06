<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2022.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/** Logger */
use Psr\Log\LoggerInterface;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Accès aux tables SLQLite*/
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Main\BatchTraitement;
use App\Entity\Main\Historique;

/** Class API Batch */
use App\Controller\BatchApiController;

/**
 * [Description BatchController]
 */
class BatchController extends AbstractController
{
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatMini = "Y-m-d";
    public static $europeParis = "Europe/Paris";
    public static $regex = "/\s+/u";
    private static $batch001="[BATCH-001] Le traitement a déjà été mis à jour.";
    private static $batch002="[BATCH-002] Aucun batch trouvé.";
    private static $batch003="[BATCH-003] Le traitement a été mis à jour.";
    private static $batch004="[BATCH-004] Le portefeuille de projets est vide !";
    private static $batch005="[BATCH-005] Récupération de la liste de projets.";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * @param  private
     * @param  private
     * @param  private
     * @param  private
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct (
      private LoggerInterface $logger,
      private EntityManagerInterface $em,
      private Connection $connection,
      private BatchApiController $api,
      )
      {
          $this->logger = $logger;
          $this->em = $em;
          $this->connection = $connection;
          $this->api = $api;
      }

    /**
     * [Description for initialisationBatch]
     * Permet de créer la liste des job a éxécuter
     *
     * @return array
     *
     * Created at: 04/12/2022, 08:48:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function inititalisationBatch(): array
    {
      /** On démarre la session de traitement de collecte des informations sonarqube :
        * 1) On récupère la liste de batch dont le statut est true (1) ;
        * 2) Pour chaque job, on programme dans la table batch_historique le traitement.
        */

      /** On crée un objet date pour marquer le job */
      $date = new DateTime();
      $date->setTimezone(new DateTimeZone(static::$europeParis));

      /** Si on a déjà lancé un traitement aujourd'hui on sort */
      $sql="SELECT date_enregistrement as date FROM batch_traitement ORDER BY date_enregistrement DESC limit 1;";
      $r = $this->connection->fetchAllAssociative($sql);
      if (!empty($r)){
        $dateDuJour=$date->format(static::$dateFormatMini);
        $dateBatch=new DateTime($r[0]['date']);
        $dateBatch->format(static::$dateFormatMini);
        if ($dateDuJour==$dateBatch) {
          $this->logger->INFO("[BATCH-001] Le traitement a déjà été mis à jour.");
          return ["message"=>static::$batch001, "date"=>$r[0]['date']];
        }
      }

      /** 1 */
      $sql="SELECT statut, titre, responsable, portefeuille, nombre_projet as nombre FROM batch ORDER BY statut ASC ;";
      $r = $this->connection->fetchAllAssociative($sql);
      if (empty($r)){
        $this->logger->INFO("[BATCH-002] Aucun batch trouvé.");
        return ["message"=>static::$batch002];
      }

      /** 2 */
      foreach ($r as $traitement) {
        /** on crée un objet BatchTraitement */
        $batch = new BatchTraitement();
        if ($traitement["statut"]=="0") {
          $demarrage="Manuel";
          } else {
            $demarrage="Auto";
          }

        $batch->setDemarrage($demarrage);
        $batch->setResultat(0);
        $batch->setTitre($traitement["titre"]);
        $batch->setPortefeuille($traitement["portefeuille"]);
        $batch->setNombreProjet($traitement["nombre"]);
        $batch->setResponsable($traitement["responsable"]);
        //$batch->setdebutTraitement(0);
        //$batch->setfinTraitement(0);
        $batch->setDateEnregistrement($date);
        /** On en enregistre */
        $this->em->persist($batch);
        $this->em->flush();
      }
      $this->logger->INFO("[BATCH-003] Le traitement a été mis à jour.");
      return ["message"=>static::$batch003, "date"=>$date];
    }

    /**
     * [Description for listeProjet]
     *
     * @param mixed $job
     *
     * @return array
     *
     * Created at: 09/12/2022, 12:05:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listeProjet($job): array
    {
      /** Si on a déjà lancé un traitement aujourd'hui on sort */
      $sql="SELECT liste FROM portefeuille WHERE titre='${job}';";
      $r = $this->connection->fetchAllAssociative($sql);
      if (empty($r)){
          $this->logger->INFO("[BATCH-004] Le portefeuille est vide !");
          return ["message"=>static::$batch004];
      }

      $liste=[];
      foreach (json_decode($r[0]['liste']) as $value){
        array_push($liste, $value);
      }
      $this->logger->INFO("[BATCH-005] Récupération de la liste de projets.");
      return ["message"=>static::$batch005, "liste"=>$liste];
    }

    /**
     * [Description for traitement]
     *
     * @return Response
     *
     * Created at: 04/12/2022, 17:42:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement', name: 'traitement')]
    public function traitement(): Response
    {
      /** On créé on objet de reponse HTTP */
      $response = new JsonResponse();

      /** On met à jour la liste des job */
      $initialise = $this->inititalisationBatch();
      $this->logger->INFO($initialise["message"]);
      $message=explode(" ", $initialise["message"]);
      if ($message[0]==="[BATCH-002]"){
        $this->logger->INFO("[BATCH-006] Pas de collecte aujourd'hui !");
        return $response->setData(
          [ "message" => "[BATCH-006]",
            "description" => "Pas de collecte aujourd'hui !", Response::HTTP_OK]);
      }

      /** on récupère la date de mise à jour des jobs */
      $dateBatch=$initialise["date"]->format(static::$dateFormat);

      /** On récupère la liste des taitements planifiés pour la date du jour */
      $sql="SELECT id, demarrage, titre, portefeuille, nombre_projet as projet
            FROM batch_traitement
            WHERE demarrage = 'Auto' AND date_enregistrement='${dateBatch}'
            ORDER BY nombre_projet ASC;";
      $trim=trim(preg_replace(static::$regex, " ", $sql));
      $r = $this->connection->fetchAllAssociative($trim);

      /** On log si il n'y a pas de job à lancer */
      if (empty($r)) {
        $this->logger->INFO("[BATCH-007] Pas de jobs programmé aujoud'hui !");
        return $response->setData(
          [ "message" => "[BATCH-007]",
            "description" => "Pas de jobs programmé aujoud'hui !", Response::HTTP_OK]);
      }

      /**
       * On a trouvé un job :
       * { "93" "Auto" "0" "ANALYSE MA-MOULINETTE"
       *   "APPLICATIONS DE GESTION SONAR" "1"
       *   "admin" "@ma-moulinette" "2023-01-12 10:04:05" }
       *
       * On traite la liste jobs en Auto */
      foreach ($r as $value) {
        /** On récupère l'id du job */
        $id=$value['id'];
        /**
         * On récupère la liste des jobs
         * liste" => array:1 [ 0 => "fr.ma-petite-entreprise:ma-moulinette" ]
         */
        $listeProjet=$this->listeProjet($value['portefeuille']);

        /** On continue le traitement si la liste n'est pas vide */
        $message=explode(" ", $listeProjet["message"]);
        if ($message[0]==="[BATCH-005]"){

          /** On démarre la mesure du batch */
          $debutBatch = new DateTime();
          $debutBatch->setTimezone(new DateTimeZone(static::$europeParis));
          $tempoDebutBatch = $debutBatch->format(static::$dateFormat);

          /** Pour chaque projet de la liste */
          foreach($listeProjet['liste'] as $mavenKey) {
            /** On regarde si le projet est présent dans l'historique ? **/
            $sql="SELECT maven_key FROM historique WHERE maven_key='$mavenKey'";
            $r = $this->connection->fetchAllAssociative($sql);

            /* Le projet n'existe pas, je lance la collecte */
            if (empty($r)){
                $this->logger->INFO("[BATCH-008] Le projet n'existe pas ; je lance la collecte !");
                $this->api->batchNouvelleCollecte($mavenKey);
              }

              /**
               * On récupère la dernière version du serveur sonarqube
               * Si la version est plus récente sur le serveur Sonarqube
               * Alors on lance la collecte sinon on ne fait rien.
               *
               * On récupère :
               *  - La version "1.6.0-RELEASE"
               *  - La date de l'analyse: "2022-11-30 00:00:00"
               */
              $batchInformation=$this->api->batchInformation($mavenKey);
              $laVersionSonar=$batchInformation["information"]["projet"];
              $laDateSonar=$batchInformation["information"]["date"];


              /**
               * On récupère la dernière version en base
               *
               * On récupère :
               *  - La version "1.0.0-RELEASE"
               *  - La date de l'analyse: "2022-04-10 00:00:00"
               */
              $sql="SELECT version, date_version as date FROM historique
                    WHERE maven_key='$mavenKey'
                    ORDER BY version DESC, date DESC limit 1;";
              $trim=trim(preg_replace(static::$regex, " ", $sql));
              $r = $this->connection->fetchAllAssociative($trim);
              $laVersionMaMoulinette=$r[0]["version"];
              $laDateMaMoulinette=$r[0]["date"];

              if ($laVersionSonar===$laVersionMaMoulinette && $laDateSonar===$laDateMaMoulinette) {
                $this->logger->NOTICE("[BATCH-008] Le projet existe, il est à jour.");
              }
              else {
                $this->logger->INFO("[BATCH-008] Le projet existe, il n'est pas à jour !");
                $this->api->batchAjouteCollecte($mavenKey);
              }
            }
            /** Fin du Batch */
            $finBatch = new DateTime();
            $finBatch->setTimezone(new DateTimeZone(static::$europeParis));
            $tempoFinBatch = $finBatch->format(static::$dateFormat);

            /**
             * On met à jour la table des traitements
             * { "98" "Auto" "1" "ANALYSE MA-MOULINETTE" "APPLICATIONS DE GESTION SONAR"
             *   "1" "admin" "@ma-moulinette"
             *   "2023-01-12 10:37:13" "2023-01-12 10:37:13" "2023-01-12 10:37:13" }
             */
            $sql="UPDATE batch_traitement
                  SET debut_traitement='${tempoDebutBatch}',
                      fin_traitement='${tempoFinBatch}',
                      resultat = 1
                  WHERE id=${id};";
            $trim=trim(preg_replace(static::$regex, " ", $sql));
            $this->em->getConnection()->prepare($trim)->executeQuery();
          }

      }

      /** Fin du traitement */
      $interval = $debutBatch->diff($finBatch);
      $temps = $interval->format("%H:%I:%S");
      return $response->setData(["message" => "Tout va bien (${temps})",  Response::HTTP_OK]);
    }

    /**
     * [Description for traitementSuivi]
     *
     * @return Response
     *
     * Created at: 04/12/2022, 08:54:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/suivi', name: 'traitement_suivi')]
    public function traitementSuivi(): Response
    {
      /** On crée un objet date */
      $date = new DateTime();
      $date->setTimezone(new DateTimeZone(static::$europeParis));
      /**
       * On récupère la date du dernier traitement
       * date" => "2023-01-12 10:37:13"
       */
      $sql="SELECT date_enregistrement as date
            FROM batch_traitement
            ORDER BY date_enregistrement DESC limit 1;";
      $trim=trim(preg_replace(static::$regex, " ", $sql));
      $r = $this->connection->fetchAllAssociative($trim);

      /** Si on a pas trouvé de traitement  */
      if (empty($r)){
        $message="[BATCH-004] Aucun traitement trouvé.";
        $this->addFlash('info', $message);
        $traitements=[['message'=>"vide"]];
        return $this->render('batch/index.html.twig',
        [   'date'=>"01/01/1980",
            'traitements'=>$traitements,
            'version' => $this->getParameter("version"), 'dateCopyright' => \date('Y')
        ]);
      }

      /**
       * On récupère la liste des taitements planifié pour la date du jour.
       * "demarrage" => "Auto"
       * "resultat" => 1
       * "titre" => "ANALYSE MA-MOULINETTE"
       * "portefeuille" => "APPLICATIONS DE GESTION SONAR"
       * "projet" => 1
       *  "responsable" => "admin @ma-moulinette"
       *  "debut" => "2023-01-12 10:37:13"
       *  "fin" => "2023-01-12 10:37:13"}
       */
      $dateDernierBatch=$r[0]['date'];
      $sql="SELECT demarrage, resultat, titre, portefeuille,
            nombre_projet as projet,
            responsable,
            debut_traitement as debut,
            fin_traitement as fin
            FROM batch_traitement
            GROUP BY titre
            ORDER BY responsable ASC;";
      $trim=trim(preg_replace(static::$regex, " ", $sql));
      $r = $this->connection->fetchAllAssociative($trim);
      /** On génére les données pour le tableau de suivi */
      $traitements=[];
      foreach ($r as $traitement) {
        /** Calcul de l'execution du traitement */
        if (empty($traitement["debut"])) {
          $resultat=3;
        } else {
          $resultat=$traitement["resultat"];
          $debut=new dateTime($traitement["debut"]);
          $fin=new dateTime($traitement["fin"]);
          $interval = $debut->diff($fin);
          $execution = $interval->format("%H:%I:%S");
        }

        $tempo=["message"=>"Tout va bien !",
                "demarrage"=>$traitement["demarrage"],
                "resultat"=>$resultat,
                "job"=>$traitement["titre"],
                "portefeuille"=>$traitement["portefeuille"],
                "projet"=>$traitement["projet"],
                "responsable"=>$traitement["responsable"],
                "execution"=>$execution];
        array_push($traitements, $tempo);
      }
      return $this->render('batch/index.html.twig',
        [   'date'=>$dateDernierBatch,
            'traitements'=>$traitements,
            'version' => $this->getParameter("version"), 'dateCopyright' => \date('Y')
        ]);
    }


}
