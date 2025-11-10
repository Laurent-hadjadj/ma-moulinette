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
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\{Request, Response, JsonResponse};
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
class BatchAutoController extends AbstractController
{
    private static $erreur400 = "❌ La requête est incorrecte (Erreur 400).";
    private static $erreur403 = "🚫 Vous n'êtes pas autorisé à acceder à ce service. (Erreur 403).";
    private static $noMessage = 'Aucun message remonté.';
    private static $noError = 'Aucune erreur remontée.';
    private static $europeParis = 'Europe/Paris';
    private static $dateFormat = "Y-m-d H:i:s";
    private static $traitementAutomatique = 'TRAITEMENT AUTOMATIQUE';
    private static $loggerUpdateBatchTraitement = '[Traitement-Automatique] ❌ Échec de la requête updateBatchTraitement';

    public function __construct(
        private CollecteController $collecte,
        private ParameterBagInterface $params,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        #[Autowire(service: 'monolog.logger.profiling')]
        private LoggerInterface $profilerLogger,
        private ListeProjetPortefeuilleService $listeProjetService,
    ) {
    }

    /**
     * [Description for traitementListe]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 08/11/2025 19:17:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/public/traitement/automatique/liste', name: 'public_traitement_liste', methods: ['POST'])]
    public function traitementListe(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/public/traitement/automatique/list");

        $batchTraitementRepos = $this->em->getRepository(BatchTraitement::class);

        /** On récupère les données du POST */
        $data = json_decode($request->getContent());

        if ($data === null || !property_exists($data, 'token')){
                $this->logger->error("[Traitement-Automatique] ❌ Requête invalide : clé 'token' manquante ou JSON mal formé.",[
                    'payload' => $data
                ]);

                return new JsonResponse([
                    'code' => 400,
                    'type' => 'error',
                    'message' => static::$erreur400
                ], Response::HTTP_OK);
        }

        // Vérification du token
        $token = $this->params->get('api.client_token');
        if ($data->token !== $token){
        $this->logger->error("[Traitement-Automatique] 🚫 Vous n'êtes pas autorisé à acceder à ce service.", ['token' => $data->token]);

        return new JsonResponse([
            'code' => 403,
            'type' => 'error',
            'message' => static::$erreur403
        ], Response::HTTP_FORBIDDEN);
        }

        /** On récupère la liste des traitements Automatique */
        $get_liste = $batchTraitementRepos->selectBatchTraitementAutomatiqueListe();

        if ($get_liste['code'] !== 200){
            $this->logger->alert("[Traitement-Automatique] ❌ Échec de la requête selectBatchTraitementListe.", [
            'code' => $get_liste,
            'message' => $get_liste['erreur'] ?? null
            ]);

            return new JsonResponse([
                'code' => $get_liste['code'],
                'type' => 'error',
                'message' => "Il n'est pas possible de mettre le traitement en file d'attente (Erreur {$get_liste['code']}).",
                'trace' => $get_liste['erreur']
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
        'code' => 200,
        'message' => 'Liste des projets pour les traitements automatique récupérée.',
        'liste_traitement' => $get_liste['liste'] ?? null
        ], Response::HTTP_OK);
    }

    /**
     * [Description for traitementAuto]
     * Lance le traitement automatique programmé
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
    #[Route('/api/public/traitement/automatique/start', name: 'traitement_automatique_start', methods: ['POST'])]
    public function traitementAuto(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/public/traitement/automatique/start");

        $batchTraitementRepos = $this->em->getRepository(BatchTraitement::class);

        /** On récupère les données du POST */
        $data = json_decode($request->getContent());

        if ($data === null ||
            !property_exists($data, 'token') ||
            !property_exists($data, 'nom_traitement') ||
            !property_exists($data, 'portefeuille') ||
            !property_exists($data, 'traitement_id')){
            $this->logger->error("[Traitement-Automatique] ❌ Requête invalide : clé 'token', 'nom_traitement', 'portefeuille' ou 'traitement_id' manquante ou JSON mal formé.",[
                    'mode' => 'TRAITEMENT AUTOMATIQUE',
                    'payload' => $data
                ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => static::$erreur400
            ], Response::HTTP_OK);
        }

        // Vérification du token
        $token = $this->params->get('api.client_token');
        if ($data->token !== $token){
            $this->logger->error("[Traitement-Automatique] 🚫 Vous n'êtes pas autorisé à acceder à ce service.",
            ['token' => $data->token]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'error',
                'message' => static::$erreur403
            ], Response::HTTP_FORBIDDEN);
        }

        // On extrait la liste des projets pour le portefeuille depuis la table batch_traitement
        $les_projets = $this->listeProjetService->listeProjet($data->nom_traitement, $data->portefeuille);

        if ($les_projets['code'] === 404){
            $this->logger->warning("[Traitement-Automatique] ❌ La liste est vide ou n'existe plus.", [
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
        $utilisateur_collecte = "🤖 I am a Robot";

        // Création du job principal
        $execution_id = new Ulid();

        $batchExecution = new BatchExecution(
            'Collecte du ' . date('d/m/Y H:i'),
            $execution_id,
            Ulid::fromString($data->traitement_id),
            $utilisateur_collecte,
            static::$traitementAutomatique
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
                'traitement_id' => $data->traitement_id,
                'wip' => 'in_progress = true'
                ]);

            return new JsonResponse([
                'code' => $update['code'],
                'type' => 'error',
                'message' => "Il n'est pas possible de mettre à jour le traitement (Erreur {$update['code']}).",
                'erreur' =>  $update['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        // =====================================================
        // === PROFILING - INITIALISATION ======================
        // =====================================================
        $totalStart = microtime(true);
        $profilingRecords = [];

        $this->profilerLogger->info('[PROFILING] --- Démarrage du traitement automatique ---');
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
                static::$traitementAutomatique,
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

            $journal = new BatchExecutionJournal();
            $journal->setCode($result['code']);
            $journal->setPortefeuille($data->portefeuille);
            $journal->setNomProjet($nom_projet);
            $journal->setCompteRendu($result['compte_rendu']);
            $journal->setDateExecution(new \DateTimeImmutable());

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
                    'success' => false,
                    'in_progress' => false,
                    'pending' => false,
                    'fin_traitement' => (new \DateTime('now', new \DateTimeZone(static::$europeParis)))->format(static::$dateFormat),
                    'traitement_id' => $data->traitement_id,
                ]);

                $code = $result['code'];
                $message = "❌ La collecte du projet $le_projet n'a pas abouti. Consultez le journal d’exécution pour plus d’informations.";

                $this->em->flush();
                return new JsonResponse(compact('code', 'message'), Response::HTTP_OK);
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
                'wip' => 'in_progress = false'
            ]);

            return new JsonResponse([
                'code' => $update['code'],
                'message' => "❌ Il n'est pas possible de mettre à jour le traitement (Erreur {$update['code']}).",
                'erreur' =>  $update['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        unset($map, $les_projets, $data, $profilingRecords);
        gc_collect_cycles();
        gc_mem_caches();

        return new JsonResponse([
            'code' => 200,
            'message' => 'Collecte terminée avec succès',
        ], Response::HTTP_OK);
    }
}
