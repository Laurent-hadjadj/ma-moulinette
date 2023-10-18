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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** Logger */
use Psr\Log\LoggerInterface;

/** gestion du journal d'activité */
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Accès aux tables SLQLite*/
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Main\BatchTraitement;
//use App\Entity\Main\Historique;

/** Rotation des logs */
use Cesargb\Log\Rotation;
use Cesargb\Log\Exceptions\RotationFailed;

/** Class API Batch */
use App\Controller\BatchApiController;

/**
 * [Description BatchController]
 */
class BatchController extends AbstractController
{
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatMini = "Y-m-d";
    public static $timeFormat = "%H:%I:%S";
    public static $europeParis = "Europe/Paris";
    public static $regex = "/\s+/u";

    private static $batch001="[BATCH-001] Le traitement a déjà été mis à jour.";
    private static $batch002="[BATCH-002] Aucun batch trouvé.";
    private static $batch003="[BATCH-003] La liste des traitements a été mis à jour.";
    private static $batch004="[BATCH-004] Le portefeuille de projets est vide !";
    private static $batch005="[BATCH-005] Récupération de la liste de projets.";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
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
       * [Description for logrotate]
       *
       * @return int
       *
       * Created at: 05/03/2023, 18:01:55 (Europe/Paris)
       * @author    Laurent HADJADJ <laurent_h@me.com>
       * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
       */
      public function logrotate(): void
      {
        /* On initialise le journal des traces */
        $filesystem = new Filesystem();
        $path= $this->getParameter('kernel.project_dir').$this->getParameter('path.audit');
        /* Le dossier d'audit est présent */
        if ($filesystem->exists($path)){
          /** Rotation des logs */
          $rotation = new Rotation([
            'files' => 5,
            'compress' => true,
            'min-size' => 102400,
            'truncate' => true,
            'then' => function ($filenameTarget, $filenameRotated) {},
            'catch' => function (RotationFailed $exception) {},
            'finally' => function ($message, $filenameTarget) {},
          ]);
        }
        /** on récupère les logs */
        $finder = new Finder();
        $finder->files()->in($path)->depth(0)->sortByName();

        foreach ($finder as $file) {
            $rotation->rotate($file->getPathname());
          }
      }

      /**
       * [Description for information]
       *
       * @param mixed $job
       * @param mixed $log
       *
       * @return void
       *
       * Created at: 05/03/2023, 00:01:12 (Europe/Paris)
       * @author    Laurent HADJADJ <laurent_h@me.com>
       * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
       */
      public function information($job, $log): int
      {
        /* On initialise le journal des traces */
        $filesystem = new Filesystem();
        $path= $this->getParameter('kernel.project_dir').'\var\audit';
        /* Le dossier d'audit est présent */
        if ($filesystem->exists($path)){
          $name=preg_replace('/\s+/', '_', $job);
          $fichier="$path\manuel_{$name}.log";
          $filesystem->appendToFile($fichier, $log, true);
        } else {
          return 404;
        }
        return 200;
      }

      /**
       * [Description for lireInformation]
       *
       * @param mixed $job
       * @param mixed $type
       *
       * @return response
       *
       * Created at: 05/03/2023, 01:50:53 (Europe/Paris)
       * @author    Laurent HADJADJ <laurent_h@me.com>
       * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
       */
      #[Route('/traitement/information', name: 'traitement_information', methods: ['POST'])]
      public function lireInformation(Request $request): response
      {
        /** On créé on objet de reponse HTTP */
        $response = new JsonResponse();

        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        $job=$data->job;
        $type=$data->type;

        /* On initialise le journal des traces */
        $filesystem = new Filesystem();
        $path= $this->getParameter('kernel.project_dir').'\var\audit';

        $recherche="KO";
        /* Le dossier d'audit est présent */
        if ($filesystem->exists($path)){
          $name=preg_replace('/\s+/', '_', $job);
          $fichier="{$type}_$name.log";

          /** on récupère la log */
          $finder = new Finder();
          $finder->files()->in($path);
          $finder->name($fichier);

          foreach ($finder as $file) {
              $c = $file->getContents();
            }
          if (empty($c)){
            $c="Pas de journal disponible.";
          } else {
            $recherche="OK";
          }
        }

        return $response->setData(["recherche" => $recherche, "journal" => $c, Response::HTTP_OK]);
        }

    /**
     * [Description for initialisationBatch]
     * Permet de créer la liste des job a éxécuter
     *
     * @return array
     *
     * Created at: 04/12/2022, 08:48:52 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function initialisationBatch(): array
    {
      /** On démarre la session de traitement de collecte des informations sonarqube :
        * 1) On récupère la liste de batch dont le statut est true (1) ;
        * 2) Pour chaque job, on programme dans la table batch_historique le traitement.
        */

      /** On crée un objet date pour marquer le job */
      $date = new DateTime();
      $date->setTimezone(new DateTimeZone(static::$europeParis));

      /** 0 - Si on a déjà lancé un traitement Automatique aujourd'hui on sort */
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

      /** 1 - on regarde si on a des batchs */
      $sql="SELECT statut, titre, responsable, portefeuille, nombre_projet as nombre
      FROM batch ORDER BY statut ASC ;";
      $r = $this->connection->fetchAllAssociative($sql);
      if (empty($r)){
        $this->logger->INFO("[BATCH-002] Aucun batch trouvé.");
        return ["message"=>static::$batch002];
      }

      /** 2 - si on a des batch et que le traitement n'a pas été lancé on créé la liste */
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
        $batch->setDateEnregistrement($date);

        /** On en enregistre */
        $this->em->persist($batch);
        $this->em->flush();
      }
      $this->logger->INFO("[BATCH-003] La liste des traitements a été mis à jour.");
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
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listeProjet($job): array
    {
      /** Si on a déjà lancé un traitement aujourd'hui on sort */
      $jobEncode=preg_replace("/'/", "''", $job);
      $sql="SELECT liste FROM portefeuille WHERE titre='$jobEncode';";
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
     * [Description for pending]
     *  On retourne le nombre de traitement selon le type
     *  pending, start, error, end
     * @return int
     *
     * Created at: 07/02/2023, 10:42:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function pending($exec): int
    {
      $sql = "SELECT count(*) as exec FROM batch
              WHERE execution='$exec'";
      $r = $this->em->getConnection()
            ->prepare(trim(preg_replace(static::$regex, " ", $sql)))
            ->executeQuery()->fetchAllAssociative();
      return $r[0]['exec'];
    }

    /**
     * [Description for traitementPending]
     * On lock le job pour l'execution
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 07/02/2023, 14:06:01 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/pending', name: 'traitement_pending', methods: ['GET'])]
    public function traitementPending(Request $request): Response
    {
      /** On récupère le portefeuille */
      $job = $request->get('job');
      $mode = $request->get('mode');

      /** On créé un nouvel objet Json */
      $response = new JsonResponse();

      /** On vérifie que le job existe */
      $sql = "SELECT * FROM batch WHERE titre='$job' limit 1";
      $request = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery()->fetchAllAssociative();

      if (empty($request)) {
        return $response->setData(["mode"=>$mode, "job"=>$job, "code" => "KO", "execution" => "error", Response::HTTP_OK]);
      }

      /**
       * On vérifie qu'il n'y a pas de traitement en cours.
       *  excecution = start
       */
      $r=$this->pending("start");
      $message="pending";
      /** On met à jour le job en start pour bloquer les autres traitements */
      if ($r===0) {
        $sql = "UPDATE batch SET execution='start' WHERE titre='$job'";
        $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();
        $message="start";
      } else {
        /** Il y a déjà un job en cours */
        $message="pending";
      }

      return $response->setData(["mode"=>$mode, "code" => "OK", "execution" => $message, Response::HTTP_OK]);
    }

    /**
     * [Description for traitement]
     * On lance les traitements automatiques
     * @return Response
     *
     * Created at: 04/12/2022, 17:42:22 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function traitement(): Response
    {
      /** On créé on objet de reponse HTTP */
      $response = new JsonResponse();

      /** On met à jour la liste des job */
      $initialise = $this->initialisationBatch();
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

      /** On récupère la liste des traitements planifiés pour la date du jour */
      $sql="SELECT id, demarrage, titre, portefeuille, nombre_projet as projet
            FROM batch_traitement
            WHERE demarrage = 'Auto' AND date_enregistrement='$dateBatch'
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
        $message=explode(" ", $listeProjet['message']);
        if ($message[0]==="[BATCH-005]") {

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
                  SET debut_traitement='$tempoDebutBatch',
                      fin_traitement='$tempoFinBatch',
                      resultat = 1
                  WHERE id=$id;";
            $trim=trim(preg_replace(static::$regex, " ", $sql));
            $this->em->getConnection()->prepare($trim)->executeQuery();
          }

      }

      /** Fin du traitement */
      $interval = $debutBatch->diff($finBatch);
      $temps = $interval->format(static::$timeFormat);
      return $response->setData(["message" => "Tout va bien ($temps)",  Response::HTTP_OK]);
    }

    /**
     * [Description for traitementManuel]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 01/03/2023, 09:21:45 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/manuel', name: 'traitement_manuel', methods: ['POST'])]
    public function traitementManuel(Request $request): Response
    {
      /** On vérifie que l'utilisateur a bien un rôle */
      //$this->denyAccessUnlessGranted("ROLE_BATCH", null,
      //"L'utilisateur essaye d'accèder à la page sans avoir le rôle ROLE_BATCH");

      /** On créé on objet de reponse HTTP */
      $response = new JsonResponse();

      /** On récupère le body */
      $data = json_decode($request->getContent());

      /** On récupère le job */
      $job= $data->job;

      $date = new DateTime();
      $date->setTimezone(new DateTimeZone(static::$europeParis));
      $maxiDate=$date->format(static::$dateFormat);

      $log="=== Initialisation du traitement le ".$maxiDate." ===\n\n";
      $this->information($job, $log);

      /** On récupère les infos du traitement planifié pour la date du jour */
      $sql="SELECT id, demarrage, titre, portefeuille, nombre_projet as projet
      FROM batch_traitement
      WHERE titre = '$job';";
      $trim=trim(preg_replace(static::$regex, " ", $sql));
      $r = $this->connection->fetchAllAssociative($trim);

      /** On test la reponse */
      if (empty($r)){
        /** Pas de job dans la table batch_traitement */
        $log="ERREUR : La liste des traitements est vide !!!\n";
        $this->information($job, $log);
        return $response->setData(['job'=>$job, Response::HTTP_NOT_ACCEPTABLE]);
      }

      /** On traite le job  */
      foreach ($r as $value) {
        /** On récupère l'id du job */
        $id=$value['id'];
        /**
         * On récupère la liste des projets pour le job
         * liste" => array:1 [ 0 => "fr.ma-petite-entreprise:ma-moulinette" ]
         */
        $listeProjet=$this->listeProjet($value['portefeuille']);

        /** On continue le traitement si la liste des projets n'est pas vide */
        $message=explode(" ", $listeProjet["message"]);
        if ($message[0]==="[BATCH-005]"){
          /** On démarre la mesure du batch */
          $debutBatch = new DateTime();
          $debutBatch->setTimezone(new DateTimeZone(static::$europeParis));
          $tempoDebutBatch = $debutBatch->format(static::$dateFormat);

          $log="INFO : Début de la collecte pour \n"."       ".$value['portefeuille']."\n";
          $this->information($job, $log);
          $i=0;
          /** Pour chaque projet de la liste */
          foreach($listeProjet['liste'] as $mavenKey) {
            $i = $i+1;
            $log="INFO : Collecte des indicateurs pour *** $mavenKey *** \n\n";
            $this->information($job, $log);

            /** On regarde si le projet est présent dans l'historique ? **/
            $sql="SELECT maven_key FROM historique WHERE maven_key='$mavenKey'";
            $r = $this->connection->fetchAllAssociative($sql);

            /* Le projet n'existe pas, je lance la collecte */
            if (empty($r)){
                $this->logger->INFO("[BATCH-008] Le projet n'existe pas ; je lance la collecte !");
                $log="INFO : Le projet n'existe pas dans la table historique ; je lance la collecte !\n";
                $this->information($job, $log);
                $this->api->batchNouvelleCollecte($mavenKey);
              }

              /**
               * On récupère la dernière version du serveur sonarqube
               * Si la version est plus récente sur le serveur Sonarqube
               * Alors on lance la collecte sinon on ne fait rien.
               */
              $batchInformation=$this->api->batchInformation($mavenKey);
              $laVersionSonar=$batchInformation["information"]["projet"];
              $laDateSonar=$batchInformation["information"]["date"];

              /** On récupère la dernière version en base */
              $sql="SELECT version, date_version as date FROM historique
                    WHERE maven_key='$mavenKey'
                    ORDER BY version DESC, date DESC limit 1;";
              $trim=trim(preg_replace(static::$regex, " ", $sql));
              $r = $this->connection->fetchAllAssociative($trim);
              $laVersionMaMoulinette=$r[0]["version"];
              $laDateMaMoulinette=$r[0]["date"];

              if ($laVersionSonar===$laVersionMaMoulinette && $laDateSonar===$laDateMaMoulinette) {
              $log="INFO : Le projet existe dans la table historique ; il est à jour !\n";
              $this->information($job, $log);
              $this->logger->NOTICE("[BATCH-008] Le projet existe, il est à jour.");
              }
              else {
                $log="INFO : Le projet existe dans la table historique ; il n'est pas à jour !\n";
                $this->information($job, $log);
                $this->logger->INFO("[BATCH-008] Le projet existe, il n'est pas à jour !");
                $this->api->batchAjouteCollecte($mavenKey);
              }
            }

            /** Fin du Batch */
            $finBatch = new DateTime();
            $finBatch->setTimezone(new DateTimeZone(static::$europeParis));
            $tempoFinBatch = $finBatch->format(static::$dateFormat);

            $log1="INFO : Fin de la collecte pour le projet.\n";
            $log2="          Fin du traitement le ".$tempoFinBatch."\n";
            $this->information($job, $log1);
            $this->information($job, $log2);

            /** On met à jour la table des traitements */
            $sql="UPDATE batch_traitement
                  SET debut_traitement='$tempoDebutBatch',
                      fin_traitement='$tempoFinBatch',
                      resultat = 1
                  WHERE id=$id;";
            $trim=trim(preg_replace(static::$regex, " ", $sql));
            $this->em->getConnection()->prepare($trim)->executeQuery();
            $log="INFO : Mise à jour de la table batch traitement.\n";
            $this->information($job, $log);
          }
      }

      /** Fin du traitement */
      $interval = $debutBatch->diff($finBatch);
      $temps = $interval->format(static::$timeFormat);
      $sql = "UPDATE batch SET execution='end' WHERE titre='$job'";
      $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();

      $log1="INFO : Durée d'exécution total -> ".$temps."\n";
      $log2="INFO : Nombre de projet -> ".$i."\n";
      $log3="INFO : statut -> 'end'.\n\n";
      $this->information($job, $log1);
      $this->information($job, $log2);
      $this->information($job, $log3);
      $log="=== Fin des traitements ===\n\n";
      $this->information($job, $log);

      return $response->setData(["execution"=>"end", "temps" => $temps, Response::HTTP_OK]);
    }

    #[Route('/traitement/auto', name: 'traitement_auto', methods: ['POST'])]
    public function traitementAuto(Request $request){
      /** On créé on objet de reponse HTTP */
      $response = new JsonResponse();

      /** On récupère le token csrf généré par le serveur */
      $data = json_decode($request->getContent());
      $csrf=$data->token;
      /** on vérifie le token */
      if ($this->isCsrfTokenValid($this->getParameter('csrf.salt'), $csrf)) {
          $message=static::traitement();
      } else {
        $message="ouuuuups ça sent mauvais !";
      }
      return $response->setData(["message"=>$message, Response::HTTP_OK]);
    }

    /**
     * [Description for traitementSuivi]
     * On récupère la liste des traitements disponibles.
     * @return Response
     *
     * Created at: 04/12/2022, 08:54:16 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/suivi', name: 'traitement_suivi')]
    public function traitementSuivi(): Response
    {
      /** On autorise des les utilisateurs ayant le rôle BATCH */
      $this->denyAccessUnlessGranted("ROLE_BATCH", null,
      "L'utilisateur essaye d'accèder à la page sans avoir le rôle ROLE_BATCH");

      /** On  archive les logs */
      $this->logrotate();

      /** On initialise les information pour la bulle d'information */
      $bulle="bulle-info-vide";
      $infoNombre="x";
      $infoTips="Aucun traitement.";

      /** On crée un objet date */
      $date = new DateTime();
      $date->setTimezone(new DateTimeZone(static::$europeParis));
      /**
       * On récupère la date du dernier traitement
       * Pour le 08/02/2023 : date" => "2023-02-08 08:57:53"
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
        $traitements=[['processus'=>"vide"]];
        return $this->render('batch/index.html.twig',
        [
          'salt'=>$this->getParameter('csrf.salt'),
          'infoNombre'=>$infoNombre,
          'infoTips'=>$infoTips,
          'bulle'=>$bulle,
          'date'=>"01/01/1980",
          'traitements'=>$traitements,
          'version' => $this->getParameter("version"), 'dateCopyright' => \date('Y')
        ]);
      }

      /**
       * On récupère la liste des traitements planifié pour la date du jour.
       */
      /** retourne la date de planification */
      $dateDernierBatch=$r[0]['date'];
      /** retourne la date au format 2023-02-08 */
      $dateTab=explode(" ", $dateDernierBatch);
      $dateDernierBatchShort=$dateTab[0];
      $sql="SELECT demarrage, resultat, titre, portefeuille,
            nombre_projet as projet,
            responsable,
            debut_traitement as debut,
            fin_traitement as fin
            FROM batch_traitement
            WHERE date_enregistrement like '$dateDernierBatchShort%'
            GROUP BY titre
            ORDER BY responsable ASC, demarrage ASC";
      $trim=trim(preg_replace(static::$regex, " ", $sql));
      $r = $this->connection->fetchAllAssociative($trim);
      /** On génére les données pour le tableau de suivi */
      $traitements=[];
      foreach ($r as $traitement) {
        /** Calcul de l'execution pour un traitement qui a démaré. */
        if (!empty($traitement['debut'])) {
          $resultat=$traitement['resultat'];

          /** on définit le message et la class css */
          if ($resultat==0){
              $message="Erreur";
              $css="ko";
            } else {
              $message="Succès";
              $css="ok";
            }
          $debut=new dateTime($traitement['debut']);
          $fin=new dateTime($traitement['fin']);
          $interval = $debut->diff($fin);
          $execution = $interval->format(static::$timeFormat);
        }

        /** on définit le type */
        if ($traitement['demarrage']==="Auto") {
          $type = "automatique";
        } else {
          $type = "manuel";
        }

        /** On formate les données pour les batchs qui n'ont pas été lancé (i.e MANUEL) */
        if (empty($traitement['debut'])) {
          $message="---";
          $css="oko";
          $execution="--:--:--";
        }

        $tempo=["processus"=>"Tout va bien !",
                /** Auto ou Manuel */
                "demarrage"=>$traitement['demarrage'],
                /** Succès, Erreur */
                "message"=>$message,
                /** ok, ko */
                "css"=>$css,
                /** automatique, manuel */
                "type"=>$type,
                "job"=>$traitement["titre"],
                "portefeuille"=>$traitement["portefeuille"],
                "projet"=>$traitement["projet"],
                "responsable"=>$traitement["responsable"],
                "execution"=>$execution];
        array_push($traitements, $tempo);
      }

      /** On regarde si on a des traitements manuels en cours */
      $r = static::pending("start");
      if ($r!==0) {
        $bulle="bulle-info-start";
        $infoNombre=$r;
        $infoTips="Traitement en cours.";
      }

      return $this->render('batch/index.html.twig',
        [
          'salt'=>$this->getParameter('csrf.salt'),
          'date'=>$dateDernierBatch,
          'traitements'=>$traitements,
          'bulle'=>$bulle,
          'infoNombre'=>$infoNombre,
          'infoTips'=>$infoTips,
          'version' => $this->getParameter("version"), 'dateCopyright' => \date('Y')
        ]);
    }

  }
