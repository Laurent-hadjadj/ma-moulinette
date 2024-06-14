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
use Symfony\Bundle\SecurityBundle\Security;

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

use App\Controller\Batch\CollecteController;

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
    public static $request = "requête : ";

    private static $batch001 = "[TRAITEMENT-001] Le portefeuille de projets est vide !";
    private static $batch002 = "[TRAITEMENT-002] Récupération de la liste de projets.";
    public static $reference = "<strong>[TRAITEMENT]</strong>";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur401 = "Vous devez avoir un compte utilisateur valide  (Erreur 401).";
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
        private FileLogger $logger,
        private CollecteController $collecte,
        private Security $security
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->collecte = $collecte;
        $this->security = $security;
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

        $journal=$this->logger->downloadContent($data->portefeuille, $data->type);

        return $response->setData(['code' => 200, 'recherche' => $journal['recherche'], 'journal' => $journal['content'], Response::HTTP_OK]);
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
        $batchTraitementRepository = $this->em->getRepository(BatchTraitement::class);

        $map=[ 'portefeuille'=> $portefeuille ];

        /** On vérifie que le portefeuille n'est pas vide */
        $portefeuille=$batchTraitementRepository->SelectBatchTraitement($map);
        if ($portefeuille['code']!=200) {
            return ['code' => $portefeuille['code'],static::$request=>'selectBatchTraitement'];
        }
        if (!isset($portefeuille['liste']) || count($portefeuille['liste'])===0)
        {
            return ['code' => 404, 'type' => 'alert', 'reference' => static::$reference, 'message' => static::$erreur404];
        }

        $result=$portefeuilleRepository->selectPortefeuille($map);
        if ($result['code'] != 200) {
            return ['code' => $result['code'], 'requête' => 'selectPortefeuille'];
        }

        if (empty($result['liste'])) { return ['code'=>404]; }
        $liste = [];
        foreach (json_decode($result['liste'][0]['liste']) as $value) {
            array_push($liste, $value);
        }
        return ['code' => 200, $liste];
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

        /** On récupère les données du POST */
        $data = json_decode($request->getContent());
        $response = new JsonResponse();

        if ($data === null ||
            !property_exists($data, 'portefeuille')) {
            return $response->setData(['data' => $data, 'code' => 400, 'type' => 'alert', 'reference' => static::$reference, 'message' => static::$erreur400], Response::HTTP_BAD_REQUEST);
        }

        // On extrait la liste des projets pour le portefeuille
        $les_projets=$this->listeProjet($data->portefeuille);
        if ($les_projets['code']===404){
            return $response->setData(['code' => 404, 'type' => 'alert',
            'reference' => static::$reference, 'message' => static::$erreur404], Response::HTTP_NOT_FOUND );
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        if ($utilisateur_collecte==='null') {
            /** L'utilisateur n'est pas autorisé */
            return $response->setData(['code' => 401, 'message' => static::$erreur401], Response::HTTP_UNAUTHORIZED);
            }

        /** On lance la collecte */
        foreach ($les_projets[0] as $le_projet){
            $collecte=$this->collecte->collecte($le_projet, 'Manuel', $utilisateur_collecte);
        }

        return $response->setData(['execution' => 'end', Response::HTTP_OK]);
    }

}
