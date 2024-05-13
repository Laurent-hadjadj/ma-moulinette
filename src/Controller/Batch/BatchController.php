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

namespace App\Controller\Batch;

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
use App\Entity\BatchTraitement;
//use App\Entity\Historique;

/** Rotation des logs */
use Cesargb\Log\Rotation;
use Cesargb\Log\Exceptions\RotationFailed;

/** Class API Batch */
use App\Controller\Batch\BatchApiController;
use App\Entity\Portefeuille;

/** Client HTTP */
use App\Service\Client;

/** AMQP */
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * [Description BatchController]
 */
class BatchController extends AbstractController
{
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatMini = "Y-m-d";
    public static $timeFormat = "%H:%I:%S";
    public static $europeParis = "Europe/Paris";

    private static $batch001 = "[BATCH-001] Le traitement a déjà été mis à jour.";
    private static $batch002 = "[BATCH-002] Aucun batch trouvé.";
    private static $batch003 = "[BATCH-003] La liste des traitements a été mis à jour.";
    private static $batch004 = "[BATCH-004] Le portefeuille de projets est vide !";
    private static $batch005 = "[BATCH-005] Récupération de la liste de projets.";

    public static $erreur403 = "Vous devez avoir le rôle BATCH pour gérer les traitements (Erreur 403).";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private Connection $connection,
        private BatchApiController $api,
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->connection = $connection;
        $this->api = $api;
    }

    /**
     * [Description for isEmpty]
     *  Détermine si la queue est vide ou non
     *
     * @param mixed $queueName
     *
     * @return bool
     *
     * Created at: 08/04/2024 22:08:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isEmpty($queue): bool
    {
        /** on bind les paramétres de connexion à RabbitMQ */
        $host=$this->getParameter('rabbitmq.host');
        $port=$this->getParameter('rabbitmq.port');
        $user=$this->getParameter('rabbitmq.username');
        $password=$this->getParameter('rabbitmq.password');
        $vhost=$this->getParameter('rabbitmq.vhost');

        /** on se connecte */
        $connection = new AMQPStreamConnection($host, $port, $user, $password, $vhost);
        $channel = $connection->channel();
        /**
         * queue - Queue names may be up to 255 bytes of UTF-8 characters
         * passive - can use this to check whether an exchange exists without modifying the server state
         * durable, make sure that RabbitMQ will never lose our queue if a crash occurs - the queue will survive a broker restart
         * exclusive - used by only one connection and the queue will be deleted when that connection closes
         * auto delete - queue is deleted when last consumer unsubscribes
        */
        list($messageCount) = $channel->queue_declare($queue,true,true,false,false);

        $isEmpty=false;
        if ($messageCount === 0) {
            $isEmpty=true;
        }
        return $isEmpty;
    }

    /**
     * [Description for logrotate]
     * Journalisation des demandes de traitements différés.
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
        $path = $this->getParameter('kernel.project_dir').$this->getParameter('path.audit');
        /* Le dossier d'audit est présent */
        if ($filesystem->exists($path)) {
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
     * Ajoute un journal de traitements différés pour un portefeuile.
     *
     * @param string $portefeuille
     * @param mixed $log
     *
     * @return void
     *
     * Created at: 05/03/2023, 00:01:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function information($portefeuille, $log): int
    {
        /* On initialise le journal des traces */
        $filesystem = new Filesystem();
        $path = $this->getParameter('kernel.project_dir').'\var\audit';
        /* Le dossier d'audit est présent */
        if ($filesystem->exists($path)) {
            $name = preg_replace('/\s+/', '_', $portefeuille);
            $fichier = "$path\manuel_{$name}.log";
            $filesystem->appendToFile($fichier, $log, true);
        } else {
            return 404;
        }
        return 200;
    }

    /**
     * [Description for lireInformation]
     * Affiche le journal d'execution pour le portefeuille
     *
     * @param string $portefeuille
     * @param string $type
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
        $portefeuille = $data->portefeuille;
        $type = $data->type;

        /* On initialise le journal des traces */
        $filesystem = new Filesystem();
        $path = $this->getParameter('kernel.project_dir').'\var\audit';

        $recherche = "KO";
        /* Le dossier d'audit est présent */
        if ($filesystem->exists($path)) {
            $name = preg_replace('/\s+/', '_', $portefeuille);
            $fichier = "{$type}_$name.log";

            /** on récupère la log */
            $finder = new Finder();
            $finder->files()->in($path);
            $finder->name($fichier);

            foreach ($finder as $file) {
                $c = $file->getContents();
            }
            if (empty($c)) {
                $c = 'Pas de journal disponible.';
            } else {
                $recherche = 'OK';
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
          * 2) Pour chaque portefeuille, on programme dans la table batch_historique le traitement.
          */

        /** On crée un objet date pour marquer le job */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** 0 - On récupère la date du dernière traitement automatique
         *      Si on a déjà lancé un traitement Automatique aujourd'hui on sort
         */
        if (!empty($r)) {
            $dateDuJour = $date->format(static::$dateFormatMini);
            $dateBatch = new DateTime($r[0]['date']);
            $dateBatch->format(static::$dateFormatMini);
            if ($dateDuJour == $dateBatch) {
                $this->logger->INFO("[BATCH-001] Le traitement a déjà été mis à jour.");
                return ["message" => static::$batch001, "date" => $r[0]['date']];
            }
        }

        /** 0 - On regarde si la liste des traitements automatiques est vide ? */
        if (!static::isEmpty('queue.traitement')) {
            $this->logger->INFO("[BATCH-001] Le traitement a déjà été mis à jour.");
            return ["message" => static::$batch001 ];
            }


        /** 1 - on regarde si on a des batchs */
        $sql = "SELECT statut, titre, responsable, portefeuille, nombre_projet as nombre
                FROM batch ORDER BY statut ASC ;";
        $r = $this->connection->fetchAllAssociative($sql);
        if (empty($r)) {
            $this->logger->INFO("[BATCH-002] Aucun batch trouvé.");
            return ["message" => static::$batch002];
        }

        /** 2 - si on a des batch et que le traitement n'a pas été lancé on créé la liste */
        foreach ($r as $traitement) {
            /** on crée un objet BatchTraitement */
            $batch = new BatchTraitement();
            if ($traitement["statut"] == "0") {
                $demarrage = "Manuel";
            } else {
                $demarrage = "Auto";
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
        return ["message" => static::$batch003, "date" => $date];
    }

    /**
     * [Description for listeProjet]
     * Récupère la liste des projets depuis un portefeuille de projets.
     *
     * @param string $portefeuille
     *
     * @return array
     *
     * Created at: 09/12/2022, 12:05:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listeProjet($mode, $portefeuille): array
    {
        /** On instancie l'EntityRepository */
        $portefeuilleEntity = $this->em->getRepository(Portefeuille::class);

        $map=[ 'portefeuille'=>$portefeuille ];
        $r=$portefeuilleEntity->selectPortefeuille($mode, $map);
        if ($r['code']!=200) {
            return ['mode' => $mode, 'code' => $r['code'], 'message'=>$r['erreur']];
        }

        if (empty($r['liste'])) {
            $this->logger->INFO("[BATCH-004] Le portefeuille est vide !");
            return ['message' => static::$batch004];
        }

        $liste = [];
        foreach (json_decode($r['liste'][0]['liste']) as $value) {
            array_push($liste, $value);
        }

        $this->logger->INFO('[BATCH-005] Récupération de la liste de projets.');
        return ['message' => static::$batch005, 'liste' => $liste];
    }

    /**
     * [Description for pending]
     * On retourne le nombre de traitement selon le type
     * pending, start, error, end
     *
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
              ->prepare($sql)
              ->executeQuery()->fetchAllAssociative();
        return $r[0]['exec'];
    }

    /**
     * [Description for traitementPending]
     * On lock le job pour l'execution
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 07/02/2023, 14:06:01 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/pending', name: 'traitement_pending', methods: ['GET'])]
    public function traitementPending(Client $client, Request $request): Response
    {
        /** On récupère le portefeuille */
        $portefeuille = $request->get('nom-traitement');
        $mode = $request->get('mode');

        /** On créé un nouvel objet Json */
        $response = new JsonResponse();

        /** On vérifie que le job existe */
        $sql = "SELECT * FROM batch WHERE titre='$portefeuille' limit 1";
        $request = $this->em->getConnection()->prepare( $sql)->executeQuery()->fetchAllAssociative();

        if (empty($request)) {
            return $response->setData(["mode" => $mode, "job" => $portefeuille, "code" => "KO", "execution" => "error", Response::HTTP_OK]);
        }

        /**
         * On vérifie qu'il n'y a pas de traitement en cours.
         *  excecution = start
         */
        $r = $this->pending("start");
        $message = "pending";
        /** On met à jour le job en start pour bloquer les autres traitements */
        if ($r === 0) {
            $sql = "UPDATE batch SET execution='start' WHERE titre='$portefeuille'";
            $this->em->getConnection()->prepare($sql)->executeQuery();
            $message = "start";
        } else {
            /** Il y a déjà un job en cours */
            $message = "pending";
        }

        return $response->setData(["mode" => $mode, "code" => "OK", "execution" => $message, Response::HTTP_OK]);
    }

    /**
     * [Description for traitement]
     * On lance les traitements automatiques
     *
     * @return Response
     *
     * Created at: 04/12/2022, 17:42:22 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function traitement(Client $client): Response
    {
        /** On créé on objet de reponse HTTP */
        $response = new JsonResponse();

        /** On met à jour la liste des job */
        $initialise = $this->initialisationBatch();
        dd($initialise);
        $this->logger->INFO($initialise['message']);
        $message = explode(" ", $initialise['message']);
        if ($message[0] === "[BATCH-002]") {
            $this->logger->INFO("[BATCH-006] Pas de collecte aujourd'hui !");
            return $response->setData(
                [ "message" => "[BATCH-006]",
                    "description" => "Pas de collecte aujourd'hui !", Response::HTTP_OK]
            );
        }

        /** on récupère la date de mise à jour des jobs */
        $dateBatch = $initialise["date"]->format(static::$dateFormat);

        /** On récupère la liste des traitements planifiés pour la date du jour */
        $sql = "SELECT id, demarrage, titre, portefeuille, nombre_projet as projet
            FROM batch_traitement
            WHERE demarrage = 'Auto' AND date_enregistrement='$dateBatch'
            ORDER BY nombre_projet ASC;";
        $r = $this->connection->fetchAllAssociative($sql);

        /** On log si il n'y a pas de job à lancer */
        if (empty($r)) {
            $this->logger->INFO("[BATCH-007] Pas de traitezments programmé aujoud'hui !");
            return $response->setData(
                [ "message" => "[BATCH-007]",
                "description" => "Pas de traitements programmé aujoud'hui !", Response::HTTP_OK]
            );
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
            $id = $value['id'];
            /**
             * On récupère la liste des jobs
             * liste" => array:1 [ 0 => "fr.ma-petite-entreprise:ma-moulinette" ]
             */
            $listeProjet = $this->listeProjet($mode='null', $value['portefeuille']);

            /** On continue le traitement si la liste n'est pas vide */
            $message = explode(" ", $listeProjet['message']);
            if ($message[0] === "[BATCH-005]") {

                /** On démarre la mesure du batch */
                $debutBatch = new DateTime();
                $debutBatch->setTimezone(new DateTimeZone(static::$europeParis));
                $tempoDebutBatch = $debutBatch->format(static::$dateFormat);

                /** Pour chaque projet de la liste */
                foreach($listeProjet['liste'] as $mavenKey) {
                    /** On regarde si le projet est présent dans l'historique ? **/
                    $sql = "SELECT maven_key FROM historique WHERE maven_key='$mavenKey'";
                    $r = $this->connection->fetchAllAssociative($sql);

                    /* Le projet n'existe pas, je lance la collecte */
                    if (empty($r)) {
                        $this->logger->INFO("[BATCH-008] Le projet n'existe pas ; je lance la collecte !");
                        $this->api->batchNouvelleCollecte($client, $mavenKey);
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
                    $batchInformation = $this->api->batchInformation($client, $mavenKey);
                    $laVersionSonar = $batchInformation["information"]["projet"];
                    $laDateSonar = $batchInformation["information"]["date"];

                    /**
                     * On récupère la dernière version en base
                     *
                     * On récupère :
                     *  - La version "1.0.0-RELEASE"
                     *  - La date de l'analyse: "2022-04-10 00:00:00"
                     */
                    $sql = "SELECT version, date_version as date FROM historique
                    WHERE maven_key='$mavenKey'
                    ORDER BY version DESC, date DESC limit 1;";
                    $r = $this->connection->fetchAllAssociative($sql);
                    $laVersionMaMoulinette = $r[0]["version"];
                    $laDateMaMoulinette = $r[0]["date"];

                    if ($laVersionSonar === $laVersionMaMoulinette && $laDateSonar === $laDateMaMoulinette) {
                        $this->logger->NOTICE("[BATCH-008] Le projet existe, il est à jour.");
                    } else {
                        $this->logger->INFO("[BATCH-008] Le projet existe, il n'est pas à jour !");
                        $this->api->batchAjouteCollecte($client, $mavenKey);
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
                $sql = "UPDATE batch_traitement
                  SET debut_traitement='$tempoDebutBatch',
                      fin_traitement='$tempoFinBatch',
                      resultat = 1
                  WHERE id=$id;";
                $this->em->getConnection()->prepare($sql)->executeQuery();
            }

        }

        /** Fin du traitement */
        $interval = $debutBatch->diff($finBatch);
        $temps = $interval->format(static::$timeFormat);
        return $response->setData(["message" => "Tout va bien ($temps)",  Response::HTTP_OK]);
    }

    /**
     * [Description for traitementManuel]
     * Lance le traitement des projets en manuel
     *
     * @param Client $client
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 01/03/2023, 09:21:45 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/manuel', name: 'traitement_manuel', methods: ['POST'])]
    public function traitementManuel(Client $client, Request $request): Response
    {
        /** On vérifie que l'utilisateur a bien un rôle */
        $this->denyAccessUnlessGranted("ROLE_BATCH", null,
        "L'utilisateur essaye d'accèder à la page sans avoir le rôle ROLE_BATCH");

        /** On récupère le body */
        $data = json_decode($request->getContent());

        /** On créé on objet de reponse HTTP */
        $response = new JsonResponse();

        /** On récupère le job */
        $portefeuille = $data->nom_traitement;

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $maxiDate = $date->format(static::$dateFormat);

        $log = "=== Initialisation du traitement le ".$maxiDate." ===\n\n";
        $this->information($portefeuille, $log);

        /** On récupère les infos du traitement planifié pour la date du jour */
        $sql = "SELECT id, demarrage, titre, portefeuille, nombre_projet as projet
                FROM batch_traitement
                WHERE titre = '$portefeuille';";
        $r = $this->connection->fetchAllAssociative($sql);

        /** On test la reponse */
        if (empty($r)) {
            /** Pas de job dans la table batch_traitement */
            $log = "ERREUR : La liste des traitements est vide !!!\n";
            $this->information($portefeuille, $log);
            return $response->setData(['job' => $portefeuille, Response::HTTP_NOT_ACCEPTABLE]);
        }

        /** On traite le job  */
        foreach ($r as $value) {
            /** On récupère l'id du job */
            $id = $value['id'];
            /**
             * On récupère la liste des projets pour le job
             * liste" => array:1 [ 0 => "fr.ma-petite-entreprise:ma-moulinette" ]
             */
            $listeProjet = $this->listeProjet($data->mode,$value['portefeuille']);

            /** On continue le traitement si la liste des projets n'est pas vide */
            $message = explode(" ", $listeProjet["message"]);
            if ($message[0] === "[BATCH-005]") {
                /** On démarre la mesure du batch */
                $debutBatch = new DateTime();
                $debutBatch->setTimezone(new DateTimeZone(static::$europeParis));
                $tempoDebutBatch = $debutBatch->format(static::$dateFormat);

                $log = "INFO : Début de la collecte pour \n"."       ".$value['portefeuille']."\n";
                $this->information($portefeuille, $log);
                $i = 0;
                /** Pour chaque projet de la liste */
                foreach($listeProjet['liste'] as $mavenKey) {
                    $i = $i + 1;
                    $log = "INFO : Collecte des indicateurs pour *** $mavenKey *** \n\n";
                    $this->information($portefeuille, $log);

                    /** On regarde si le projet est présent dans l'historique ? **/
                    $sql = "SELECT maven_key FROM historique WHERE maven_key='$mavenKey'";
                    $r = $this->connection->fetchAllAssociative($sql);

                    /* Le projet n'existe pas, je lance la collecte */
                    if (empty($r)) {
                        $this->logger->INFO("[BATCH-008] Le projet n'existe pas ; je lance la collecte !");
                        $log = "INFO : Le projet n'existe pas dans la table historique ; je lance la collecte !\n";
                        $this->information($portefeuille, $log);
                        $this->api->batchNouvelleCollecte($client,$mavenKey);
                    }

                    /**
                     * On récupère la dernière version du serveur sonarqube
                     * Si la version est plus récente sur le serveur Sonarqube
                     * Alors on lance la collecte sinon on ne fait rien.
                     */
                    $batchInformation = $this->api->batchInformation($client, $mavenKey);
                    $laVersionSonar = $batchInformation["information"]["projet"];
                    $laDateSonar = $batchInformation["information"]["date"];

                    /** On récupère la dernière version en base */
                    $sql = "SELECT version, date_version as date FROM historique
                            WHERE maven_key='$mavenKey'
                            ORDER BY version DESC, date DESC limit 1;";
                    $r = $this->connection->fetchAllAssociative($sql);
                    $laVersionMaMoulinette = $r[0]["version"];
                    $laDateMaMoulinette = $r[0]["date"];

                    if ($laVersionSonar === $laVersionMaMoulinette && $laDateSonar === $laDateMaMoulinette) {
                        $log = "INFO : Le projet existe dans la table historique ; il est à jour !\n";
                        $this->information($portefeuille, $log);
                        $this->logger->NOTICE("[BATCH-008] Le projet existe, il est à jour.");
                    } else {
                        $log = "INFO : Le projet existe dans la table historique ; il n'est pas à jour !\n";
                        $this->information($portefeuille, $log);
                        $this->logger->INFO("[BATCH-008] Le projet existe, il n'est pas à jour !");
                        $this->api->batchAjouteCollecte($client,$mavenKey);
                    }
                }

                /** Fin du Batch */
                $finBatch = new DateTime();
                $finBatch->setTimezone(new DateTimeZone(static::$europeParis));
                $tempoFinBatch = $finBatch->format(static::$dateFormat);

                $log1 = "INFO : Fin de la collecte pour le projet.\n";
                $log2 = "          Fin du traitement le ".$tempoFinBatch."\n";
                $this->information($portefeuille, $log1);
                $this->information($portefeuille, $log2);

                /** On met à jour la table des traitements */
                $sql = "UPDATE batch_traitement
                  SET debut_traitement='$tempoDebutBatch',
                      fin_traitement='$tempoFinBatch',
                      resultat = 1
                  WHERE id=$id;";
                $this->em->getConnection()->prepare($sql)->executeQuery();
                $log = "INFO : Mise à jour de la table batch traitement.\n";
                $this->information($portefeuille, $log);
            }
        }

        /** Fin du traitement */
        $interval = $debutBatch->diff($finBatch);
        $temps = $interval->format(static::$timeFormat);
        $sql = "UPDATE batch SET execution='end' WHERE titre='$portefeuille'";
        $this->em->getConnection()->prepare($sql)->executeQuery();

        $log1 = "INFO : Durée d'exécution total -> ".$temps."\n";
        $log2 = "INFO : Nombre de projet -> ".$i."\n";
        $log3 = "INFO : statut -> 'end'.\n\n";
        $this->information($portefeuille, $log1);
        $this->information($portefeuille, $log2);
        $this->information($portefeuille, $log3);
        $log = "=== Fin des traitements ===\n\n";
        $this->information($portefeuille, $log);

        return $response->setData(["execution" => "end", "temps" => $temps, Response::HTTP_OK]);
    }

    /**
     * [Description for traitementAuto]
     * Lance le traitement automatique programmé ou manuel
     *
     * @param Client $client
     * @param Request $request
     *
     * @return [type]
     *
     * Created at: 10/04/2024 07:48:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/auto', name: 'traitement_auto', methods: ['POST'])]
    public function traitementAuto(Client $client, Request $request)
    {
        /** On créé on objet de reponse HTTP */
        $response = new JsonResponse();

        /** On récupère le token csrf généré par le serveur */
        $data = json_decode($request->getContent());
        $csrf = $data->token;

        /** on vérifie le token et on lance le traitement */
        if ($this->isCsrfTokenValid($this->getParameter('csrf.salt'), $csrf)) {
            $message = static::traitement($client);
        } else {
            $message = "ouuuuups ça sent mauvais !";
        }
        return $response->setData(['code'=>200, 'message' => $message, Response::HTTP_OK]);
    }

    /**
     * [Description for traitementSuivi]
     * Interface web : affiche la liste des traitements disponibles.
     *
     * @return Response
     *
     * Created at: 04/12/2022, 08:54:16 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/suivi', name: 'traitement_suivi', methods:'GET')]
    public function traitementSuivi(Request $request): Response
    {
        /** On instancie l'EntityRepository */
        $batchTraitementEntity = $this->em->getRepository(BatchTraitement::class);

        /** On teste si on est en mode Test ou pas */
        $mode = $request->get('mode');
        if (empty($mode)){
            $mode='null';
        }

        /** On initialise les information pour la bulle d'information */
        $bulle = 'bulle-info-vide';
        $infoNombre = 'x';
        $infoTips = 'Aucun traitement.';
        $render= [
            'salt' => $this->getParameter('csrf.salt'),
            'infoNombre' => $infoNombre,
            'infoTips' => $infoTips,
            'bulle' => $bulle,
            'date' => '01/01/1980',
            'traitements' => [['processus' => 'vide']],
            'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
        ];

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_BATCH')) {
            $message=static::$erreur403;
            $this->addFlash('alert', $message);
            return $this->render('batch/index.html.twig', $render);
            }

        /** On archive les logs */
        $this->logrotate();

        /** On crée un objet date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        /**
         * On récupère la date du dernier traitement automatique ou programmé
         * Pour le 08/02/2023 : date" => "2023-02-08 08:57:53"
         */
        $r=$batchTraitementEntity->selectBatchTraitementDateEnregistrementLast($mode);
        if ($r['code']!=200) {
            $message=$r['erreur'];
            $this->addFlash('alert', $message);
            return $this->render('batch/index.html.twig', $render);
        }

        /** Si on a pas trouvé de traitements dans la table */
        if (empty($r)) {
            $message = "[BATCH] Aucun traitement trouvé.";
            $this->addFlash('info', $message);
            return $this->render('batch/index.html.twig', $render);
        }

        /**
         * On récupère la liste des traitements planifiés pour la date du jour.
         */
        /** retourne la date de planification */
        $dateDernierBatch = $r['liste'][0]['date'];
        /** retourne la date au format 2023-02-08 */
        $dateTab = explode(" ", $dateDernierBatch);
        $dateLike = $dateTab[0].'%';
        $listeAll=$batchTraitementEntity->selectBatchTraitementLast($mode, $dateLike);
        if ($listeAll['code']!=200) {
            $message=$listeAll['erreur'];
        }

        /** On génére les données pour le tableau de suivi */
        $traitements = [];
        foreach ($listeAll['liste'] as $traitement) {
            /** Calcul de l'execution pour un traitement qui a démaré. */
            if (!empty($traitement['debut'])) {
                $resultat = $traitement['resultat'];

                /** on définit le message et la class css */
                if ($resultat == 0) {
                    $message = "Erreur";
                    $css = "ko";
                } else {
                    $message = "Succès";
                    $css = "ok";
                }
                $debut = new dateTime($traitement['debut']);
                $fin = new dateTime($traitement['fin']);
                $interval = $debut->diff($fin);
                $execution = $interval->format(static::$timeFormat);
            }

            /** on définit le type */
            if ($traitement['demarrage'] === "Auto") {
                $type = "automatique";
            } else {
                $type = "manuel";
            }

            /** On formate les données pour les batchs qui n'ont pas été lancé (i.e MANUEL) */
            if (empty($traitement['debut'])) {
                $message = "---";
                $css = "oko";
                $execution = "--:--:--";
            }

            $tempo = ["processus" => "Tout va bien !",
                        /** Auto ou Manuel */
                        'demarrage' => $traitement['demarrage'],
                        /** Succès, Erreur */
                        'message' => $message,
                        /** ok, ko */
                        'css' => $css,
                        /** automatique, manuel */
                        'type' => $type,
                        'job' => $traitement['titre'],
                        'portefeuille' => $traitement['portefeuille'],
                        'projet' => $traitement['projet'],
                        'responsable' => $traitement['responsable'],
                        'execution' => $execution];
            array_push($traitements, $tempo);
        }

        /** On regarde si on a des traitements manuels en cours */
        $r = static::pending('start');
        if ($r !== 0) {
            $bulle = 'bulle-info-start';
            $infoNombre = $r;
            $infoTips = 'Traitement en cours.';
        }

        return $this->render(
            'batch/index.html.twig',
            [
                'salt' => $this->getParameter('csrf.salt'),
                'date' => $dateDernierBatch,
                'traitements' => $traitements,
                'bulle' => $bulle,
                'infoNombre' => $infoNombre,
                'infoTips' => $infoTips,
                'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
            ]
        );
    }

}
