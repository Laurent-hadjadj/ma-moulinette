<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Command\Prod;

use App\Entity\NpmProcessingQueue;
use App\Repository\NpmProcessingQueueRepository;
use App\Service\NpmAudit\NpmAuditIngester;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Worker court de traitement de la queue npm audit — même pattern que
 * DependencyCheckProcessCommand (process court, pas de Messenger
 * long-running, garde-fous mémoire entre chaque rapport).
 *
 * Modes :
 *   --batch=5       : taille du batch à traiter (default 5)
 *   --max-batches=1 : nombre de batches consécutifs (default 1, cron court)
 *   --watch=N       : DEV ONLY -> boucle infinie, sleep N secondes entre ticks
 */
#[AsCommand(
    name: 'app:npm-audit:process',
    description: "Traite la queue d'ingestion npm audit (worker court).",
)]
class NpmAuditProcessCommand extends Command
{
    private const STALE_TIMEOUT_SECONDS = 300;
    private const MAX_ATTEMPTS = 3;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly NpmProcessingQueueRepository $queueRepository,
        private readonly NpmAuditIngester $ingester,
        #[Autowire(service: 'monolog.logger.cron')]
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    protected function configure(): void
    {
        $this
            ->addOption('batch', 'b', InputOption::VALUE_REQUIRED, 'Nombre de rapports traités par batch.', 5)
            ->addOption('max-batches', 'm', InputOption::VALUE_REQUIRED, 'Nombre de batches consécutifs (1 par défaut pour cron court).', 1)
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
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
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
     *
     * @param SymfonyStyle $io
     * @param int $batchSize
     * @param int $maxBatches
     *
     * @return int
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function runOneShot(SymfonyStyle $io, int $batchSize, int $maxBatches): int
    {
        $totals = ['done' => 0, 'failed' => 0, 'requeued' => 0];

        $totals['requeued'] = $this->queueRepository->reclaimStaleProcessing(self::STALE_TIMEOUT_SECONDS);
        if ($totals['requeued'] > 0) {
            $io->note(sprintf('%d row(s) "processing" orphelines requeuées.', $totals['requeued']));
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
     *
     * @param SymfonyStyle $io
     * @param int $batchSize
     * @param int $sleepSec
     *
     * @return int
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function runWatch(SymfonyStyle $io, int $batchSize, int $sleepSec): int
    {
        $cycle = 0;
        for (;;) {
            $cycle++;
            $io->section(sprintf('Tick %d (%s)', $cycle, (new \DateTimeImmutable())->format('H:i:s')));

            $requeued = $this->queueRepository->reclaimStaleProcessing(self::STALE_TIMEOUT_SECONDS);
            if ($requeued > 0) {
                $io->text(sprintf('  %d requeuée(s)', $requeued));
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
     *
     * @param SymfonyStyle $io
     * @param int $batchSize
     *
     * @return array{claimed: int, done: int, failed: int}
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
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
            $this->logger->critical('[NpmAudit-Worker] Échec claim batch.', ['error' => $e->getMessage()]);
            return $totals;
        }

        $totals['claimed'] = count($claimed);
        if ($totals['claimed'] === 0) {
            $this->logHeartbeatIfDue();
            return $totals;
        }

        $io->text(sprintf('Batch réclamé : %d row(s)', $totals['claimed']));

        $ids = array_map(static fn(NpmProcessingQueue $e) => $e->getId(), $claimed);
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
                $this->logger->info('[NpmAudit-Worker] Rapport ingéré.', [
                    'ulid'       => $ulid,
                    'scan_id'    => $scan->getId(),
                    'vuln_total' => $scan->getVulnCountTotal(),
                ]);

                $entry->setStatus(NpmProcessingQueue::STATUS_DONE);
                $entry->setProcessedAt(new \DateTimeImmutable());
                $entry->setErrorMessage(null);
                $this->em->flush();

                $totals['done']++;
                $io->text(sprintf(
                    '  ✓ %s done (%d:%s:%s)',
                    $ulid,
                    $totals['done'],
                    $entry->getProjectArtifact(),
                    $entry->getProjectVersion()
                ));
            } catch (\Throwable $e) {
                $message = $e->getMessage();
                $finalFail = $entry->getAttempts() >= self::MAX_ATTEMPTS;

                $entry->setStatus($finalFail ? NpmProcessingQueue::STATUS_FAILED : NpmProcessingQueue::STATUS_QUEUED);
                $entry->setProcessedAt($finalFail ? new \DateTimeImmutable() : null);
                $entry->setStartedAt(null);
                $entry->setErrorMessage(substr($message, 0, 65535));

                try {
                    $this->em->flush();
                } catch (\Throwable $flushErr) {
                    $this->em->clear();
                    $this->logger->critical('[NpmAudit-Worker] Échec flush après erreur traitement.', [
                        'ulid'   => $ulid,
                        'error'  => $flushErr->getMessage(),
                        'origin' => $message,
                    ]);
                }

                if ($finalFail) {
                    $totals['failed']++;
                    $io->text(sprintf('  ✗ %s FAILED définitif (%s)', $ulid, $message));
                } else {
                    $io->text(sprintf(
                        '  ⤴ %s requeue (tentative %d/%d : %s)',
                        $ulid,
                        $entry->getAttempts(),
                        self::MAX_ATTEMPTS,
                        $message
                    ));
                }

                $this->logger->error('[NpmAudit-Worker] Échec traitement.', [
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
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
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


    /**
     * [Description for logHeartbeatIfDue]
     * Preuve de vie throttlée : la commande tourne chaque minute via la
     * crontab mais ne journalise rien quand la queue est vide (cf.
     * processOneBatch ci-dessus), donc cron.log n'apparaît jamais tant
     * qu'aucun rapport n'a été ingéré. Log un heartbeat toutes les 15
     * minutes (sans état persisté : simple modulo sur la minute courante,
     * aligné sur la fréquence "* * * * *" de la crontab) plutôt qu'à
     * chaque exécution, pour éviter de noyer cron.log une fois les files
     * réellement actives.
     * @return void
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private function logHeartbeatIfDue(): void
    {
        $minute = (int) (new \DateTimeImmutable())->format('i');
        if (self::isHeartbeatDue($minute)) {
            $this->logger->info('[NpmAudit-Worker] Heartbeat - file vide.');
        }
    }

    /**
     * [Description for isHeartbeatDue]
     *
     * @param int $minute
     *
     * @return bool
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    private static function isHeartbeatDue(int $minute): bool
    {
        return $minute % 15 === 0;
    }
}
