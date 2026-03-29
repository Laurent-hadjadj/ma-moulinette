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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request, Response, JsonResponse, BinaryFileResponse};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Ulid;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Controller\Batch\CollecteController;
use App\Entity\{BatchTraitement, BatchExecution, BatchExecutionJournal, BatchProfiling};
use App\Service\ListeProjetPortefeuilleService;

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
    private static $traitementManuel = 'TRAITEMENT MANUEL';
    private static $loggerUpdateBatchTraitement = '[Traitement-Manuel] ❌ Échec de la requête updateBatchTraitement';

    public function __construct(
        private CollecteController $collecte,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        #[Autowire(service: 'monolog.logger.profiling')]
        private LoggerInterface $profilerLogger,
        private Security $security,
        private ListeProjetPortefeuilleService $listeProjetService
    ) {
    }

    #[Route('/workers/pendingWorker.js', name: 'worker_pending')]
    public function worker(): Response
    {
        $path = $this->getParameter('kernel.project_dir').'/assets/js/mon-application/batch/pendingWorker.js';
        return new BinaryFileResponse($path, 200, ['Content-Type' => 'application/javascript']);
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
    #[Route('/api/secure/traitement/pending', name: 'get_pending_or_inprogress', methods: ['GET'])]
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
            'in_progress' => $count['progress'] ?? 0,
        ], Response::HTTP_OK);
    }

    /**
     * [Description for addPending]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 08/11/2025 19:28:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/traitement/add-pending', name: 'add_pending', methods: ['POST'])]
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
    #[Route('/api/secure/traitement/start', name: 'traitement_start', methods: ['POST'])]
    public function traitementManuel(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /traitement/start");

        // ========= Debug =========
        /*'$debug = $this->collecte->collecte(
                'test_portefeuille',
                'fr.ma-moulinette:monapplication',
                static::$traitementManuel,
                'test_utilisateur'
            );*/
        // =========================

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

            if ($add_pending['code'] !== 200){
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
                'message' => 'Un traitement est déjà en cours. Votre demande a été mise en attente (Erreur 202).',
            ], Response::HTTP_OK);
        }

        // On extrait la liste des projets pour le portefeuille depuis la table batch_traitement
        $les_projets = $this->listeProjetService->listeProjet($data->titre_portefeuille, $data->portefeuille);

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
            static::$traitementManuel
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
            $this->logger->error(static::$loggerUpdateBatchTraitement, [
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

        /** =============================
        *  Création du job principal
        *  ============================= */
        $batchExecution = new BatchExecution(
            'Collecte du ' . date('d/m/Y H:i'),
            $execution_id,
            Ulid::fromString($data->traitement_id),
            $utilisateur_collecte,
            static::$traitementManuel
        );

        // On enregistre le job immédiatement pour obtenir sa PK (id int)
        $this->em->persist($batchExecution);

        $debut_traitement = new \DateTime('now', new \DateTimeZone(static::$europeParis));

        /** Mise à jour de la table des traitements */
        $map = [
            'debut_traitement' => $debut_traitement->format(static::$dateFormat),
            'fin_traitement' => null,
            'success' => null,
            'in_progress' => true,
            'pending' => null,
            'traitement_id' => $data->traitement_id,
        ];

        $update = $batchTraitementRepos->updateBatchTraitement($map);

        if ($update['code'] !== 200) {
            $this->logger->error(static::$loggerUpdateBatchTraitement, [
                'code' => $update['code'],
                'message' => $update['message'] ?? static::$noMessage,
                'erreur' => $update['erreur'] ?? static::$noError,
            ]);

            return new JsonResponse([
                'code' => $update['code'],
                'type' => 'error',
                'message' => "Impossible de mettre à jour le traitement (Erreur {$update['code']}).",
                'erreur' => $update['erreur'] ?? null,
            ], Response::HTTP_OK);
        }

        // =====================================================
        // === PROFILING - INITIALISATION ======================
        // =====================================================
        $totalStart = microtime(true);
        $profilingRecords = [];

        $this->profilerLogger->info('[PROFILING] --- Démarrage du traitement manuel ---');
        $this->profilerLogger->info(sprintf('[PROFILING] Portefeuille: %s / Utilisateur: %s', $data->portefeuille, $utilisateur_collecte));

        /** On lance la collecte */
        $processed = 0;

        foreach ($les_projets['liste'] as $le_projet) {

            // Démarre le chronomètre et la mesure mémoire
            $projectStart = microtime(true);
            $memBefore = memory_get_usage(true);

            // === Collecte principale ===
            $result = $this->collecte->collecte(
                $data->portefeuille,
                $le_projet,
                static::$traitementManuel,
                $utilisateur_collecte
            );

            // === Statistiques ===
            $memAfter = memory_get_usage(true);
            $elapsed = microtime(true) - $projectStart;
            $memUsed = round(($memAfter - $memBefore) / 1024 / 1024, 1);

            $profilingRecords[] = [
                'projet' => $le_projet,
                'time' => round($elapsed, 2),
                'memory' => $memUsed,
                'status' => $result['code'],
            ];

            // === Journal d’exécution ===
            $explose_le_projet = explode(':', $le_projet, 2);
            $nom_projet = (count($explose_le_projet) === 2) ? $explose_le_projet[1] : $le_projet;

            $journal = new BatchExecutionJournal($result['code'], $data->portefeuille, $nom_projet, $result['compte_rendu'], new \DateTimeImmutable());

            $batchExecution->addJournal($journal);
            $this->em->persist($journal);

            if ($result['code'] === 500) {
                $this->profilerLogger->warning(sprintf(
                    '[PROFILING] ❌ Erreur sur %s (%ss / +%s MB)',
                    $le_projet,
                    round($elapsed, 2),
                    $memUsed
                ));

                $batchTraitementRepos->updateBatchTraitement([
                    'debut_traitement' => $debut_traitement->format(static::$dateFormat),
                    'success' => false,
                    'in_progress' => false,
                    'pending' => false,
                    'fin_traitement' => (new \DateTime('now', new \DateTimeZone(static::$europeParis)))->format(static::$dateFormat),
                    'traitement_id' => $data->traitement_id,
                ]);

                $code = $result['code'];
                $type = 'warning';
                $message = "La collecte du projet <strong>$le_projet</strong> n'a pas abouti.<br>Consultez le journal d’exécution pour plus d’informations.";

                $this->em->flush();
                return new JsonResponse(compact('code', 'type', 'message'), Response::HTTP_OK);
            }

            // === Flush périodique et nettoyage mémoire ===
            $this->logger->debug('peak before flush: ' . memory_get_peak_usage(true)/1024/1024 . ' MB');
            $this->em->flush();
            $this->logger->debug('peak after flush: ' . memory_get_peak_usage(true)/1024/1024 . ' MB');
            $this->em->clear();

            $batchExecution = $this->em->getReference(BatchExecution::class, $batchExecution->getId());
            gc_collect_cycles();
            gc_mem_caches();

            $processed++;
            $this->profilerLogger->info(sprintf(
                '[PROFILING] ✅ %s traité en %ss (+%s MB)',
                $le_projet,
                round($elapsed, 2),
                $memUsed
            ));
        }

        // =====================================================
        // === PROFILING - SYNTHÈSE GLOBALE ====================
        // =====================================================

        $totalEnd = microtime(true);
        $totalTime = round($totalEnd - $totalStart, 2);
        $avgTime = $totalTime / max(count($profilingRecords), 1);
        $avgMem = array_sum(array_column($profilingRecords, 'memory')) / max(count($profilingRecords), 1);

        $this->profilerLogger->info('[PROFILING] =======================');
        $this->profilerLogger->info("[PROFILING] Temps total : {$totalTime}s pour " . count($profilingRecords) . " projets");
        $this->profilerLogger->info("[PROFILING] Temps moyen par projet : " . round($avgTime, 2) . "s");
        $this->profilerLogger->info("[PROFILING] Mémoire moyenne par projet : " . round($avgMem, 1) . " MB");

        foreach ($profilingRecords as $p) {
            $this->profilerLogger->info(sprintf(
                "[PROFILING] - %s → %ss (+%s MB) [%s]",
                $p['projet'],
                $p['time'],
                $p['memory'],
                $p['status'] === 200 ? 'OK' : 'ERR'
            ));
        }
        $this->profilerLogger->info('[PROFILING] =======================');

        $fin_traitement = new \DateTime('now', new \DateTimeZone(static::$europeParis));
        $interval = $debut_traitement->diff($fin_traitement);
        $temps_traitement = $interval->format('%H:%i:%s.%f');

        // On récupère les stats issues du profiling
        $nb_projets = count($les_projets['liste']);
        $memoire_peak = memory_get_peak_usage(true) / 1024 / 1024; // MB
        $memoire_moyenne = round($avgMem, 2);
        $temps_total = round($totalEnd - $totalStart, 2);
        $temps_moyen = $temps_total / max(count($les_projets['liste']), 1);

        // Création du profiling en base
        $profiling = new BatchProfiling(
            portefeuille: $data->portefeuille,
            nbProjets: $nb_projets,
            tempsTotal: $temps_total,
            tempsMoyen: $temps_moyen,
            memoirePeak: $memoire_peak,
            memoireMoyenne: $memoire_moyenne,
            utilisateur: $utilisateur_collecte,
            executionReference: (string)$execution_id
        );

        $this->em->persist($profiling);
        $this->logger->debug('peak before flush: ' . memory_get_peak_usage(true)/1024/1024 . ' MB');
        $this->em->flush();
        $this->logger->debug('peak after flush: ' . memory_get_peak_usage(true)/1024/1024 . ' MB');
        $this->em->clear();

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
            $this->logger->error(static::$loggerUpdateBatchTraitement, [
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

        unset($map, $les_projets, $data, $profilingRecords);
        gc_collect_cycles();
        gc_mem_caches();

        return new JsonResponse([
            'code' => 200,
            'message' => 'Collecte terminée avec succès',
            'reference' => (string) $execution_id,
            'temps_traitement' => $temps_traitement
        ], Response::HTTP_OK);
    }

}
