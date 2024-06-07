<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
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

/** Les services */
use App\Service\FileLogger;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Portefeuille;
use App\Entity\Batch;
use App\Entity\BatchTraitement;

/** AMQP */
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Exchange\AMQPExchangeType;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * [Description BatchController]
 */
class BatchManuelController extends AbstractController
{
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatMini = "Y-m-d";
    public static $timeFormat = "%H:%I:%S";
    public static $europeParis = "Europe/Paris";

    private static $batch001 = "[TRAITEMENT-001] Le traitement a déjà été mis à jour.";
    private static $batch002 = "[TRAITEMENT-002] Aucun batch trouvé.";
    private static $batch003 = "[TRAITEMENT-003] La liste des traitements a été mis à jour.";
    private static $batch004 = "[TRAITEMENT-004] Le portefeuille de projets est vide !";
    private static $batch005 = "[TRAITEMENT-005] Récupération de la liste de projets.";
    public static $reference = "<strong>[TRAITEMENT]</strong>";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur403 = "Vous devez avoir le rôle BATCH pour gérer les traitements (Erreur 403).";
    public static $erreur404 = "L'appel à l'API n'a pas abouti (Erreur 404).";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private FileLogger $logger
    ) {
        $this->em = $em;
        $this->logger = $logger;
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
        /** on bind les paramètres de connexion à RabbitMQ */
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
        if ($data === null || !property_exists($data, 'portefeuille') || !property_exists($data, 'type')) {
            return $response->setData([
                'code' => 400, 'type' => 'alert', 'reference' => static::$reference, 'message' => static::$erreur400
            ], Response::HTTP_BAD_REQUEST);
        }

        $journal=$this->logger->downloadContent($data->type, $data->portefeuille);

        return $response->setData(['code' => 200, 'recherche' => $journal['recherche'], 'journal' => $journal['content'], Response::HTTP_OK]);
    }

    /**
     * [Description for initialisationBatch]
     * Permet de créer la liste des job a exécuter
     *
     * @return array
     *
     * Created at: 04/12/2022, 08:48:52 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function initialisationBatch(): array
    {
         /*** On instancie l'entityRepository */
        $batchRepository=$this->em->getRepository(Batch::class);
        $portefeuilleRepository=$this->em->getRepository(Portefeuille::class);
        /** On démarre la session de traitement de collecte des informations SonarQube :
          * 1) On récupère la liste de batch dont le statut est true (1) ;
          * 2) Pour chaque portefeuille, on programme dans la table batch_historique le traitement.
          */

        /** On crée un objet date pour marquer le job */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** 0 - On récupère la date du dernière traitement automatique
         *      Si on a déjà lancé un traitement Automatique aujourd'hui on sort
         */
        if (!empty($r)) {
            $dateDuJour = $date->format(static::$dateFormatMini);
            $dateBatch = new \DateTimeImmutable($r[0]['date']);
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
        $request=$batchRepository->selectBatchByStatut();
        if (empty($request)) {
            $this->logger->INFO("[BATCH-002] Aucun batch trouvé.");
            return ["message" => static::$batch002];
        }

        /** 2 - si on a des batch et que le traitement n'a pas été lancé on créé la liste */
        foreach ($request as $traitement) {
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
    public function listeProjet(string $portefeuille): array
    {
        /*** On instancie l'entityRepository */
        $portefeuilleRepository=$this->em->getRepository(Portefeuille::class);

        $map=[ 'portefeuille'=> $portefeuille ];
        $result=$portefeuilleRepository->selectPortefeuille($map);
        if ($result['code'] != 200) {
            $this->logger->log($portefeuille, "*** Erreur :" . $result['code'] . " selectPortefeuille\n");
            return ['code' => $result['code'], 'requête' => 'selectPortefeuille'];
        }

        if (empty($result['liste'])) {
            $this->logger->log($portefeuille, "[Traitement-004] Le portefeuille est vide !\n");
            return ['message' => static::$batch004];
        }

        /** Décodage de la liste JSON des projets et préparation de la liste */
        $liste = [];
        foreach (json_decode($result['liste'][0]['liste']) as $value) {
            $liste[] = $value;
        }
        /** récupération réussie de la liste de projets */
        $this->logger->log($portefeuille, "[BATCH-005] Récupération de la liste de projets.\n");
        return ['message' => static::$batch005, 'liste' => $liste];
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
    public function traitementManuel(Request $request): Response
    {
        $this->denyAccessUnlessGranted("ROLE_BATCH", null, "L'utilisateur essaye d’accéder à la page sans avoir le rôle ROLE_BATCH");

        $response = new JsonResponse();

        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'portefeuille')) {
            return $response->setData([
                'data' => $data,
                'code' => 400,
                'type' => 'alert',
                'reference' => static::$reference,
                'message' => static::$erreur400
            ], Response::HTTP_BAD_REQUEST);
        }

        //$portefeuille = $data->portefeuille;

        $results = [['id' => 0, 'portefeuille' => 'Portefeuille-2048', 'projet' => 1]];
        foreach ($results as $value) {
            $this->processPortefeuille($value);
        }

        return $response->setData(["execution" => "end", "temps" => 'debutBatch, finBatch', Response::HTTP_OK]);
    }

}
