<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

/* MODIF 2026-05-21 : déplacé dans App\Command\Prod. */
namespace App\Command\Prod;

use App\Entity\DcProcessingQueue;
use App\Exception\DependencyCheck\{DcEmptyPayloadException,DcGzipDecodeException, DcInvalidJsonException,DcInvalidStructureException, DcPayloadException};
use App\Repository\DcProcessingQueueRepository;
use App\Service\DependencyCheck\DependencyCheckIngester;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * MODIF 2026-05-08 : worker court de traitement de la queue OWASP DependencyCheck.
 *
 * Pourquoi un process court (pas un long-running Messenger) :
 *  - retour d'experience utilisateur : Messenger long-running fuit en mémoire.
 *  - process court = chaque execution est un PHP frais, l'OS récupère tout
 *    quand le process meurt. Zero fuite structurelle possible.
 *
 * Sequence d'une execution :
 *   1. reclamation des rows "processing" abandonnées (worker mort) -> requeue
 *   2. claimNextBatch() avec FOR UPDATE SKIP LOCKED -> rows passent en "processing"
 *   3. pour chaque row :
 *        - decompression du payload
 *        - parsing JSON (validation light)
 *        - PHASE 1 : marque "done" (la dédup CVE/dépendances arrive en phase 2)
 *   4. exit -> mémoire libérée par l'OS
 *
 * Modes :
 *   --batch=5       : taille du batch à traiter (default 5)
 *   --max-batches=1 : nombre de batches consécutifs (default 1, pour cron court)
 *   --watch=N       : DEV ONLY -> boucle infinie, sleep N secondes entre ticks
 *
 * Usage :
 *   php bin/console app:dependency-check:process              -> 1 batch, exit
 *   php bin/console app:dependency-check:process --batch=10   -> jusqu'a 10 rapports
 *   php bin/console app:dependency-check:process --watch=60   -> dev, boucle 60s
 *
 * Garde-fous mémoire :
 *   - $em->clear() entre chaque rapport
 *   - gc_collect_cycles() entre chaque rapport
 *   - exit garanti après max-batches (sauf --watch)
 */
#[AsCommand(
    name: 'app:dependency-check:process',
    description: "Traite la queue d'ingestion OWASP DependencyCheck (worker court).",
)]
class DependencyCheckProcessCommand extends Command
{
    private const STALE_TIMEOUT_SECONDS = 300; // 5 min : un row "processing" plus vieux est requeue
    private const MAX_ATTEMPTS = 3;            // au-delà : status=failed définitif

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly DcProcessingQueueRepository $queueRepository,
        private readonly LoggerInterface $logger,
        // MODIF 2026-05-08 : injection de l'Ingester metier.
        // Optionnel : si null (cas tests Unit pre-phase-2), on bascule sur le
        // comportement legacy (juste validation).
        private readonly ?DependencyCheckIngester $ingester = null,
    ) {
        parent::__construct();
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 24/05/2026 10:43:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'batch',
                'b',
                InputOption::VALUE_REQUIRED,
                'Nombre de rapports traites par batch.',
                5
            )
            ->addOption(
                'max-batches',
                'm',
                InputOption::VALUE_REQUIRED,
                'Nombre de batches consecutifs (1 par defaut pour cron court).',
                1
            )
            ->addOption(
                'watch',
                'w',
                InputOption::VALUE_REQUIRED,
                'DEV ONLY : boucle infinie avec sleep N secondes entre ticks.',
                null
            );
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 24/05/2026 10:43:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $batchSize  = max(1, (int) $input->getOption('batch'));
        $maxBatches = max(1, (int) $input->getOption('max-batches'));
        $watch      = $input->getOption('watch');
        $watchSec   = $watch !== null ? max(5, (int) $watch) : null;

        if ($watchSec !== null) {
            $io->warning(sprintf('Mode WATCH actif (toutes les %d sec). DEV ONLY. Ctrl-C pour stopper.', $watchSec));
            return $this->runWatch($io, $batchSize, $watchSec);
        }

        return $this->runOneShot($io, $batchSize, $maxBatches);
    }

    /**
     * [Description for runOneShot]
     * Execution one-shot (cron classique).
     *
     * @param SymfonyStyle $io
     * @param int $batchSize
     * @param int $maxBatches
     *
     * @return int
     *
     * Created at: 24/05/2026 10:43:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function runOneShot(SymfonyStyle $io, int $batchSize, int $maxBatches): int
    {
        $totals = ['done' => 0, 'failed' => 0, 'requeued' => 0];

        // 1. Récupère les processing abandonnés
        $totals['requeued'] = $this->queueRepository->reclaimStaleProcessing(self::STALE_TIMEOUT_SECONDS);
        if ($totals['requeued'] > 0) {
            $io->note(sprintf('%d row(s) "processing" orphelines requeue(es).', $totals['requeued']));
        }

        // 2. Boucle batches
        for ($i = 0; $i < $maxBatches; $i++) {
            $batchTotals = $this->processOneBatch($io, $batchSize);
            $totals['done']   += $batchTotals['done'];
            $totals['failed'] += $batchTotals['failed'];

            // Si le batch était vide, inutile de boucler
            if ($batchTotals['claimed'] === 0) {
                break;
            }
        }

        $this->printSummary($io, $totals);

        /* MODIF 2026-07-21 : le code retour ignorait totals['failed'] — un rapport
         * passé en status=failed définitif (après MAX_ATTEMPTS) ne remontait donc
         * jamais comme échec côté cron/supervision, alors que le worker avait bien
         * détecté et enregistré l'échec. Sans effet sur la planification (Supercronic
         * ne fait rien de spécial sur un code non nul, il relance à la minute
         * suivante quoi qu'il arrive) — seule la visibilité du log change. */
        return $totals['failed'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * [Description for runWatch]
     * Execution en boucle (dev only).
     *
     * @param SymfonyStyle $io
     * @param int $batchSize
     * @param int $sleepSec
     *
     * @return int
     *
     * Created at: 24/05/2026 10:43:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function runWatch(SymfonyStyle $io, int $batchSize, int $sleepSec): int
    {
        $cycle = 0;
        while (true) {
            $cycle++;
            $io->section(sprintf('Tick %d (%s)', $cycle, (new \DateTimeImmutable())->format('H:i:s')));

            $requeued = $this->queueRepository->reclaimStaleProcessing(self::STALE_TIMEOUT_SECONDS);
            if ($requeued > 0) {
                $io->text(sprintf('  %d requeue(es)', $requeued));
            }

            $batchTotals = $this->processOneBatch($io, $batchSize);
            $io->text(sprintf(
                '  done: %d  failed: %d  claimed: %d',
                $batchTotals['done'],
                $batchTotals['failed'],
                $batchTotals['claimed']
            ));

            if ($batchTotals['claimed'] === 0) {
                $io->text(sprintf('  (queue vide, sleep %ds)', $sleepSec));
            }
            sleep($sleepSec);
        }
    }

    /**
     * [Description for processOneBatch]
     * Réclame et traite un batch.
     *
     * @param SymfonyStyle $io
     * @param int $batchSize
     *
     * @return array{claimed: int, done: int, failed: int}
     *
     * Created at: 24/05/2026 10:44:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function processOneBatch(SymfonyStyle $io, int $batchSize): array
    {
        $totals = ['claimed' => 0, 'done' => 0, 'failed' => 0];

        // Transaction sur le claim pour permettre le SKIP LOCKED
        $this->em->beginTransaction();
        try {
            $claimed = $this->queueRepository->claimNextBatch($batchSize);
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            $io->error('Échec claim batch : ' . $e->getMessage());
            $this->logger->critical('[DC-Worker] Échec claim batch.', ['error' => $e->getMessage()]);
            return $totals;
        }

        $totals['claimed'] = count($claimed);
        if ($totals['claimed'] === 0) {
            return $totals;
        }

        $io->text(sprintf('Batch réclame : %d row(s)', $totals['claimed']));

        // Capture des IDs : on travaille en re-fetch pour permettre $em->clear() entre
        // chaque rapport sans détacher les entities suivantes du tableau.
        $ids = array_map(static fn(DcProcessingQueue $e) => $e->getId(), $claimed);
        $this->em->clear();

        foreach ($ids as $entryId) {
            $entry = $this->queueRepository->find($entryId);
            if ($entry === null) {
                $io->warning(sprintf('Row %d disparue entre claim et traitement, skip.', $entryId));
                continue;
            }
            $ulid = $entry->getUlid();
            try {
                $this->processOne($entry);

                $entry->setStatus(DcProcessingQueue::STATUS_DONE);
                $entry->setProcessedAt(new \DateTimeImmutable());
                $entry->setErrorMessage(null);
                $this->em->flush();

                $totals['done']++;
                $io->text(sprintf('  ✓ %s done (%d:%s:%s)',
                    $ulid, $totals['done'],
                    $entry->getProjectArtifact(),
                    $entry->getProjectVersion()
                ));
            } catch (\Throwable $e) {
                $message = $e->getMessage();
                $finalFail = $entry->getAttempts() >= self::MAX_ATTEMPTS;

                $entry->setStatus($finalFail ? DcProcessingQueue::STATUS_FAILED : DcProcessingQueue::STATUS_QUEUED);
                $entry->setProcessedAt($finalFail ? new \DateTimeImmutable() : null);
                $entry->setStartedAt(null);
                $entry->setErrorMessage(substr($message, 0, 65535));

                try {
                    $this->em->flush();
                } catch (\Throwable $flushErr) {
                    $this->em->clear();
                    $this->logger->critical('[DC-Worker] Echec flush apres erreur traitement.', [
                        'ulid'   => $ulid,
                        'error'  => $flushErr->getMessage(),
                        'origin' => $message,
                    ]);
                }

                if ($finalFail) {
                    $totals['failed']++;
                    $io->text(sprintf('  ✗ %s FAILED définitif (%s)', $ulid, $message));
                } else {
                    $io->text(sprintf('  ⤴ %s requeue (tentative %d/%d : %s)',
                        $ulid, $entry->getAttempts(), self::MAX_ATTEMPTS, $message));
                }

                $this->logger->error('[DC-Worker] Échec traitement.', [
                    'ulid'        => $ulid,
                    'attempts'    => $entry->getAttempts(),
                    'final_fail'  => $finalFail,
                    'error'       => $message,
                ]);
            } finally {
                // Garde-fou mémoire entre chaque rapport
                $this->em->clear();
                gc_collect_cycles();
            }
        }

        return $totals;
    }

    /**
     * [Description for processOne]
     * Traitement d'UN rapport.
     *
     * MODIF 2026-05-08 :
     *   - si Ingester injecte : délègue tout le pipeline metier (parse + dedup +
     *     creation scan + findings) avec idempotence par (projet, version, scan_date).
     *   - sinon : fallback sur la validation light (compatibilité tests Unit phase 1).
     *
     * @param DcProcessingQueue $entry
     * @throws DcPayloadException si le payload est illisible, mal décompressé,
     *                            mal forme JSON ou non conforme au schema DC.
     *
     * @return void
     *
     * Created at: 24/05/2026 10:44:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function processOne(DcProcessingQueue $entry): void
    {
        if ($this->ingester !== null) {
            // PHASE 2 : ingestion complète via le service metier.
            // L'Ingester gère sa propre transaction interne.
            $scan = $this->ingester->ingest($entry);
            $this->logger->info('[DC-Worker] Rapport ingéré (phase 2).', [
                'ulid'      => $entry->getUlid(),
                'scan_id'   => $scan->getId(),
                'cve_total' => $scan->getCveCountTotal(),
            ]);
            return;
        }

        // PHASE 1 fallback : validation light sans persistence metier.
        $payloadGz = $entry->getPayloadGz();
        if (is_resource($payloadGz)) {
            $payloadGz = stream_get_contents($payloadGz);
        }
        if (!is_string($payloadGz) || $payloadGz === '') {
            throw new DcEmptyPayloadException('Payload vide ou illisible.');
        }

        $jsonString = @gzdecode($payloadGz);
        if ($jsonString === false) {
            throw new DcGzipDecodeException('Decompression gzip échouée.');
        }

        try {
            $parsed = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new DcInvalidJsonException('JSON invalide en queue : ' . $e->getMessage(), 0, $e);
        }
        if (!is_array($parsed) || !isset($parsed['dependencies'])) {
            throw new DcInvalidStructureException('Structure JSON invalide (dependencies manquant).');
        }

        $depCount = is_array($parsed['dependencies']) ? count($parsed['dependencies']) : 0;
        $vulnCount = 0;
        foreach ($parsed['dependencies'] as $dep) {
            if (is_array($dep) && isset($dep['vulnerabilities']) && is_array($dep['vulnerabilities'])) {
                $vulnCount += count($dep['vulnerabilities']);
            }
        }

        $this->logger->info('[DC-Worker] Rapport traité (phase 1 fallback).', [
            'ulid'         => $entry->getUlid(),
            'dependencies' => $depCount,
            'cve_total'    => $vulnCount,
        ]);

        unset($parsed, $jsonString, $payloadGz);
    }

    /**
     * [Description for printSummary]
     *
     * @param SymfonyStyle $io
     * @param array $totals
     *
     * @return void
     *
     * Created at: 24/05/2026 10:45:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printSummary(SymfonyStyle $io, array $totals): void
    {
        $io->newLine();
        $io->definitionList(
            ['Done'      => $totals['done']],
            ['Failed'    => $totals['failed']],
            ['Requeued'  => $totals['requeued']],
        );
    }
}
