<?php

/**
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

use App\Service\FileLogger;
use App\Entity\Portefeuille;
use App\Entity\BatchTraitement;
use App\Service\RabbitMQService;

use Doctrine\ORM\EntityManagerInterface;

use Symfony\Bundle\SecurityBundle\Security;
use App\Controller\Batch\CollecteController;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;


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

    public static $reference = "<strong>TRAITEMENT</strong>";
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
        private $em;
        private $logger;
        private $collecte;
        private $security;
        private $rabbitMQService;

    public function __construct(
        EntityManagerInterface $em,
        FileLogger $logger,
        CollecteController $collecte,
        Security $security,
        RabbitMQService $rabbitMQService,
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->collecte = $collecte;
        $this->security = $security;
        $this->rabbitMQService = $rabbitMQService;
    }

    /**
     * [Description for getMessageCount]
     * Compte le nombre de message dans une queue RabbitMQ
     *
     * @param string $queueName
     *
     * @return Response
     *
     * Created at: 14/06/2024 18:01:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/messages/count/{queueName}', name: 'messages_count_queue', methods: ['GET'])]
    public function getMessageCount(string $queueName): response
    {
         /** On créé on objet de response HTTP */
        $response = new JsonResponse();
        $messageCount = $this->rabbitMQService->getMessageCount($queueName.'_queue');
        return $response->setData(['nombre' => $messageCount], Response::HTTP_OK);
    }


    #[Route('/message/send', name: 'send_message', methods: ['GET'])]
    public function sendMessage($queueName): Response
    {
        $message = 'Hello, RabbitMQ!';

        // Envoyer un message à la queue
        $this->rabbitMQService->sendMessage($queueName, $message);

        // Fermer la connexion
        $this->rabbitMQService->close();

        return new Response('Message sent to RabbitMQ!');
    }

    /**
     * [Description for lireJournal]
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
    #[Route('/traitement/journal/lire', name: 'traitement_journal_lire', methods: ['POST'])]
    public function lireJournal(Request $request): response
    {
        /** On créé on objet de response HTTP */
        $response = new JsonResponse();

        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'portefeuille') || !property_exists($data, 'type')) {
            return $response->setData([
                'code' => 400, 'type' => 'alert', 'reference' => static::$reference,
                'message' => static::$erreur400], Response::HTTP_BAD_REQUEST);
        }

        $journal=$this->logger->downloadContent($data->portefeuille, $data->type);
        return $response->setData(['code' => 200, 'recherche' => $journal['recherche'], 'journal' => $journal['content'], Response::HTTP_OK]);
    }

    #[Route('/traitement/journal/efface', name: 'journal_efface', methods: ['DELETE'])]
    public function effaceJournal(Request $request): response
    {
        /** On créé on objet de response HTTP */
        $response = new JsonResponse();

        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'portefeuille') || !property_exists($data, 'type')) {
            return $response->setData([
                'code' => 400, 'type' => 'alert', 'reference' => static::$reference, 'message' => static::$erreur400
            ], Response::HTTP_BAD_REQUEST);
        }

        $this->logger->log($data->portefeuille, $data->type, 'delete');

        return $response->setData(['code' => 200, Response::HTTP_OK]);
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
    public function listeProjet(string $titrePortefeuille, string $portefeuille): array
    {
        /*** On instancie l'entityRepository */
        $portefeuilleRepository=$this->em->getRepository(Portefeuille::class);
        $batchTraitementRepository = $this->em->getRepository(BatchTraitement::class);

        /** On envoi le titre du portefeuille et le nom du portefeuille */
        $map=[ 'titre_portefeuille' => $titrePortefeuille, 'portefeuille' => $portefeuille ];

        /** On vérifie que le portefeuille n'est pas vide */
        $listeProjets=$batchTraitementRepository->SelectBatchTraitement($map);
        //debug : dd($portefeuille, $listeProjets, $map);
        if ($listeProjets['code']!=200) {
            return ['code' => $listeProjets['code'],static::$request=>'selectBatchTraitement'];
        }

        if (!isset($listeProjets['liste']) || count($listeProjets['liste'])===0)
        {
            return ['code' => 404, 'complement' => 'La liste des traitements ne contient pas votre projet !'];
        }

        $result=$portefeuilleRepository->selectPortefeuille($map);
        if ($result['code'] != 200) {
            return ['code' => $result['code'], 'requête' => 'selectPortefeuille'];
        }

        if (empty($result['liste'])) {
            return ['code' => 404, 'complement' => 'Votre portefeuille ne contient pas ce projet !'];
        }

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
                !property_exists($data, 'titre_portefeuille') ||
                !property_exists($data, 'portefeuille')) {
            return $response->setData(['data' => $data, 'code' => 400, 'type' => 'alert', 'reference' => static::$reference, 'message' => static::$erreur400], Response::HTTP_BAD_REQUEST);
        }

        // On extrait la liste des projets pour le portefeuille
        $les_projets=$this->listeProjet($data->titre_portefeuille, $data->portefeuille);
        if ($les_projets['code']===404){
            return $response->setData(['code' => 404, 'type' => 'alert',
            'reference' => static::$reference, 'message' => static::$erreur404, 'complement' => $les_projets['complement']], Response::HTTP_NOT_FOUND );
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        if ($utilisateur_collecte==='null') {
            /** L'utilisateur n'est pas autorisé */
            return $response->setData(['code' => 401, 'message' => static::$erreur401], Response::HTTP_UNAUTHORIZED);
            }

        /** On lance la collecte */
        foreach ($les_projets[0] as $le_projet){
            $resultat=$this->collecte->collecte($data->portefeuille, $le_projet, 'Manuel', $utilisateur_collecte);
            if ($resultat['code']===500){
                $code=$resultat['code'];
                $type="warning";
                $reference='<strong>Traitement</strong>';
                $message="La collecte du projet <b>$le_projet</b> n'a pas abouti.";
                $complement="Consulter le journal d'execution pour avoir plus d'information.";
                return $response->setData(compact('code', 'type', 'reference', 'message', 'complement'),
                Response::HTTP_OK);
            }
        }
        return $response->setData(['code' => 200, Response::HTTP_OK]);
    }

}
