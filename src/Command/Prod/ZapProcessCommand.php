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

namespace App\Command\Prod;

use App\Entity\ZapProcessingQueue;
use App\Repository\ZapProcessingQueueRepository;
use App\Service\Zap\ZapIngester;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * MODIF 2026-10-13 : worker court de traitement de la queue ZAP (DAST), sur
 * le gabarit DependencyCheckProcessCommand.
 *
 * Pourquoi un process court (pas un long-running Messenger) : mêmes raisons
 * que Dependency-Check — chaque execution est un PHP frais, l'OS récupère
 * tout quand le process meurt, zero fuite structurelle possible.
 *
 * Modes :
 *   --batch=5       : taille du batch à traiter (default 5)
 *   --max-batches=1 : nombre de batches consécutifs (default 1, pour cron court)
 *   --watch=N       : DEV ONLY -> boucle infinie, sleep N secondes entre ticks
 *
 * Usage :
 *   php bin/console app:zap:process              -> 1 batch, exit
 *   php bin/console app:zap:process --batch=10   -> jusqu'a 10 rapports
 *   php bin/console app:zap:process --watch=60   -> dev, boucle 60s
 */
#[AsCommand(
    name: 'app:zap:process',
    description: "Traite la queue d'ingestion ZAP (DAST, worker court).",
)]
class ZapProcessCommand extends Command
{
    private const STALE_TIMEOUT_SECONDS = 300; // 5 min : un row "processing" plus vieux est requeue
    private const MAX_ATTEMPTS = 3;            // au-delà : status=failed définitif

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly ZapProcessingQueueRepository $queueRepository,
        private readonly ZapIngester $ingester,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->addOption('batch', 'b', InputOption::VALUE_REQUIRED, 'Nombre de rapports traites par batch.', 5)
            ->addOption('max-batches', 'm', InputOption::VALUE_REQUIRED, 'Nombre de batches consecutifs (1 par defaut pour cron court).', 1)
            ->addOption('watch', 'w', InputOption::VALUE_REQUIRED, 'DEV ONLY : boucle infinie avec sleep N secondes entre ticks.', null);
    }

    /**
     * [Description for execute]
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
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
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function runOneShot(SymfonyStyle $io, int $batchSize, int $maxBatches): int
    {
        $totals = ['done' => 0, 'failed' => 0, 'requeued' => 0];

        $totals['requeued'] = $this->queueRepository->reclaimStaleProcessing(self::STALE_TIMEOUT_SECONDS);
        if ($totals['requeued'] > 0) {
            $io->note(sprintf('%d row(s) "processing" orphelines requeue(es).', $totals['requeued']));
        }

        for ($i = 0; $i < $maxBatches; $i++) {
            $batchTotals = $this->processOneBatch($io, $batchSize);
            $totals['done']   += $batchTotals['done'];
            $totals['failed'] += $batchTotals['failed'];

            if ($batchTotals['claimed'] === 0) {
                break;
            }
        }

        $this->printSummary($io, $totals);

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
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
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
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function processOneBatch(SymfonyStyle $io, int $batchSize): array
    {
        $totals = ['claimed' => 0, 'done' => 0, 'failed' => 0];

        $this->em->beginTransaction();
        try {
            $claimed = $this->queueRepository->claimNextBatch($batchSize);
            $this->em->commit();
        } catch (\Throwable $e) {
            $this->em->rollback();
            $io->error('Échec claim batch : ' . $e->getMessage());
            $this->logger->critical('[ZAP-Worker] Échec claim batch.', ['error' => $e->getMessage()]);
            return $totals;
        }

        $totals['claimed'] = count($claimed);
        if ($totals['claimed'] === 0) {
            return $totals;
        }

        $io->text(sprintf('Batch réclame : %d row(s)', $totals['claimed']));

        $ids = array_map(static fn(ZapProcessingQueue $e) => $e->getId(), $claimed);
        $this->em->clear();

        foreach ($ids as $entryId) {
            $entry = $this->queueRepository->find($entryId);
            if ($entry === null) {
                $io->warning(sprintf('Row %d disparue entre claim et traitement, skip.', $entryId));
                continue;
            }
            $ulid = $entry->getUlid();
            try {
                $scan = $this->ingester->ingest($entry);

                $entry->setStatus(ZapProcessingQueue::STATUS_DONE);
                $entry->setProcessedAt(new \DateTimeImmutable());
                $entry->setErrorMessage(null);
                $this->em->flush();

                $totals['done']++;
                $io->text(sprintf('  ✓ %s done (scan #%s, %s:%s)',
                    $ulid, (string) $scan->getId(), $entry->getMavenKey(), $entry->getProjectVersion()
                ));
            } catch (\Throwable $e) {
                $message = $e->getMessage();
                $finalFail = $entry->getAttempts() >= self::MAX_ATTEMPTS;

                $entry->setStatus($finalFail ? ZapProcessingQueue::STATUS_FAILED : ZapProcessingQueue::STATUS_QUEUED);
                $entry->setProcessedAt($finalFail ? new \DateTimeImmutable() : null);
                $entry->setStartedAt(null);
                $entry->setErrorMessage(substr($message, 0, 65535));

                try {
                    $this->em->flush();
                } catch (\Throwable $flushErr) {
                    $this->em->clear();
                    $this->logger->critical('[ZAP-Worker] Echec flush apres erreur traitement.', [
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

                $this->logger->error('[ZAP-Worker] Échec traitement.', [
                    'ulid'       => $ulid,
                    'attempts'   => $entry->getAttempts(),
                    'final_fail' => $finalFail,
                    'error'      => $message,
                ]);
            } finally {
                $this->em->clear();
                gc_collect_cycles();
            }
        }

        return $totals;
    }

    /**
     * [Description for printSummary]
     *
     * @param SymfonyStyle $io
     * @param array{done: int, failed: int, requeued: int} $totals
     *
     * @return void
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function printSummary(SymfonyStyle $io, array $totals): void
    {
        $io->newLine();
        $io->definitionList(
            ['Done'     => $totals['done']],
            ['Failed'   => $totals['failed']],
            ['Requeued' => $totals['requeued']],
        );
    }
}
