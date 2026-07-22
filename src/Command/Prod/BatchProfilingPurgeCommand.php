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

use App\Repository\BatchProfilingRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * MODIF 2026-07-20 : purge des lignes de batch_profiling plus anciennes que
 * N jours, via la fonction PostgreSQL ma_moulinette.purge_batch_profiling()
 * (migrations/POSTGRESQL/50_functions/purge_batch_profiling.sql) — définie en
 * base mais jusqu'ici jamais appelée par le code applicatif (voir
 * back-office/profiling.md).
 *
 * Usage typique :
 *   - cron quotidien/hebdomadaire en prod
 *   - manuel a la demande en dev
 *
 * Exemples :
 *   php bin/console app:batch-profiling:purge                  -> purge >90j
 *   php bin/console app:batch-profiling:purge --days=30         -> purge >30j
 *   php bin/console app:batch-profiling:purge --dry-run         -> simulation
 */
#[AsCommand(
    name: 'app:batch-profiling:purge',
    description: 'Purge les mesures de performance des traitements batch plus vieilles que N jours.',
)]
class BatchProfilingPurgeCommand extends Command
{
    public function __construct(
        private readonly BatchProfilingRepository $repository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 20/07/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_REQUIRED,
                'Nombre de jours de retention (lignes plus vieilles sont purgées).',
                90
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Affiche le nombre de lignes qui seraient purgées sans rien supprimer.'
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
     * Created at: 20/07/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days   = max(1, (int) $input->getOption('days'));
        $dryRun = (bool) $input->getOption('dry-run');

        $io->section(sprintf(
            'Purge Batch Profiling : retention %d jour(s)%s',
            $days,
            $dryRun ? ' [DRY-RUN]' : ''
        ));

        try {
            if ($dryRun) {
                $count = $this->repository->countOlderThan($days);
            } else {
                $count = $this->repository->purge($days);
            }
        } catch (\Throwable $e) {
            $io->error('❌ Échec de la purge : ' . $e->getMessage());
            $this->logger->critical('[Batch-Profiling-Purge] ❌ Échec.', [
                'error'   => $e->getMessage(),
                'days'    => $days,
                'dry_run' => $dryRun,
            ]);
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note(sprintf('%d ligne(s) seraient purgées (dry-run, rien supprimé).', $count));
        } else {
            $io->success(sprintf('ℹ️ %d ligne(s) supprimée(s).', $count));
            $this->logger->info('[Batch-Profiling-Purge] ℹ️ Purge effectuée.', [
                'rows_deleted' => $count,
                'days'         => $days,
            ]);
        }

        return Command::SUCCESS;
    }
}
