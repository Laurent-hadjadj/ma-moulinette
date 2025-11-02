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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Uid\Ulid;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

use App\Controller\Batch\CollecteController;
use App\Entity\Portefeuille;
use App\Entity\BatchTraitement;
use App\Entity\BatchExecution;
use App\Entity\BatchExecutionJournal;

/**
 * [Description BatchController]
 */
class BatchManuelController extends AbstractController
{
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static $noMessage = 'Aucun message remonté.';
    private static $noError = 'Aucune erreur remontée.';
    private static $europeParis = 'Europe/Paris';
    private static $dateFormat = "Y-m-d H:i:s";

    public function __construct(
        private CollecteController $collecte,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private Security $security,
    ) {
    }

    #[Route('/workers/pendingWorker.js', name: 'worker_pending')]
    public function worker(): Response
    {
        $path = $this->getParameter('kernel.project_dir').'/assets/js/mon-application/batch/pendingWorker.js';
        return new BinaryFileResponse($path, 200, ['Content-Type' => 'application/javascript']);
    }

    /**
     * [Description for listeProjet]
     * Récupère la liste des projets depuis un portefeuille de projets.
     *
     * @param string $titre_portefeuille
     * @param string $portefeuille
     *
     * @return array
     *
     * Created at: 09/12/2022, 12:05:30 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listeProjet(string $titre_portefeuille, string $portefeuille): array
    {
        /*** On instancie l'entityRepository */
        $portefeuilleRepos = $this->em->getRepository(Portefeuille::class);
        $batchTraitementRepos = $this->em->getRepository(BatchTraitement::class);

        /** On envoi le titre du portefeuille et le nom du portefeuille */
        $map = [
            'titre_portefeuille' => $titre_portefeuille,
            'portefeuille' => $portefeuille
        ];

        /** On vérifie que le portefeuille n'est pas vide pour le traitement */
        // liste" => [ "id" => 1, "mode_collecte" => "Manuel", "titre" => "EXP",
        // "portefeuille" => "JAVA", "projet" => 3 ]
        $traitement = $batchTraitementRepos->selectBatchTraitement($map);

        if ($traitement['code'] !== 200) {
            $this->logger->error('[Traitement-Manuel] ❌ Échec de la requête selectBatchTraitement', [
                'code' => $traitement['code'],
                'message' => $traitement['message'] ?? static::$noMessage,
                'erreur' => $traitement['erreur'] ?? static::$noError,
                ]);

            return [
                'code' => $traitement['code'],
                'type' => 'error',
                'message' => "Une erreur est survenue lors de la récupération des projets du portefeuille ({$traitement['code']}).",
                'erreur' => $traitement['erreur']
            ];
        }

         /** La liste est vide */
        if (!isset($traitement['liste']) || count($traitement['liste']) === 0)
        {
            $this->logger->warning('[Batch Manuel] ⚠️ La liste des traitements ne contient pas le portefeuille !', [
                'code' => $traitement['code'],
                'message' => $traitement['message'] ?? static::$noMessage,
                'erreur' => $traitement['erreur'] ?? static::$noError,
                'portefeuille' => $titre_portefeuille ?? 'inconnu'
                ]);

            return [
                'code' => 404,
                'type' => 'warning',
                'message' => 'La liste des traitements ne contient pas le portefeuille (Erreur 404).',
                'erreur' => $traitement['erreur'] ?? null
            ];
        }

        /** On récupère le portefeuille de projets */
        $liste_projets = $portefeuilleRepos->selectPortefeuille($map);

        if ($liste_projets['code'] !== 200) {
            $this->logger->error('[Batch Manuel] ❌ Échec de la requête selectPortefeuille', [
                'code' => $liste_projets['code'],
                'message' => $liste_projets['message'] ?? static::$noMessage,
                'erreur' => $liste_projets['erreur'] ?? static::$noError,
                'portefeuille' => $titre_portefeuille ?? 'inconnu'
                ]);

            return [
                'code' => $liste_projets['code'],
                'type' => 'error',
                'message' => "Le portefeuille de projet n'est pas accessible (Erreur {$liste_projets['code']}).",
                'erreur' =>  $liste_projets['erreur'] ?? null
            ];
        }

        if (empty($liste_projets['liste'])) {
            $this->logger->warning('[Batch Manuel] ⚠️ La liste des traitements ne contient pas votre portefeuille !', [
                'code' => $liste_projets['code'],
                'message' => $liste_projets['message'] ?? static::$noMessage,
                'erreur' => $liste_projets['erreur'] ?? static::$noError,
                'portefeuille' => $titre_portefeuille ?? 'inconnu'
                ]);

            return [
                'code' => 404,
                'type' => 'warning',
                'message' => "Votre portefeuille ne contient pas ce projet (Erreur {$liste_projets['code']}).",
                'erreur' => $liste_projets['erreur'] ?? static::$noError
            ];
        }

        $liste = json_decode($liste_projets['liste'][0]['liste'], true) ?? [];

        // On renvoie une liste de maven_key
        return [
            'code' => 200,
            'liste' => $liste
        ];
    }

    /**
     * [Description for countPendingJob]
     *
     * @return JsonResponse
     *
     * Created at: 27/10/2025 19:46:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/traitement/pending', name: 'get_pending_or_inprogress', methods: ['GET'])]
    public function getPendingOrProgress(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/traitement/pending");

        $batchTraitementRepos = $this->em->getRepository(BatchTraitement::class);
        $count = $batchTraitementRepos->countBatchTraitementPendingAndProgress();

        if ($count['code'] !== 200){
            $this->logger->error("[Traitement-Manuel] ❌ Échec de la requête countBatchTraitementPendingAndProgress.", [
                'erreur' => $count['erreur'],
            ]);

            return new JsonResponse([
                'code' => $count['code'],
                'type' => 'critical',
                'message' => "Une erreur s'est produite lors de la récupération du nombre de traitements en attente et en cours (Erreur {$count['code']}).",
                'erreur' => $count['erreur']
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'message' => 'Récupération du nombre de traitements en attente et en cours.',
            'pending' => $count['pending'] ?? 0,
            'in_progress' => $count['progress'] ?? 0
        ], Response::HTTP_OK);
    }

    #[Route('/api/traitement/add-pending', name: 'add_pending', methods: ['POST'])]
    public function addPending(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/traitement/add-pending");

        $batchTraitementRepos = $this->em->getRepository(BatchTraitement::class);

        /** On récupère les données du POST */
        $data = json_decode($request->getContent());

        if ($data === null || !property_exists($data, 'traitement_id')){
                $this->logger->error("[Traitement-Manuel] ❌ Requête invalide : clé 'traitement_id' manquante ou JSON mal formé.",[ 'payload' => $data ]);

                return new JsonResponse([
                    'code' => 400,
                    'type' => 'error',
                    'message' => static::$erreur400
                ], Response::HTTP_OK);
        }

        $map = [
                'traitement_id' => $data->traitement_id,
                'pending' => true
            ];

            $add_pending = $batchTraitementRepos->updateBatchTraitementPending($map);

            if ($add_pending !== 200){
                $this->logger->alert("[Traitement-Manuel] ❌ Échec de la requête updateBatchTraitementPending.", [
                'code' => $add_pending['code'],
                'message' => $add_pending['erreur'] ?? null
                ]);

                return new JsonResponse([
                    'code' => $add_pending['code'],
                    'type' => 'error',
                    'message' => "Il n'est pas possible de mettre le traitement en file d'attente (Erreur {$add_pending['code']}).",
                    'trace' => $add_pending['erreur']
                ], Response::HTTP_OK);
            }

        return new JsonResponse([
            'code' => 200,
            'message' => "Ajout du traitement en file d'attente.",
        ], Response::HTTP_OK);
    }

    /**
     * [Description for traitementManuel]
     * Lance le traitement des projets en manuel
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 01/03/2023, 09:21:45 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/traitement/start', name: 'traitement_start', methods: ['POST'])]
    public function traitementManuel(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /traitement/start");

        $batchTraitementRepos = $this->em->getRepository(BatchTraitement::class);
        $user = $this->security->getUser();

        $this->denyAccessUnlessGranted("ROLE_BATCH",
        null, "L'utilisateur essaye d’accéder à la page sans avoir le rôle ROLE_BATCH");

        /** On récupère les données du POST */
        $data = json_decode($request->getContent());

        if ($data === null ||
                !property_exists($data, 'traitement_id') ||
                !property_exists($data, 'titre_portefeuille') ||
                !property_exists($data, 'portefeuille')){
                $this->logger->error("[Traitement-Manuel] ❌ Requête invalide : clé 'traitement_id', 'titre_portefeuille' ou 'portefeuille' manquante ou JSON mal formé.",[
                    'utilisateur' => $user,
                    'payload' => $data
                ]);

                return new JsonResponse([
                    'code' => 400,
                    'type' => 'error',
                    'message' => static::$erreur400
                ], Response::HTTP_OK);
        }

        /** On regarde si un traitement est déjà démarré */
        $isStarted = $batchTraitementRepos->findBy(['inProgress' => true]);
        if ($isStarted){
            $this->logger->info("[Traitement-Manuel] ⚠️ Un traitement est déjà en cours d'execution.");

            $map = [
                'traitement_id' => $data->traitement_id,
                'pending' => true
            ];

            $add_pending = $batchTraitementRepos->updateBatchTraitementPending($map);

            if ($add_pending !== 200){
                $this->logger->alert("[Traitement-Manuel] ❌ Échec de la requête updateBatchTraitementPending.", [
                'code' => $add_pending,
                'message' => $add_pending['erreur'] ?? null
                ]);

                return new JsonResponse([
                    'code' => $add_pending['code'],
                    'type' => 'error',
                    'message' => "Il n'est pas possible de mettre le traitement en file d'attente (Erreur {$add_pending['code']}).",
                    'trace' => $add_pending['erreur']
                ], Response::HTTP_OK);
            }

            return new JsonResponse([
                'code' => 202,
                'type' => 'info',
                'message' => 'Un traitement est déjà en cours. Votre demande a été mise en attente.',
            ], Response::HTTP_OK);
        }

        // On extrait la liste des projets pour le portefeuille depuis la table batch_traitement
        $les_projets = $this->listeProjet($data->titre_portefeuille, $data->portefeuille);

        if ($les_projets['code'] === 404){
            $this->logger->warning("[Traitement-Manuel] ❌ La liste est vide ou n'existe plus.", [
                'code' => $les_projets,
                'message' => $les_projets['erreur'] ?? null
            ]);

            return new JsonResponse([
                'code' => 404,
                'type' => $les_projets['type'],
                'message' => $les_projets['message'],
                'trace' => $les_projets['erreur']
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        // Création du job principal
        $execution_id = new Ulid();
        $batchExecution = new BatchExecution(
            'Collecte du ' . date('d/m/Y H:i'),
            $execution_id,
            Ulid::fromString($data->traitement_id),
            $utilisateur_collecte,
            'TRAITEMENT MANUEL'
        );

        $this->em->persist($batchExecution);

        $debut_traitement = new \DateTime('now', new \DateTimeZone(static::$europeParis));

        /** On met à jour la table des traitements */
        $map = [
            'debut_traitement' => $debut_traitement->format(static::$dateFormat),
            'fin_traitement' => null,
            'success' => null,
            'in_progress' => true,
            'pending' => null,
            'traitement_id' => $data->traitement_id
        ];

        $update = $batchTraitementRepos->updateBatchTraitement($map);

        if ($update['code'] !== 200) {
            $this->logger->error('[Batch Manuel] ❌ Échec de la requête updateBatchTraitement', [
                'code' => $update['code'],
                'message' => $update['message'] ?? static::$noMessage,
                'erreur' => $update['erreur'] ?? static::$noError,
                'id' => $data->projet_id ?? 'inconnu',
                'wip' => 'in_progress = true'
                ]);

            return new JsonResponse([
                'code' => $update['code'],
                'type' => 'error',
                'message' => "Il n'est pas possible de mettre à jour le traitement (Erreur {$update['code']}).",
                'erreur' =>  $update['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        /** On lance la collecte */
        foreach ($les_projets['liste'] as $le_projet){
            $result = $this->collecte->collecte($data->portefeuille, $le_projet, 'TRAITEMENT MANUEL', $utilisateur_collecte);
            $explose_le_projet = explode(':',$le_projet,2);
            $nom_projet = (count($explose_le_projet) == 2 ) ?  $explose_le_projet[1] : $le_projet;

            /** On crée le journal d'execution */
            $journal = new BatchExecutionJournal();
            $journal->setCode($result['code']);
            $journal->setPortefeuille($data->portefeuille);
            $journal->setNomProjet($nom_projet);
            $journal->setCompteRendu($result['compte_rendu']);
            $journal->setDateExecution(new \DateTimeImmutable());

            $batchExecution->addJournal($journal);
            $this->em->persist($journal);

            if ($result['code'] === 500){
                $code = $result['code'];
                $type = 'warning';
                $message = "La collecte du projet <strong>$le_projet</strong> n'a pas abouti.<br>Consulter le journal d'execution pour avoir plus d'information.";

                $this->em->flush();

                return new JsonResponse(compact('code', 'type', 'message'),
                Response::HTTP_OK);
            }
            /** Flush global */
            $this->em->flush();
            unset($le_projet);
        }

        $fin_traitement = new \DateTime('now', new \DateTimeZone(static::$europeParis));
        $interval = $debut_traitement->diff($fin_traitement);
        $temps_traitement = $interval->format('%H:%i:%s.%f');

        /** On met à jour la table des traitements */
        $map = [
            'debut_traitement' => $debut_traitement->format(static::$dateFormat),
            'fin_traitement' => $fin_traitement->format(static::$dateFormat),
            'success' => true,
            'in_progress' => false,
            'pending' => false,
            'traitement_id' => $data->traitement_id
        ];

        $update = $batchTraitementRepos->updateBatchTraitement($map);
        if ($update['code'] !== 200) {
            $this->logger->error('[Batch Manuel] ❌ Échec de la requête updateBatchTraitement', [
                'code' => $update['code'],
                'message' => $update['message'] ?? static::$noMessage,
                'erreur' => $update['erreur'] ?? static::$noError,
                'id' => $data->projet_id ?? 'inconnu',
                'wip' => 'in_progress = false'
            ]);

            return new JsonResponse([
                'code' => $update['code'],
                'type' => 'error',
                'message' => "Il n'est pas possible de mettre à jour le traitement (Erreur {$update['code']}).",
                'erreur' =>  $update['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        unset($map);
        unset($les_projets);
        unset($data);
        return new JsonResponse([
            'code' => 200,
            'message' => 'Collecte terminée avec succès',
            'reference' => (string) $execution_id,
            'temps_traitement' => $temps_traitement
        ], Response::HTTP_OK);
    }

}
