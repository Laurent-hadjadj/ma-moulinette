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

use App\Repository\DcProcessingQueueRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\{InputInterface, InputOption};
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * MODIF 2026-05-08 : purge des rows terminées (done|failed) de la
 * queue OWASP DependencyCheck.
 *
 * Pas de purge des status queued/processing :
 *  - queued ancien = anomalie à investiguer (worker en panne ?)
 *  - processing ancien = traité par reclaimStaleProcessing() du worker
 *
 * Usage typique :
 *   - cron quotidien (1 fois par jour, env 03h00) en prod
 *   - manuel a la demande en dev
 *
 * Exemples :
 *   php bin/console app:dependency-check:purge                  -> purge >30j
 *   php bin/console app:dependency-check:purge --days=7         -> purge >7j
 *   php bin/console app:dependency-check:purge --dry-run        -> simulation
 */
#[AsCommand(
    name: 'app:dependency-check:purge',
    description: 'Purge les rapports OWASP DependencyCheck termines plus vieux que N jours.',
)]
class DependencyCheckPurgeCommand extends Command
{
    public function __construct(
        private readonly DcProcessingQueueRepository $queueRepository,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    /**
     * [Description for configure]
     *
     * @return void
     *
     * Created at: 24/05/2026 10:46:03 (Europe/Paris)
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
                'Nombre de jours de retention (rows plus vieilles sont purgées).',
                30
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Affiche le nombre de rows qui seraient purgées sans rien supprimer.'
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
     * Created at: 24/05/2026 10:46:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $days   = max(1, (int) $input->getOption('days'));
        $dryRun = (bool) $input->getOption('dry-run');

        $threshold = (new \DateTimeImmutable())->modify("-{$days} days");

        $io->section(sprintf(
            'Purge OWASP DependencyCheck : retention %d jour(s) (avant %s)%s',
            $days,
            $threshold->format('Y-m-d H:i:s'),
            $dryRun ? ' [DRY-RUN]' : ''
        ));

        try {
            $count = $this->queueRepository->purgeTerminatedBefore($threshold, $dryRun);
        } catch (\Throwable $e) {
            $io->error('❌ Échec de la purge : ' . $e->getMessage());
            $this->logger->critical('[DC-Purge] ❌ Échec.', [
                'error'   => $e->getMessage(),
                'days'    => $days,
                'dry_run' => $dryRun,
            ]);
            return Command::FAILURE;
        }

        if ($dryRun) {
            $io->note(sprintf('%d row(s) seraient purgées (dry-run, rien supprime).', $count));
        } else {
            $io->success(sprintf('ℹ️ %d row(s) terminée(s) supprimée(s).', $count));
            $this->logger->info('[DC-Purge] ℹ️ Purge effectuée.', [
                'rows_deleted' => $count,
                'days'         => $days,
                'threshold'    => $threshold->format('c'),
            ]);
        }

        // Statistiques restantes
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
