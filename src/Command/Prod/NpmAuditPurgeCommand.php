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

use App\Repository\NpmProcessingQueueRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Purge des rows terminées (done|failed) de la queue npm audit — même
 * pattern que DependencyCheckPurgeCommand. Ne purge jamais queued/processing
 * (ancien queued = anomalie à investiguer, processing ancien = rattrapé par
 * reclaimStaleProcessing() du worker).
 *
 * Exemples :
 *   php bin/console app:npm-audit:purge                   -> purge >30j
 *   php bin/console app:npm-audit:purge --days=7          -> purge >7j
 *   php bin/console app:npm-audit:purge --dry-run         -> simulation
 */
#[AsCommand(
    name: 'app:npm-audit:purge',
    description: 'Purge les rapports npm audit terminés plus vieux que N jours.',
)]
class NpmAuditPurgeCommand extends Command
{
    public function __construct(
        private readonly NpmProcessingQueueRepository $queueRepository,
        #[Autowire(service: 'monolog.logger.cron')]
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_REQUIRED, 'Nombre de jours de rétention (rows plus vieilles sont purgées).', 30)
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Affiche le nombre de rows qui seraient purgées sans rien supprimer.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days   = max(1, (int) $input->getOption('days'));
        $dryRun = (bool) $input->getOption('dry-run');

        $threshold = (new \DateTimeImmutable())->modify("-{$days} days");

        $io->section(sprintf(
            'Purge npm audit : rétention %d jour(s) (avant %s)%s',
            $days,
            $threshold->format('Y-m-d H:i:s'),
            $dryRun ? ' [DRY-RUN]' : ''
        ));

        try {
            $count = $this->queueRepository->purgeTerminatedBefore($threshold, $dryRun);
        } catch (\Throwable $e) {
            $io->error('❌ Échec de la purge : ' . $e->getMessage());
            $this->logger->critical('[NpmAudit-Purge] ❌ Échec.', [
                'error'   => $e->getMessage(),
                'days'    => $days,
                'dry_run' => $dryRun,
            ]);
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note(sprintf('%d row(s) seraient purgées (dry-run, rien supprimé).', $count));
        } else {
            $io->success(sprintf('ℹ️ %d row(s) terminée(s) supprimée(s).', $count));
            $this->logger->info('[NpmAudit-Purge] ℹ️ Purge effectuée.', [
                'rows_deleted' => $count,
                'days'         => $days,
                'threshold'    => $threshold->format('c'),
            ]);
        }

        $stats = $this->queueRepository->countByStatus();
        $io->definitionList(
            ['Restant queued'     => $stats['queued']],
            ['Restant processing' => $stats['processing']],
            ['Restant done'       => $stats['done']],
            ['Restant failed'     => $stats['failed']],
        );

        return Command::SUCCESS;
    }
}
